<?php
// ===== แก้ 3 บรรทัดนี้ (XAMPP: root และรหัสผ่านว่าง / cPanel: ใช้ค่าที่สร้างไว้) =====
const DB_NAME = 'rice_shop';
const DB_USER = 'root';
const DB_PASS = '';
// ===== ตั้งค่า OTP / SMS =====
const SMS_PROVIDER = 'demo';            // 'demo' = แสดงรหัสบนหน้าจอ (ใช้ได้เฉพาะ localhost) | 'thaibulksms' = ส่ง SMS จริง
const SMS_KEY = '';
const SMS_SECRET = '';
const SMS_SENDER = '';   // จาก ThaiBulkSMS (ชื่อผู้ส่งต้องลงทะเบียนก่อน)
const OTP_SECRET = 'เปลี่ยนเป็นข้อความสุ่มยาวๆ-อย่าให้ใครรู้';
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $x) {
    die('เชื่อมต่อฐานข้อมูลไม่ได้ กรุณาตรวจสอบ config.php');
}
const ST = ['pending' => ['รอตรวจสอบสินค้า', '#b7791f'], 'confirmed' => ['รอชำระเงิน', '#2b6cb0'], 'paid' => ['รอตรวจสอบสลิป', '#805ad5'], 'verified' => ['รอจัดส่ง', '#dd6b20'], 'shipped' => ['จัดส่งแล้ว', '#2f855a'], 'rejected' => ['สินค้าไม่เพียงพอ', '#c53030']];
function q($s, $a = [])
{
    global $pdo;
    $t = $pdo->prepare($s);
    $t->execute($a);
    return $t;
}
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function baht($n)
{
    return '฿' . number_format($n);
}
function csrf()
{
    return $_SESSION['c'] ??= bin2hex(random_bytes(16));
}
function csf()
{
    return '<input type="hidden" name="c" value="' . csrf() . '">';
}
function chk()
{
    if (!hash_equals($_SESSION['c'] ?? '', $_POST['c'] ?? '')) die('Bad request');
}
function setting($k, $d = '')
{
    static $c = null;
    if ($c === null) $c = q('SELECT k,v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $c[$k] ?? $d;
}
function user()
{
    static $u = false;
    if ($u === false) $u = !empty($_SESSION['uid']) ? (q('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null) : null;
    return $u;
}
function norm_phone($s)
{
    $d = preg_replace('/\D/', '', (string)$s);
    if (strpos($d, '66') === 0 && strlen($d) == 11) $d = '0' . substr($d, 2);
    return preg_match('/^0[689]\d{8}$/', $d) ? $d : null;
}
function otp_hash($p, $c)
{
    return hash_hmac('sha256', $p . '|' . $c, OTP_SECRET);
}
function setv($k, $v)
{
    q('INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=?', [$k, $v, $v]);
}
function badge($s)
{
    [$t, $c] = ST[$s];
    return '<span class="tag" style="color:' . $c . ';background:' . $c . '22">' . $t . '</span>';
}
function pimg($p, $pre = '')
{
    return $p['image'] ? '<img src="' . $pre . 'uploads/products/' . e($p['image']) . '" alt="' . e($p['name']) . '">' : '<div class="noimg">🌾</div>';
}
function upload($f, $dir, $max = 3145728)
{
    if (!$f || $f['error'] !== 0 || $f['size'] > $max) return null;
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
    if (!$ext) return null;
    $d = __DIR__ . '/uploads/' . $dir;
    if (!is_dir($d)) @mkdir($d, 0755, true);
    $n = bin2hex(random_bytes(10)) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], "$d/$n") ? $n : null;
}
function total($id)
{
    return (int)q('SELECT COALESCE(SUM(price*qty),0) FROM order_items WHERE order_id=?', [$id])->fetchColumn();
}
function items_html($id)
{
    $h = '<table class="tb">';
    foreach (q('SELECT * FROM order_items WHERE order_id=?', [$id])->fetchAll() as $i) $h .= '<tr><td>' . e($i['name']) . '</td><td>× ' . $i['qty'] . '</td><td class="r">' . baht($i['price'] * $i['qty']) . '</td></tr>';
    return $h . '<tr class="tot"><td colspan="2">รวมทั้งสิ้น</td><td class="r">' . baht(total($id)) . '</td></tr></table>';
}
function pcard($p)
{
    $h = '<div class="card pc"><a href="product.php?id=' . $p['id'] . '">' . pimg($p) . '<b>' . e($p['name']) . '</b></a><div class="mu">' . e($p['size']) . '</div><div class="price">' . baht($p['price']) . '</div>';
    if ($p['stock'] > 0) $h .= '<form method="post" action="cart.php">' . csf() . '<input type="hidden" name="a" value="add"><input type="hidden" name="id" value="' . $p['id'] . '"><input type="hidden" name="ret" value="' . e(basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')) . '"><button class="btn sm full">หยิบใส่ตะกร้า</button></form>';
    else $h .= '<button class="btn sm full" disabled>สินค้าหมด</button>';
    return $h . '</div>';
}
function head($t, $act = '')
{

    $n = array_sum($_SESSION['cart'] ?? []);
    $nav = ['home' => ['index.php', 'หน้าแรก'], 'products' => ['products.php', 'สินค้า'], 'orders' => ['my-orders.php', 'คำสั่งซื้อของฉัน'], 'contact' => ['#contact', 'ติดต่อเรา']];
    echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($t) . ' - ' . e(setting('shop_name', 'RiceShop')) . '</title><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><link rel="stylesheet" href="assets/style.css"></head><body><header class="top"><div class="wrap"><a class="logo" href="index.php">' . (setting('logo') ? '<img src="uploads/logo/' . e(setting('logo')) . '" alt=""> ' : '🌾 ') . e(setting('shop_name', 'RiceShop')) . '</a><nav>';
    foreach ($nav as $k => [$u, $l]) echo '<a href="' . $u . '" class="' . ($act == $k ? 'on' : '') . '">' . $l . '</a>';
    $u = user();
    echo $u ? bell('user', $u['id']) . '<span class="mu">👤 ' . e($u['phone']) . '</span><a href="login.php?out=1">ออกจากระบบ</a>' : '<a href="login.php">เข้าสู่ระบบ</a>';
    echo '<a href="cart.php" class="' . ($act == 'cart' ? 'on' : '') . '">🛒 ตะกร้า (' . $n . ')</a></nav></div></header><main class="wrap">';
}
function foot()
{
    echo '</main><footer id="contact"><div class="wrap">🌾 ' . e(setting('shop_name', 'RiceShop')) . ' · โทร ' . e(setting('phone')) . ' · LINE ' . e(setting('line')) . '</div></footer></body></html>';
}

function notify($t, $uid, $msg, $link = '')
{
    if (q('SELECT COUNT(*) FROM notifications WHERE target=? AND user_id<=>? AND message=? AND is_read=0', [$t, $uid, $msg])->fetchColumn()) return;
    q('INSERT INTO notifications(target,user_id,message,link) VALUES(?,?,?,?)', [$t, $uid, $msg, $link]);
}
function unotify($oid, $msg)
{
    $o = q('SELECT order_no,user_id,token FROM orders WHERE id=?', [$oid])->fetch();
    if ($o && $o['user_id']) notify('user', $o['user_id'], 'ออเดอร์ ' . $o['order_no'] . ' ' . $msg, 'order.php?t=' . $o['token']);
}
function bell($t, $uid = null, $pre = '')
{
    $w = $t == 'admin' ? "target='admin'" : "target='user' AND user_id=" . (int)$uid;
    $n = (int)q("SELECT COUNT(*) FROM notifications WHERE $w AND is_read=0")->fetchColumn();
    $h = '<div class="bell"><button type="button" class="bellb" aria-label="การแจ้งเตือน" onclick="this.parentNode.classList.toggle(\'open\')">🔔'
        . ($n ? '<span class="bdg">' . ($n > 99 ? '99+' : $n) . '</span>' : '') . '</button><div class="belld"><div class="belh"><b>การแจ้งเตือนใหม่</b>'
        . ($n ? '<a href="' . $pre . 'notif.php?t=' . $t . '&amp;all=1">อ่านทั้งหมด</a>' : '') . '</div>';
    $rows = q("SELECT * FROM notifications WHERE $w AND is_read=0 ORDER BY id DESC LIMIT 10")->fetchAll();
    if (!$rows) $h .= '<div class="beln mu">ไม่มีการแจ้งเตือนใหม่</div>';
    foreach ($rows as $r) $h .= '<a class="beln" href="' . $pre . 'notif.php?t=' . $t . '&amp;go=' . $r['id'] . '">' . e($r['message']) . '<br><span class="mu">' . date('d/m H:i', strtotime($r['created_at'])) . '</span></a>';
    return $h . '</div></div><script>document.addEventListener("click",function(e){if(!e.target.closest(".bell"))document.querySelectorAll(".bell.open").forEach(function(b){b.classList.remove("open")})})</script>';
}
