<?php require __DIR__.'/_layout.php';
function okurl($u){$u=trim($u);return ($u===''||preg_match('~^(https?://|/|[a-z0-9\-]+\.php)~i',$u))?$u:'';}
if($_SERVER['REQUEST_METHOD']=='POST'){chk();$a=$_POST['a']??'';$id=(int)($_POST['id']??0);
 if($a=='save'){$d=[trim($_POST['title']),trim($_POST['subtitle']),okurl($_POST['link']),(int)$_POST['sort'],isset($_POST['active'])?1:0];$img=upload($_FILES['img']??null,'banners',5242880);
  if($id){q('UPDATE banners SET title=?,subtitle=?,link=?,sort_order=?,active=? WHERE id=?',[...$d,$id]);
   if($img){$old=q('SELECT image FROM banners WHERE id=?',[$id])->fetchColumn();q('UPDATE banners SET image=? WHERE id=?',[$img,$id]);if($old)@unlink(__DIR__.'/../uploads/banners/'.basename($old));}
   $_SESSION['flash']='บันทึกแล้ว';}
  elseif($img){q('INSERT INTO banners(title,subtitle,link,sort_order,active,image) VALUES(?,?,?,?,?,?)',[...$d,$img]);$_SESSION['flash']='เพิ่มแบนเนอร์แล้ว';}
  else $_SESSION['flash']='กรุณาเลือกรูป JPG/PNG/WEBP ขนาดไม่เกิน 5MB';}
 if($a=='del'){$old=q('SELECT image FROM banners WHERE id=?',[$id])->fetchColumn();q('DELETE FROM banners WHERE id=?',[$id]);if($old)@unlink(__DIR__.'/../uploads/banners/'.basename($old));$_SESSION['flash']='ลบแล้ว';}
 if($a=='tog')q('UPDATE banners SET active=1-active WHERE id=?',[$id]);
 header('Location: banners.php');exit;}
ahead('Banners (สไลด์หน้าแรก)','banners.php');
$ed=null;if(isset($_GET['new']))$ed=['id'=>0,'title'=>'','subtitle'=>'','link'=>'','sort_order'=>0,'active'=>1,'image'=>''];
if(isset($_GET['edit']))$ed=q('SELECT * FROM banners WHERE id=?',[(int)$_GET['edit']])->fetch()?:null;
echo '<p><a class="btn sm" href="?new=1">+ เพิ่มแบนเนอร์</a> <span class="mu">แนะนำรูปแนวนอนสัดส่วน 16:6 เช่น 1600×600 px (ไม่เกิน 5MB) · แสดงเรียงตามลำดับเลขน้อยไปมาก</span></p>';
if($ed){echo '<form method="post" enctype="multipart/form-data" class="card mb">'.csf().'<input type="hidden" name="a" value="save"><input type="hidden" name="id" value="'.$ed['id'].'">';
 if($ed['image'])echo '<img src="../uploads/banners/'.e($ed['image']).'" style="max-width:320px;border-radius:10px">';
 echo '<label>'.($ed['id']?'เปลี่ยนรูป (เว้นว่างถ้าใช้รูปเดิม)':'รูปแบนเนอร์').'</label><input type="file" name="img" accept="image/*" '.($ed['id']?'':'required').'>
 <div class="cols2"><div><label>หัวข้อ (ไม่บังคับ)</label><input name="title" value="'.e($ed['title']).'"></div><div><label>ข้อความรอง (ไม่บังคับ)</label><input name="subtitle" value="'.e($ed['subtitle']).'"></div>
 <div><label>ลิงก์เมื่อกดที่รูป (เช่น products.php?cat=1)</label><input name="link" value="'.e($ed['link']).'"></div><div><label>ลำดับ</label><input type="number" name="sort" value="'.(int)$ed['sort_order'].'"></div></div>
 <label class="rad"><input type="checkbox" name="active" '.($ed['active']?'checked':'').'> แสดงบนหน้าแรก</label><div class="row mt"><button class="btn">บันทึก</button><a class="btn line" href="banners.php">ยกเลิก</a></div></form>';}
echo '<div class="card"><table class="tb"><tr><th>รูป</th><th>ข้อความ</th><th>ลำดับ</th><th>สถานะ</th><th></th></tr>';
$rows=q('SELECT * FROM banners ORDER BY sort_order,id')->fetchAll();if(!$rows)echo '<tr><td colspan="5" class="mu">ยังไม่มีแบนเนอร์ หน้าแรกจะแสดง Hero แบบเดิมจนกว่าจะเพิ่มรูป</td></tr>';
foreach($rows as $b)echo '<tr><td><img src="../uploads/banners/'.e($b['image']).'" style="width:120px;height:auto;border-radius:8px"></td><td><b>'.e($b['title']).'</b><br><span class="mu">'.e($b['subtitle']).'</span></td><td>'.$b['sort_order'].'</td><td>'.($b['active']?'<span style="color:var(--ok)">แสดง</span>':'<span class="mu">ซ่อน</span>').'</td><td class="r" style="white-space:nowrap"><a class="btn sm line" href="?edit='.$b['id'].'">แก้ไข</a> '.af('tog',$b['id'],$b['active']?'ซ่อน':'แสดง','line').' '.af('del',$b['id'],'ลบ','bad').'</td></tr>';
echo '</table></div>';afoot();
