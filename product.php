<?php require 'config.php';
$p = q('SELECT * FROM products WHERE id=?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$p) {
    http_response_code(404);
    die('ไม่พบสินค้า');
}
head($p['name'], 'products'); ?>
<div class="cols3 card">
    <div><?php if ($p['image']): ?><div class="pdimg" id="zoom" tabindex="0" role="button" title="คลิกเพื่อขยายรูป"><img src="uploads/products/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>"></div><?php else: ?><div class="pdimg">
                <div class="noimg">🌾</div>
            </div><?php endif ?></div>
    <div>
        <h2 style="margin:0"><?= e($p['name']) ?></h2>
        <div class="price" style="font-size:30px;margin:6px 0"><?= baht($p['price']) ?></div>
        <p><?= nl2br(e($p['description'])) ?></p>
        <p><b>ขนาดบรรจุ:</b> <span class="tag" style="background:var(--cr2)"><?= e($p['size']) ?></span> · <span class="mu"><?= $p['stock'] > 0 ? 'คงเหลือ ' . $p['stock'] : 'สินค้าหมด' ?></span></p>
        <?php if ($p['stock'] > 0): ?><form method="post" action="cart.php" class="row"><?= csf() ?><input type="hidden" name="a" value="add"><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="ret" value="cart.php">
                <input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] ?>" style="width:90px;flex:none"><button class="btn">🛒 เพิ่มลงตะกร้า</button>
            </form><?php endif ?>
        <div class="feat" style="grid-template-columns:repeat(3,1fr)">
            <div>🌿 สินค้าคุณภาพ</div>
            <div>🚚 จัดส่งทั่วไทย</div>
            <div>🔒 ปลอดภัย 100%</div>
        </div>
    </div>
</div>
<div class="lb" id="lb"><button class="lbx" aria-label="ปิด">×</button><img id="lbi" alt=""></div>
<script>
    (() => {
        const z = document.getElementById('zoom');
        if (!z) return;
        const lb = document.getElementById('lb'),
            li = document.getElementById('lbi');
        const open = () => {
            li.src = z.querySelector('img').src;
            lb.classList.add('on');
            document.body.style.overflow = 'hidden'
        };
        const close = () => {
            lb.classList.remove('on');
            document.body.style.overflow = ''
        };
        z.onclick = open;
        z.onkeydown = e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open()
            }
        };
        lb.onclick = close;
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') close()
        })
    })();
</script>
<?php foot();
