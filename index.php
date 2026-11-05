<?php require 'config.php';
head('หน้าแรก', 'home');
$cats = q('SELECT * FROM categories ORDER BY id')->fetchAll();
$best = q('SELECT p.* FROM products p ORDER BY (SELECT COALESCE(SUM(qty),0) FROM order_items WHERE product_id=p.id) DESC,id DESC LIMIT 6')->fetchAll(); ?>
<?php $bn = q('SELECT * FROM banners WHERE active=1 ORDER BY sort_order,id')->fetchAll();
if ($bn): ?>
    <section class="slider" id="sl">
        <div class="track"><?php foreach ($bn as $b): $im = '<img src="uploads/banners/' . e($b['image']) . '" alt="' . e($b['title']) . '">'; ?><div class="slide"><?= $b['link'] ? '<a href="' . e($b['link']) . '">' . $im . '</a>' : $im ?><?php if ($b['title'] || $b['subtitle']): ?><div class="cap"><?= $b['title'] ? '<h2>' . e($b['title']) . '</h2>' : '' ?><?= $b['subtitle'] ? '<p>' . e($b['subtitle']) . '</p>' : '' ?></div><?php endif ?></div><?php endforeach ?></div>
        <?php if (count($bn) > 1): ?><button class="nv pv" aria-label="ก่อนหน้า">‹</button><button class="nv nx" aria-label="ถัดไป">›</button>
            <div class="dots"></div><?php endif ?>
    </section>
    <script>
        (() => {
            const s = document.getElementById('sl'),
                t = s.querySelector('.track'),
                n = t.children.length,
                d = s.querySelector('.dots');
            if (n < 2) return;
            let i = 0,
                tm, x0 = null;
            const go = k => {
                i = (k + n) % n;
                t.style.transform = 'translateX(-' + i * 100 + '%)';
                [...d.children].forEach((b, j) => b.className = j == i ? 'on' : '')
            };
            for (let k = 0; k < n; k++) {
                const b = document.createElement('button');
                b.setAttribute('aria-label', 'สไลด์ ' + (k + 1));
                b.onclick = () => {
                    go(k);
                    play()
                };
                d.appendChild(b)
            }
            const play = () => {
                clearInterval(tm);
                tm = setInterval(() => go(i + 1), 5000)
            };
            s.querySelector('.pv').onclick = () => {
                go(i - 1);
                play()
            };
            s.querySelector('.nx').onclick = () => {
                go(i + 1);
                play()
            };
            s.onmouseenter = () => clearInterval(tm);
            s.onmouseleave = play;
            s.addEventListener('touchstart', e => {
                x0 = e.touches[0].clientX
            }, {
                passive: true
            });
            s.addEventListener('touchend', e => {
                if (x0 === null) return;
                const dx = e.changedTouches[0].clientX - x0;
                if (Math.abs(dx) > 40) {
                    go(i + (dx < 0 ? 1 : -1));
                    play()
                }
                x0 = null
            });
            go(0);
            play()
        })()
    </script>
<?php else: ?>
    <section class="hero">
        <div class="bg">🌾</div>
        <h1>Premium Thai Rice</h1>
        <p>Good Rice · Good Life</p><a class="btn" href="products.php">เลือกซื้อสินค้า →</a>
    </section>
<?php endif ?>
<div class="cats"><?php foreach ($cats as $c): ?><a href="products.php?cat=<?= $c['id'] ?>"><span><?= e($c['icon']) ?></span><?= e($c['name']) ?></a><?php endforeach ?></div>
<h2 class="sec">สินค้าขายดี <a href="products.php">ดูทั้งหมด →</a></h2>
<div class="grid"><?php foreach ($best as $p) echo pcard($p) ?></div>
<div class="feat">
    <div>✅ <b>คุณภาพพรีเมียม</b><br><span class="mu">คัดสรร 100% ธรรมชาติ</span></div>
    <div>🚚 <b>จัดส่งทั่วไทย</b><br><span class="mu">รวดเร็ว ปลอดภัย</span></div>
    <div>🔒 <b>ชำระเงินปลอดภัย</b><br><span class="mu">โอน/สแกน QR</span></div>
</div>
<?php foot();
