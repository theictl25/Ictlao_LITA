<?php require 'config.php';
$cat = (int)($_GET['cat'] ?? 0);
$pr = $_GET['pr'] ?? '';
$so = $_GET['so'] ?? 'new';
$w = ['1=1'];
$a = [];
if ($cat) {
    $w[] = 'category_id=?';
    $a[] = $cat;
}
if ($pr == '1') $w[] = 'price<200';
if ($pr == '2') $w[] = 'price BETWEEN 200 AND 300';
if ($pr == '3') $w[] = 'price>300';
$ord = ['new' => 'id DESC', 'asc' => 'price ASC', 'desc' => 'price DESC'][$so] ?? 'id DESC';
$ps = q('SELECT * FROM products WHERE ' . implode(' AND ', $w) . ' ORDER BY ' . $ord, $a)->fetchAll();
head('สินค้า', 'products'); ?>
<h2 class="sec">สินค้าทั้งหมด <span class="mu">(<?= count($ps) ?> รายการ)</span></h2>
<div class="cols">
    <form class="card" method="get"><b>หมวดหมู่สินค้า</b>
        <label class="rad"><input type="radio" name="cat" value="0" <?= $cat ? '' : 'checked' ?>> ทั้งหมด</label>
        <?php foreach (q('SELECT * FROM categories')->fetchAll() as $c): ?><label class="rad"><input type="radio" name="cat" value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'checked' : '' ?>> <?= e($c['icon'] . ' ' . $c['name']) ?></label><?php endforeach ?>
        <b style="display:block;margin-top:14px">ช่วงราคา</b>
        <?php foreach (['' => 'ทั้งหมด', '1' => 'ต่ำกว่า 200', '2' => '200 – 300', '3' => '300 ขึ้นไป'] as $k => $l): ?><label class="rad"><input type="radio" name="pr" value="<?= $k ?>" <?= $pr == (string)$k ? 'checked' : '' ?>> <?= $l ?></label><?php endforeach ?>
        <label>เรียงตาม</label><select name="so">
            <option value="new">ใหม่ล่าสุด</option>
            <option value="asc" <?= $so == 'asc' ? 'selected' : '' ?>>ราคาต่ำ → สูง</option>
            <option value="desc" <?= $so == 'desc' ? 'selected' : '' ?>>ราคาสูง → ต่ำ</option>
        </select>
        <button class="btn full mt">ค้นหา</button>
    </form>
    <div><?php if (!$ps) echo '<p class="mu">ไม่พบสินค้า</p>'; ?><div class="grid"><?php foreach ($ps as $p) echo pcard($p) ?></div>
    </div>
</div>
<?php foot();
