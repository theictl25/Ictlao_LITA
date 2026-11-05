<?php require __DIR__.'/_layout.php';ahead('Customers','customers.php');
echo '<div class="card"><table class="tb"><tr><th>ชื่อ</th><th>เบอร์โทร</th><th>ที่อยู่ล่าสุด</th><th>ออเดอร์</th><th class="r">ยอดซื้อ</th></tr>';
foreach(q("SELECT phone,SUBSTRING_INDEX(GROUP_CONCAT(cust_name ORDER BY id DESC SEPARATOR '|'),'|',1) n,SUBSTRING_INDEX(GROUP_CONCAT(REPLACE(addr,'|',' ') ORDER BY id DESC SEPARATOR '|'),'|',1) a,COUNT(*) c,SUM((SELECT SUM(price*qty) FROM order_items WHERE order_id=orders.id)) t FROM orders WHERE phone IS NOT NULL AND phone<>'' GROUP BY phone ORDER BY t DESC")->fetchAll() as $c)
 echo '<tr><td>'.e($c['n']).'</td><td>'.e($c['phone']).'</td><td class="mu">'.e($c['a']).'</td><td>'.$c['c'].'</td><td class="r">'.baht($c['t']).'</td></tr>';
echo '</table></div>';afoot();
