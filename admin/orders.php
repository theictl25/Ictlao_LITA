<?php require __DIR__ . '/_layout.php';
$v = $_GET['v'] ?? ($_POST['v'] ?? 'new');
if (!in_array($v, ['new', 'pay', 'ship', 'all'])) $v = 'new';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    chk();
    $a = $_POST['a'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $m = '';
    if ($a == 'confirm') {
        $pdo->beginTransaction();
        $it = q('SELECT product_id,qty FROM order_items WHERE order_id=?', [$id])->fetchAll();
        $ok = true;
        foreach ($it as $i) {
            $s = q('SELECT stock FROM products WHERE id=? FOR UPDATE', [$i['product_id']])->fetchColumn();
            if ($s === false || $s < $i['qty']) $ok = false;
        }
        if ($ok) {
            foreach ($it as $i) q('UPDATE products SET stock=stock-? WHERE id=?', [$i['qty'], $i['product_id']]);
            q("UPDATE orders SET status='confirmed' WHERE id=? AND status='pending'", [$id]);
        }
        $pdo->commit();
        $m = $ok ? 'ยืนยันแล้ว ลูกค้าสามารถเลือกชำระเงินได้' : 'สต็อกไม่พอ';
    }
    if ($a == 'reject') {
        q("UPDATE orders SET status='rejected',note=? WHERE id=? AND status='pending'", [trim($_POST['note'] ?? ''), $id]);
        $m = 'ปฏิเสธออเดอร์แล้ว';
    }
    if ($a == 'approve') {
        q("UPDATE orders SET status='verified' WHERE id=? AND status='paid'", [$id]);
        $m = 'อนุมัติการชำระเงินแล้ว ไปที่ Shipping Management';
    }
    if ($a == 'rejpay') {
        q("UPDATE orders SET status='confirmed',note=?,slip=NULL WHERE id=? AND status='paid'", [trim($_POST['note'] ?? '') ?: 'หลักฐานไม่ถูกต้อง', $id]);
        $m = 'ส่งกลับให้ลูกค้าชำระใหม่';
    }
    if ($a == 'ship') {
        $c = trim($_POST['carrier'] ?? '');
        $t = trim($_POST['tracking'] ?? '');
        if ($c && $t) {
            q("UPDATE orders SET status='shipped',carrier=?,tracking=?,shipped_at=NOW() WHERE id=? AND status='verified'", [$c, $t, $id]);
            $m = 'ยืนยันจัดส่งแล้ว';
        } else $m = 'กรุณากรอกขนส่งและเลขพัสดุ';
    }
    $o2 = q('SELECT status,carrier,tracking FROM orders WHERE id=?', [$id])->fetch();
    if ($o2) {
        $nm = [
            'confirm' => ['confirmed', 'พร้อมแล้ว กรุณาชำระเงิน'],
            'reject'  => ['rejected',  'ไม่สามารถจัดหาสินค้าได้'],
            'approve' => ['verified',  'ตรวจสอบการชำระเงินแล้ว กำลังเตรียมจัดส่ง'],
            'rejpay'  => ['confirmed', 'สลิปไม่ผ่าน กรุณาอัปโหลดสลิปใหม่'],
            'ship'    => ['shipped',   'จัดส่งแล้ว (' . $o2['carrier'] . ' ' . $o2['tracking'] . ')'],
        ];
        if (isset($nm[$a]) && $o2['status'] == $nm[$a][0]) unotify($id, $nm[$a][1]);
    }
    $_SESSION['flash'] = $m;
    header('Location: orders.php?v=' . $v);
    exit;
}
$T = ['new' => ['ออเดอร์ใหม่ / ตรวจสอบสินค้า', "status='pending'"], 'pay' => ['ตรวจสอบการชำระเงิน', "status='paid'"], 'ship' => ['จัดการจัดส่ง', "status='verified'"], 'all' => ['ออเดอร์ทั้งหมด', '1=1']][$v];
ahead($T[0], $v == 'all' ? 'orders.php?v=all' : 'orders.php?v=' . $v);
$rows = q('SELECT * FROM orders WHERE ' . $T[1] . ' ORDER BY id DESC')->fetchAll();
if (!$rows) echo '<p class="mu">ไม่มีรายการ</p>';
foreach ($rows as $o) {
    $s = $o['status'];
    echo '<div class="card ocard"><div class="h"><b>' . e($o['order_no']) . '</b>' . badge($s) . '<span class="mu">' . e($o['created_at']) . '</span></div>';
    if ($s == 'pending') {
        $ok = true;
        echo '<table class="tb"><tr><th>สินค้า</th><th>สั่ง</th><th>พร้อมส่ง</th><th>สถานะ</th></tr>';
        foreach (q('SELECT i.*,p.stock FROM order_items i LEFT JOIN products p ON p.id=i.product_id WHERE order_id=?', [$o['id']])->fetchAll() as $i) {
            $g = $i['stock'] !== null && $i['stock'] >= $i['qty'];
            if (!$g) $ok = false;
            echo '<tr><td>' . e($i['name']) . '</td><td>' . $i['qty'] . '</td><td>' . (int)$i['stock'] . '</td><td>' . ($g ? '<span style="color:var(--ok)">✔</span>' : '<span style="color:var(--bad)">⚠ ไม่พอ</span>') . '</td></tr>';
        }
        echo '<tr class="tot"><td colspan="3">ยอดรวม</td><td>' . baht(total($o['id'])) . '</td></tr></table><div class="row">' . ($ok ? af('confirm', $o['id'], 'ยืนยันคำสั่งซื้อ', 'ok', '', $v) : '<button class="btn sm" disabled>สต็อกไม่พอ</button>') . af('reject', $o['id'], 'ปฏิเสธ', 'bad', '<input name="note" value="สินค้าไม่เพียงพอ">', $v) . '</div>';
    } else echo items_html($o['id']);
    if ($o['cust_name']) echo '<div class="note" style="text-align:left"><b>' . e($o['cust_name']) . '</b> · ' . e($o['phone']) . '<br>' . nl2br(e($o['addr'])) . '<br><span class="mu">ชำระ: ' . ($o['method'] == 'qr' ? 'QR' : 'โอนบัญชี') . ' · ' . e($o['paid_at']) . '</span>' . ($o['slip'] ? '<br><a href="../uploads/slips/' . e($o['slip']) . '" target="_blank"><img class="slip" src="../uploads/slips/' . e($o['slip']) . '"></a>' : '') . '</div>';
    if ($s == 'paid') echo '<div class="row">' . af('approve', $o['id'], 'อนุมัติ', 'ok', '', $v) . af('rejpay', $o['id'], 'ปฏิเสธ', 'bad', '<input name="note" placeholder="เหตุผล">', $v) . '</div>';
    if ($s == 'verified') echo af('ship', $o['id'], 'ยืนยันการจัดส่ง', '', '<input name="carrier" placeholder="บริษัทขนส่ง เช่น Kerry Express" required><input name="tracking" placeholder="เลขพัสดุ" required>', $v);
    if ($s == 'shipped') echo '<p class="mu">🚚 ' . e($o['carrier']) . ' · ' . e($o['tracking']) . '</p>';
    echo '</div>';
}
afoot();
