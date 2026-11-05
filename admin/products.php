<?php require __DIR__ . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  chk();
  $a = $_POST['a'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  if ($a == 'save') {
    $d = [(int)$_POST['cat'] ?: null, trim($_POST['name']), trim($_POST['size']), max(0, (int)$_POST['price']), max(0, (int)$_POST['stock']), trim($_POST['desc'])];
    if ($d[1]) {
      $img = upload($_FILES['img'] ?? null, 'products');
      if ($id) {
        q('UPDATE products SET category_id=?,name=?,size=?,price=?,stock=?,description=? WHERE id=?', [...$d, $id]);
        if ($img) q('UPDATE products SET image=? WHERE id=?', [$img, $id]);
      } else q('INSERT INTO products(category_id,name,size,price,stock,description,image) VALUES(?,?,?,?,?,?,?)', [...$d, $img]);
      $_SESSION['flash'] = 'บันทึกสินค้าแล้ว';
    }
  }
  if ($a == 'del') {
    q('DELETE FROM products WHERE id=?', [$id]);
    $_SESSION['flash'] = 'ลบสินค้าแล้ว';
  }
  header('Location: products.php');
  exit;
}
ahead('Products', 'products.php');
$cats = q('SELECT * FROM categories')->fetchAll();
echo '<p><button type="button" class="btn sm" onclick="pform()">+ เพิ่มสินค้า</button></p>';
echo '<div class="card"><table class="tb"><tr><th></th><th>สินค้า</th><th>ราคา</th><th>สต็อก</th><th></th></tr>';
foreach (q('SELECT * FROM products ORDER BY id DESC')->fetchAll() as $p)
  echo '<tr><td>' . ($p['image'] ? '<img src="../uploads/products/' . e($p['image']) . '">' : '🌾') . '</td><td>' . e($p['name']) . '<br><span class="mu">' . e($p['size']) . '</span></td><td>' . baht($p['price']) . '</td><td style="' . ($p['stock'] < 5 ? 'color:var(--bad);font-weight:700' : '') . '">' . $p['stock'] . '</td><td class="r" style="white-space:nowrap"><button type="button" class="btn sm line" onclick="pform(' . e(json_encode($p)) . ')">แก้ไข</button> ' . af('del', $p['id'], 'ลบ', 'bad') . '</td></tr>';
echo '</table></div>';
?>
<dialog id="pd" class="dlg">
  <form method="post" enctype="multipart/form-data">
    <?= csf() ?><input type="hidden" name="a" value="save"><input type="hidden" name="id" id="f_id" value="0">
    <div class="dh"><b id="f_t">เพิ่มสินค้า</b><button type="button" class="dx" onclick="this.closest('dialog').close()">×</button></div>
    <label>ชื่อสินค้า</label><input name="name" id="f_name" required>
    <div class="cols2">
      <div><label>ขนาดบรรจุ</label><input name="size" id="f_size"></div>
      <div><label>หมวดหมู่</label><select name="cat" id="f_cat">
          <option value="0">-</option><?php foreach ($cats as $c) echo '<option value="' . $c['id'] . '">' . e($c['name']) . '</option>'; ?>
        </select></div>
      <div><label>ราคา (บาท)</label><input type="number" min="0" name="price" id="f_price" required></div>
      <div><label>สต็อก</label><input type="number" min="0" name="stock" id="f_stock" required></div>
    </div>
    <label>รายละเอียด</label><textarea name="desc" id="f_desc" rows="3"></textarea>
    <label>รูปสินค้า (JPG/PNG/WEBP)</label>
    <div id="f_cur" class="mb"></div><input type="file" name="img" id="f_file" accept="image/*">
    <div class="row mt"><button class="btn">บันทึก</button><button type="button" class="btn line" onclick="this.closest('dialog').close()">ยกเลิก</button></div>
  </form>
</dialog>
<script>
  const dlgP = document.getElementById('pd');
  dlgP.addEventListener('click', e => {
    if (e.target === dlgP) dlgP.close();
  });

  function pform(d) {
    d = d || {};
    const g = i => document.getElementById(i);
    g('f_t').textContent = d.id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า';
    g('f_id').value = d.id || 0;
    g('f_name').value = d.name || '';
    g('f_size').value = d.size || '';
    g('f_cat').value = d.category_id || 0;
    g('f_price').value = d.price ?? '';
    g('f_stock').value = d.stock ?? '';
    g('f_desc').value = d.description || '';
    g('f_file').value = '';
    g('f_cur').innerHTML = d.image ? '<img src="../uploads/products/' + d.image + '" style="max-width:120px;border-radius:8px">' : '';
    dlgP.showModal();
  }
</script>
<?php afoot();
