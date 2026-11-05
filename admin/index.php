<?php require __DIR__ . '/_layout.php';
ahead('Dashboard', 'index.php');
$n = fn($s) => (int)q("SELECT COUNT(*) FROM orders WHERE status=?", [$s])->fetchColumn();
$rev = (int)q("SELECT COALESCE(SUM(i.price*i.qty),0) FROM order_items i JOIN orders o ON o.id=i.order_id WHERE o.status IN('verified','shipped')")->fetchColumn();
$cards = [['📦', 'สินค้า', q('SELECT COUNT(*) FROM products')->fetchColumn()], ['🆕', 'คำสั่งซื้อใหม่', $n('pending')], ['🚚', 'รอจัดส่ง', $n('verified')], ['💰', 'ยอดขายรวม', baht($rev)]];
echo '<div class="stats">';
foreach ($cards as [$i, $l, $v]) echo '<div class="card"><span class="mu">' . $i . ' ' . $l . '</span><b>' . $v . '</b></div>';
echo '</div>';

// ยอดขาย 6 เดือนล่าสุด (เดือนที่ไม่มียอดแสดงเป็น 0)
$raw = q("SELECT DATE_FORMAT(o.created_at,'%Y-%m') m, SUM(i.price*i.qty) s FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.status IN('verified','shipped') GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
$m = [];
for ($j = 5; $j >= 0; $j--) {
    $key = date('Y-m', mktime(0, 0, 0, date('n') - $j, 1));
    $m[$key] = (float)($raw[$key] ?? 0);
}
$mx = max($m) ?: 1;
$bars = '';
$j = 0;
foreach ($m as $ym => $val) {
    $h = round(($val / $mx) * 120);
    $x = 25 + $j * 82;
    $bars .= '<rect x="' . $x . '" y="' . (150 - $h) . '" width="50" height="' . max($h, $val > 0 ? 2 : 0) . '" rx="4" fill="#c8922a"/>';
    if ($val > 0) $bars .= '<text x="' . ($x + 25) . '" y="' . (144 - $h) . '" font-size="11" text-anchor="middle" fill="#6b3f1d">' . number_format($val) . '</text>';
    $bars .= '<text x="' . ($x + 25) . '" y="172" font-size="11" text-anchor="middle" fill="#8a7a66">' . substr($ym, 5, 2) . '/' . substr($ym, 2, 2) . '</text>';
    $j++;
}
echo '<div class="cols2"><div class="card"><b>ยอดขายรายเดือน (บาท)</b>';
echo '<svg viewBox="0 0 520 185" style="width:100%"><line x1="10" y1="150" x2="510" y2="150" stroke="#e8dcc3"/>' . $bars . '</svg>';
if (!array_sum($m)) echo '<p class="mu">ยังไม่มียอดขาย (นับเมื่อแอดมินอนุมัติการชำระเงินแล้ว)</p>';
echo '</div><div class="card"><b>คำสั่งซื้อล่าสุด</b><table class="tb">';
foreach (q('SELECT * FROM orders ORDER BY id DESC LIMIT 6')->fetchAll() as $o) echo '<tr><td>' . e($o['order_no']) . '</td><td>' . badge($o['status']) . '</td><td class="r">' . baht(total($o['id'])) . '</td></tr>';
echo '</table></div></div>';
afoot();
