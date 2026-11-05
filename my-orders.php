<?php require 'config.php';
if(!user()){header('Location: login.php?next=my-orders.php');exit;}
head('คำสั่งซื้อของฉัน','orders');echo '<h2 class="sec">ประวัติสั่งซื้อของฉัน</h2>';
$rows=q('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[user()['id']])->fetchAll();
if(!$rows)echo '<div class="card c mu">ยังไม่มีคำสั่งซื้อ</div>';
foreach($rows as $o)echo '<a class="card mb row" style="justify-content:space-between;text-decoration:none" href="order.php?t='.$o['token'].'"><span><b>'.e($o['order_no']).'</b><br><span class="mu">'.e($o['created_at']).'</span></span>'.badge($o['status']).'<b>'.baht(total($o['id'])).' ›</b></a>';
foot();
