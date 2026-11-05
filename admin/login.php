<?php require __DIR__.'/../config.php';
if(isset($_GET['out'])){unset($_SESSION['admin']);header('Location: login.php');exit;}
$has=q('SELECT COUNT(*) FROM admins')->fetchColumn();$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){chk();$u=trim($_POST['u']??'');$p=$_POST['p']??'';
 if(!$has){if($u&&strlen($p)>=8){q('INSERT INTO admins(username,pw) VALUES(?,?)',[$u,password_hash($p,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['admin']=$pdo->lastInsertId();header('Location: index.php');exit;}$err='ตั้งชื่อผู้ใช้ และรหัสผ่านอย่างน้อย 8 ตัว';}
 else{$r=q('SELECT * FROM admins WHERE username=?',[$u])->fetch();if($r&&password_verify($p,$r['pw'])){session_regenerate_id(true);$_SESSION['admin']=$r['id'];header('Location: index.php');exit;}$err='ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';}}?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin</title><link rel="stylesheet" href="../assets/style.css"></head><body style="display:grid;place-items:center;min-height:100vh">
<form method="post" class="card" style="width:340px"><h2>🌾 <?=$has?'เข้าสู่ระบบแอดมิน':'ตั้งค่าแอดมินคนแรก'?></h2><?php if($err)echo '<div class="alert">'.e($err).'</div>'?><?=csf()?>
<label>ชื่อผู้ใช้</label><input name="u" required><label>รหัสผ่าน</label><input name="p" type="password" required><button class="btn full mt"><?=$has?'เข้าสู่ระบบ':'สร้างบัญชี'?></button></form></body></html>
