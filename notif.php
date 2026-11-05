<?php require 'config.php';
$t = ($_GET['t'] ?? '') == 'admin' ? 'admin' : 'user';
if ($t == 'admin') {
    if (empty($_SESSION['admin'])) {
        header('Location: admin/login.php');
        exit;
    }
    $w = "target='admin'";
    $home = 'admin/index.php';
} else {
    $u = user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    $w = "target='user' AND user_id=" . (int)$u['id'];
    $home = 'index.php';
}
if (isset($_GET['all'])) {
    q("UPDATE notifications SET is_read=1 WHERE $w");
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    header('Location: ' . ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['SERVER_NAME'] ?? '') ? $ref : $home));
    exit;
}
if (isset($_GET['go'])) {
    $r = q("SELECT * FROM notifications WHERE id=? AND $w", [(int)$_GET['go']])->fetch();
    if ($r) {
        q('UPDATE notifications SET is_read=1 WHERE id=?', [$r['id']]);
        if (preg_match('~^(admin/)?[a-z\-]+\.php(\?[\w=&%.\-]*)?$~', $r['link'])) {
            header('Location: ' . $r['link']);
            exit;
        }
    }
}
header('Location: ' . $home);
