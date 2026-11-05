<?php require 'config.php';require 'sms.php';
if(isset($_GET['out'])){unset($_SESSION['uid'],$_SESSION['otp_phone'],$_SESSION['demo_otp']);header('Location: index.php');exit;}
$next=$_POST['next']??($_GET['next']??'index.php');if(!preg_match('/^[a-z\-]+\.php(\?[\w=&%.\-]*)?$/',$next))$next='index.php';
if(user()){header('Location: '.$next);exit;}
$err='';$a=$_POST['a']??'';
if($_SERVER['REQUEST_METHOD']=='POST'){chk();
 if($a=='back'){unset($_SESSION['otp_phone'],$_SESSION['demo_otp']);}
 if($a=='send'){$ph=norm_phone($_POST['phone']??'');
  if(!$ph)$err='กรุณากรอกเบอร์มือถือไทย 10 หลัก เช่น 0812345678';
  elseif(q('SELECT COUNT(*) FROM otp_codes WHERE phone=? AND created_at>NOW()-INTERVAL 60 SECOND',[$ph])->fetchColumn())$err='กรุณารอ 60 วินาทีก่อนขอรหัสใหม่';
  elseif(q('SELECT COUNT(*) FROM otp_codes WHERE phone=? AND created_at>NOW()-INTERVAL 1 HOUR',[$ph])->fetchColumn()>=5)$err='ขอรหัสเกินจำนวนที่กำหนด กรุณาลองใหม่ภายหลัง';
  else{$code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$r=send_sms($ph,'รหัส OTP ของคุณคือ '.$code.' (ใช้ได้ 5 นาที)');
   if($r===true){q('INSERT INTO otp_codes(phone,code_hash,expires_at) VALUES(?,?,NOW()+INTERVAL 5 MINUTE)',[$ph,otp_hash($ph,$code)]);$_SESSION['otp_phone']=$ph;}else $err=$r;}}
 if($a=='verify'&&!empty($_SESSION['otp_phone'])){$ph=$_SESSION['otp_phone'];$code=preg_replace('/\D/','',$_POST['code']??'');
  $r=q('SELECT * FROM otp_codes WHERE phone=? AND used=0 AND expires_at>NOW() ORDER BY id DESC LIMIT 1',[$ph])->fetch();
  if(!$r)$err='รหัสหมดอายุ กรุณาขอรหัสใหม่';
  elseif($r['attempts']>=5)$err='กรอกผิดเกินกำหนด กรุณาขอรหัสใหม่';
  elseif(hash_equals($r['code_hash'],otp_hash($ph,$code))){q('UPDATE otp_codes SET used=1 WHERE id=?',[$r['id']]);
   $u=q('SELECT id FROM users WHERE phone=?',[$ph])->fetch();$uid=$u?$u['id']:(q('INSERT INTO users(phone) VALUES(?)',[$ph])?$pdo->lastInsertId():0);
   session_regenerate_id(true);$_SESSION['uid']=$uid;unset($_SESSION['otp_phone'],$_SESSION['demo_otp']);header('Location: '.$next);exit;}
  else{q('UPDATE otp_codes SET attempts=attempts+1 WHERE id=?',[$r['id']]);$err='รหัส OTP ไม่ถูกต้อง';}}}
$ph=$_SESSION['otp_phone']??null;
head('เข้าสู่ระบบ','login');?>
<form method="post" class="card" style="max-width:400px;margin:30px auto"><?=csf()?><input type="hidden" name="next" value="<?=e($next)?>">
<h2 style="margin-top:0">🔐 เข้าสู่ระบบด้วยเบอร์มือถือ</h2><?php if($err)echo '<div class="alert">'.e($err).'</div>'?>
<?php if(!$ph):?><input type="hidden" name="a" value="send"><label>เบอร์มือถือ</label><input name="phone" inputmode="tel" placeholder="0812345678" autofocus required>
<p class="mu">เราจะส่งรหัส OTP 6 หลักไปยังเบอร์นี้ ผู้ใช้ใหม่จะสร้างบัญชีให้อัตโนมัติ</p><button class="btn full">ขอรหัส OTP</button>
<?php else:?><input type="hidden" name="a" value="verify"><p>ส่งรหัสไปที่ <b><?=e($ph)?></b> แล้ว</p>
<?php if(!empty($_SESSION['demo_otp']))echo '<div class="note">🧪 โหมดทดสอบ (localhost)<br>'.e($_SESSION['demo_otp']).'</div>'?>
<label>รหัส OTP 6 หลัก</label><input name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus required style="font-size:22px;letter-spacing:6px;text-align:center">
<button class="btn full mt">ยืนยัน</button>
<p class="c mu mt"><button class="btn sm line" name="a" value="back" formnovalidate>เปลี่ยนเบอร์ / ขอรหัสใหม่</button></p><?php endif?></form>
<?php foot();
