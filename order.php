<?php require 'config.php';
$o = q('SELECT * FROM orders WHERE token=?', [$_GET['t'] ?? ''])->fetch();
if (!$o) {
    http_response_code(404);
    die('ไม่พบคำสั่งซื้อ');
    if (user()) q("UPDATE notifications SET is_read=1 WHERE target='user' AND user_id=? AND link=?", [user()['id'], 'order.php?t=' . $o['token']]);
}
$err = '';
if ($o['status'] == 'confirmed' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    chk();
    $n = trim($_POST['name'] ?? '');
    $ph = trim($_POST['phone'] ?? '');
    $ad = trim($_POST['addr'] ?? '');
    $m = ($_POST['method'] ?? '') == 'bank' ? 'bank' : 'qr';
    if (!$n || !$ph || !$ad) $err = 'กรุณากรอกข้อมูลการจัดส่งให้ครบ';
    elseif (!($fn = upload($_FILES['slip'] ?? null, 'slips'))) $err = 'กรุณาอัปโหลดสลิป (JPG/PNG/WEBP ไม่เกิน 3MB)';
    else {
        q("UPDATE orders SET status='paid',method=?,cust_name=?,phone=?,addr=?,slip=?,note=NULL,paid_at=NOW() WHERE id=? AND status='confirmed'", [$m, $n, $ph, $ad, $fn, $o['id']]);
        notify('admin', null, '💳 ' . $o['order_no'] . ' ชำระเงินแล้ว รอตรวจสอบสลิป', 'admin/orders.php?v=pay');
        header('Location: order.php?t=' . $o['token']);
        exit;
    }
}
$s = $o['status'];
$tot = total($o['id']);
head('ออเดอร์ ' . $o['order_no'], 'orders');
if ($s == 'pending'): ?>
    <div class="card okbox"><i>✓</i>
        <h2 style="color:var(--ok)">สั่งซื้อสำเร็จแล้ว!</h2>
        <p>หมายเลขคำสั่งซื้อ: <b><?= e($o['order_no']) ?></b><br>วันที่สั่งซื้อ: <?= e($o['created_at']) ?></p>
        <div class="note"><b>กำลังตรวจสอบสินค้า</b><br>กรุณาตรวจสอบสถานะในเมนู "คำสั่งซื้อของฉัน" ร้านจะแจ้งให้ชำระเงินเมื่อสินค้าพร้อม</div><?= items_html($o['id']) ?><a class="btn" href="my-orders.php">ดูรายละเอียดคำสั่งซื้อ</a>
    </div>
<?php elseif ($s == 'rejected'): ?>
    <div class="card c">
        <h3>ออเดอร์ <?= e($o['order_no']) ?> <?= badge($s) ?></h3>
        <p class="alert"><?= e($o['note'] ?: 'ขออภัย สินค้าไม่เพียงพอ') ?></p><?= items_html($o['id']) ?><a class="btn" href="products.php">เลือกสินค้าใหม่</a>
    </div>
<?php elseif ($s == 'confirmed'):
    if ($err) echo '<div class="alert">' . e($err) . '</div>';
    if ($o['note']) echo '<div class="alert">สลิปไม่ผ่าน: ' . e($o['note']) . ' กรุณาอัปโหลดใหม่</div>'; ?>
    <h2 class="sec">ชำระเงิน · <?= e($o['order_no']) ?></h2>
    <form method="post" enctype="multipart/form-data" class="cols2"><?= csf() ?>
        <div class="card"><b>ข้อมูลการจัดส่ง</b><label>ชื่อ-นามสกุล</label><input name="name" value="<?= e(user()['name'] ?? '') ?>" required><label>เบอร์โทร</label><input name="phone" value="<?= e(user()['phone'] ?? '') ?>" required><label>ที่อยู่</label><textarea name="addr" rows="3" required></textarea>
            <label>วิธีชำระเงิน</label><select name="method" id="m">
                <option value="qr">สแกน QR Code</option>
                <option value="bank">โอนผ่านบัญชีธนาคาร</option>
            </select>
            <div class="mt"><?= items_html($o['id']) ?></div>
        </div>
        <div class="card c">
            <div class="mu">ยอดที่ต้องชำระ</div>
            <div class="big"><?= baht($tot) ?></div>
            <div id="qr"><?php $qr = setting('qr');
                            echo $qr ? '<img class="qr" src="uploads/qr/' . e($qr) . '" alt="QR">' : '<p class="mu">(ร้านยังไม่ได้อัปโหลด QR)</p>'; ?><div class="mu">สแกน QR Code เพื่อชำระเงิน</div>
            </div>
            <div id="bk" style="display:none" class="note"><?= e(setting('bank')) ?></div>
            <label style="text-align:left">อัปโหลดสลิปการโอนเงิน (JPG/PNG)</label><input type="file" name="slip" accept="image/jpeg,image/png,image/webp" required>
            <button class="btn full mt">ยืนยันการชำระเงิน</button>
        </div>
    </form>
    <script>
        m.onchange = () => {
            qr.style.display = m.value == "qr" ? "" : "none";
            bk.style.display = m.value == "bank" ? "" : "none"
        }
    </script>
<?php else: $d = ['paid' => 2, 'verified' => 3, 'shipped' => 4][$s];
    $lb = ['สั่งซื้อแล้ว', 'ชำระเงิน', 'ตรวจสอบการชำระ', 'จัดส่งแล้ว']; ?>
    <h2 class="sec">สถานะการชำระเงิน · <?= e($o['order_no']) ?> <?= badge($s) ?></h2>
    <div class="card">
        <div class="steps"><?php foreach ($lb as $i => $l) echo '<div class="' . ($i < $d ? 'on' : '') . '"><i>' . ($i < $d ? '✓' : ($i + 1)) . '</i>' . $l . '</div>' ?></div>
        <div class="note"><?php if ($s == 'paid') echo '<b>ส่งหลักฐานการชำระเงินแล้ว</b><br>รอร้านตรวจสอบ ขอบคุณค่ะ';
                            elseif ($s == 'verified') echo '<b>ตรวจสอบการชำระเงินแล้ว</b><br>ร้านกำลังเตรียมจัดส่งสินค้า';
                            else echo '<b>📦 จัดส่งแล้ว</b><br>ขนส่ง: ' . e($o['carrier']) . ' · เลขพัสดุ: <b>' . e($o['tracking']) . '</b>'; ?></div>
        <?= items_html($o['id']) ?><p class="mu">จัดส่งถึง: <?= e($o['cust_name']) ?> · <?= e($o['phone']) ?> · <?= nl2br(e($o['addr'])) ?></p>
    </div>
<?php endif;
foot();
