<?php require 'config.php';
$cart = $_SESSION['cart'] ?? [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  chk();
  $a = $_POST['a'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  $p = $id ? q('SELECT stock FROM products WHERE id=?', [$id])->fetch() : null;
  if ($a == 'add' && $p) $cart[$id] = min($p['stock'], ($cart[$id] ?? 0) + max(1, (int)($_POST['qty'] ?? 1)));
  if ($a == 'set' && $p) {
    $n = min($p['stock'], (int)$_POST['qty']);
    if ($n > 0) $cart[$id] = $n;
    else unset($cart[$id]);
  }
  if ($a == 'del') unset($cart[$id]);
  $_SESSION['cart'] = $cart;
  if ($a == 'place' && $cart) {
    if (!user()) {
      header('Location: login.php?next=cart.php');
      exit;
    }
    $ids = array_keys($cart);
    $ps = q('SELECT * FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids)->fetchAll();
    if ($ps) {
      $tok = bin2hex(random_bytes(12));
      $pdo->beginTransaction();
      $no = 'ORD-' . date('Ymd') . '-' . str_pad(q('SELECT COUNT(*)+1 FROM orders WHERE DATE(created_at)=CURDATE()')->fetchColumn(), 3, '0', STR_PAD_LEFT);
      q('INSERT INTO orders(token,order_no,user_id) VALUES(?,?,?)', [$tok, $no, user()['id']]);
      $oid = $pdo->lastInsertId();
      foreach ($ps as $p) q('INSERT INTO order_items(order_id,product_id,name,price,qty) VALUES(?,?,?,?,?)', [$oid, $p['id'], trim($p['name'] . ' ' . $p['size']), $p['price'], $cart[$p['id']]]);
      $pdo->commit();
      notify('admin', null, '🆕 ออเดอร์ใหม่ ' . $no . ' รอตรวจสอบสินค้า', 'admin/orders.php?v=new');
      $_SESSION['my'][] = $tok;
      $_SESSION['cart'] = [];
      header('Location: order.php?t=' . $tok);
      exit;
    }
  }
  $ret = $_POST['ret'] ?? 'cart.php';
  if (!preg_match('/^[a-z\-]+\.php(\?[\w=&%.\-]*)?$/', $ret)) $ret = 'cart.php';
  header('Location: ' . ($a == 'add' ? $ret : 'cart.php'));
  exit;
}
head('ตะกร้าสินค้า', 'cart');
echo '<h2 class="sec">ตะกร้าสินค้า</h2>';
if (!$cart) echo '<div class="card c"><p class="mu">ตะกร้าว่างเปล่า</p><a class="btn" href="products.php">เลือกสินค้า</a></div>';
else {
  $ids = array_keys($cart);
  $ps = q('SELECT * FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids)->fetchAll();
  $sum = 0;
  echo '<div class="card"><table class="tb"><tr><th>สินค้า</th><th>ราคา</th><th>จำนวน</th><th class="r">รวม</th><th></th></tr>';
  foreach ($ps as $p) {
    $l = $p['price'] * $cart[$p['id']];
    $sum += $l;
    echo '<tr><td><div class="row">' . pimg($p) . '<span><b>' . e($p['name']) . '</b><br><span class="mu">' . e($p['size']) . '</span></span></div></td><td>' . baht($p['price']) . '</td><td><form method="post">' . csf() . '<input type="hidden" name="a" value="set"><input type="hidden" name="id" value="' . $p['id'] . '"><input type="number" name="qty" value="' . $cart[$p['id']] . '" min="0" max="' . $p['stock'] . '" style="width:70px" onchange="this.form.submit()"></form></td><td class="r">' . baht($l) . '</td><td><form method="post">' . csf() . '<input type="hidden" name="a" value="del"><input type="hidden" name="id" value="' . $p['id'] . '"><button class="btn sm line" title="ลบ">🗑</button></form></td></tr>';
  }
  echo '</table><div class="r mt"><div class="mu">ค่าจัดส่ง: ฟรี</div><div class="big">รวมทั้งสิ้น ' . baht($sum) . '</div></div>
 <p class="note">หลังกดสั่งซื้อ ร้านจะตรวจสอบสต็อกก่อน แล้วคุณจึงเลือกวิธีชำระเงินได้</p>
 <form method="post">' . csf() . '<input type="hidden" name="a" value="place"><button class="btn full">' . (user() ? '🛒 ดำเนินการสั่งซื้อ' : '🔐 เข้าสู่ระบบเพื่อสั่งซื้อ') . '</button></form></div>';
}
foot();
