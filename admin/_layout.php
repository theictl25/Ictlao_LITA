<?php require __DIR__ . '/../config.php';
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
function ahead($t, $act)
{
    $m = ['index.php' => ['📊', 'Dashboard'], 'products.php' => ['📦', 'Products'], 'orders.php?v=new' => ['🆕', 'New Orders / ตรวจสอบสินค้า'], 'orders.php?v=pay' => ['💳', 'Payment Verification'], 'orders.php?v=ship' => ['🚚', 'Shipping Management'], 'orders.php?v=all' => ['📋', 'All Orders'], 'customers.php' => ['👥', 'Customers'], 'settings.php' => ['⚙️', 'Settings']];
    q("UPDATE notifications SET is_read=1 WHERE target='admin' AND link=?", ['admin/' . $act]);
    $nb = q("SELECT link, COUNT(*) FROM notifications WHERE target='admin' AND is_read=0 GROUP BY link")->fetchAll(PDO::FETCH_KEY_PAIR);
    $nb['admin/orders.php?v=ship'] = (int)q("SELECT COUNT(*) FROM orders WHERE status='verified'")->fetchColumn();
    echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($t) . '</title><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/style.css"></head><body><div class="adm"><aside class="side"><b>' . (setting('logo') ? '<img src="../uploads/logo/' . e(setting('logo')) . '" alt="" style="height:30px;vertical-align:middle;border-radius:6px"> ' : '🌾 ') . e(setting('shop_name', 'RiceShop')) . ' Admin</b>';
    foreach ($m as $u => [$i, $l]) {
        $n = $nb['admin/' . $u] ?? 0;
        echo '<a href="' . $u . '" class="' . ($act == $u ? 'on' : '') . '">' . $i . ' ' . $l . ($n ? '<span class="nbadge">' . $n . '</span>' : '') . '</a>';
    }
    echo '<a href="../index.php">↗ ดูหน้าร้าน</a><a href="login.php?out=1">⎋ ออกจากระบบ</a></aside><div class="amain">';
    echo '<div class="row" style="justify-content:space-between;margin-bottom:14px"><h1 style="margin:0">' . e($t) . '</h1>' . bell('admin', null, '../') . '</div>';
    if (!empty($_SESSION['flash'])) {
        echo '<div class="alert i">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
function afoot()
{
    echo '</div></div></body></html>';
}
function af($a, $id, $l, $c = '', $x = '', $v = '')
{
    return '<form method="post" class="row" style="display:inline-flex">' . csf() . '<input type="hidden" name="a" value="' . $a . '"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="v" value="' . e($v) . '">' . $x . '<button class="btn sm ' . $c . '">' . $l . '</button></form>';
}
