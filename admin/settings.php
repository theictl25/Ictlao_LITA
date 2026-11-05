<?php require __DIR__ . '/_layout.php';
function okurl($u)
{
    $u = trim($u);
    return ($u === '' || preg_match('~^(https?://|/|[a-z0-9\-]+\.php)~i', $u)) ? $u : '';
}
function rmfile($d, $f)
{
    if ($f) @unlink(__DIR__ . '/../uploads/' . $d . '/' . basename($f));
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    chk();
    $a = $_POST['a'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $m = [];
    $to = 'settings.php';
    if ($a == 'pay') {
        setv('bank', trim($_POST['bank']));
        if ($f = upload($_FILES['qr'] ?? null, 'qr')) setv('qr', $f);
        $m[] = 'บันทึกการชำระเงินแล้ว';
    }
    if ($a == 'gen') {
        setv('shop_name', trim($_POST['shop_name']) ?: 'RiceShop');
        setv('phone', trim($_POST['phone']));
        setv('line', trim($_POST['line']));
        $old = setting('logo');
        if (isset($_POST['rmlogo']) && $old) {
            rmfile('logo', $old);
            setv('logo', '');
            $m[] = 'ลบโลโก้แล้ว';
        } elseif (($_FILES['logo']['error'] ?? 4) !== 4) {
            if ($f = upload($_FILES['logo'], 'logo', 2097152)) {
                rmfile('logo', $old);
                setv('logo', $f);
                $m[] = 'เปลี่ยนโลโก้แล้ว';
            } else $m[] = 'อัปโหลดโลโก้ไม่สำเร็จ (รับ JPG/PNG/WEBP ไม่เกิน 2MB)';
        }
        $pw = $_POST['pw'] ?? '';
        if ($pw !== '') {
            if (strlen($pw) >= 8) {
                q('UPDATE admins SET pw=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $_SESSION['admin']]);
                $m[] = 'เปลี่ยนรหัสผ่านแล้ว';
            } else $m[] = 'รหัสผ่านต้องอย่างน้อย 8 ตัว';
        }
        array_unshift($m, 'บันทึกการตั้งค่าแล้ว');
    }
    if ($a == 'bsave') {
        $to .= '#banners';
        $d = [trim($_POST['title']), trim($_POST['subtitle']), okurl($_POST['link']), (int)$_POST['sort'], isset($_POST['active']) ? 1 : 0];
        $img = upload($_FILES['img'] ?? null, 'banners', 5242880);
        if ($id) {
            q('UPDATE banners SET title=?,subtitle=?,link=?,sort_order=?,active=? WHERE id=?', [...$d, $id]);
            if ($img) {
                rmfile('banners', q('SELECT image FROM banners WHERE id=?', [$id])->fetchColumn());
                q('UPDATE banners SET image=? WHERE id=?', [$img, $id]);
            }
            $m[] = 'บันทึกแบนเนอร์แล้ว';
        } elseif ($img) {
            q('INSERT INTO banners(title,subtitle,link,sort_order,active,image) VALUES(?,?,?,?,?,?)', [...$d, $img]);
            $m[] = 'เพิ่มแบนเนอร์แล้ว';
        } else $m[] = 'กรุณาเลือกรูป JPG/PNG/WEBP ขนาดไม่เกิน 5MB';
    }
    if ($a == 'bdel') {
        $to .= '#banners';
        rmfile('banners', q('SELECT image FROM banners WHERE id=?', [$id])->fetchColumn());
        q('DELETE FROM banners WHERE id=?', [$id]);
        $m[] = 'ลบแบนเนอร์แล้ว';
    }
    if ($a == 'btog') {
        $to .= '#banners';
        q('UPDATE banners SET active=1-active WHERE id=?', [$id]);
    }
    $_SESSION['flash'] = implode(' · ', $m);
    header('Location: ' . $to);
    exit;
}
ahead('Settings', 'settings.php');
$qr = setting('qr');
$lg = setting('logo');
$ed = null;
if (isset($_GET['bnew'])) $ed = ['id' => 0, 'title' => '', 'subtitle' => '', 'link' => '', 'sort_order' => 0, 'active' => 1, 'image' => ''];
if (isset($_GET['bedit'])) $ed = q('SELECT * FROM banners WHERE id=?', [(int)$_GET['bedit']])->fetch() ?: null; ?>
<div class="cols2">
    <form method="post" enctype="multipart/form-data" class="card" id="pay"><?= csf() ?><input type="hidden" name="a" value="pay"><b>🏦 การชำระเงิน</b>
        <label>รูป QR ชำระเงิน (JPG/PNG)</label><input type="file" name="qr" accept="image/*"><?php if ($qr) echo '<img class="qr" src="../uploads/qr/' . e($qr) . '">' ?>
        <label>ข้อมูลบัญชีธนาคาร (แสดงเมื่อลูกค้าเลือกโอน)</label><textarea name="bank" rows="3"><?= e(setting('bank')) ?></textarea><button class="btn mt">บันทึก</button>
    </form>
    <form method="post" enctype="multipart/form-data" class="card" id="gen"><?= csf() ?><input type="hidden" name="a" value="gen"><b>⚙️ ข้อมูลร้านและบัญชี</b>
        <label>ชื่อร้าน</label><input name="shop_name" value="<?= e(setting('shop_name')) ?>">
        <label>โลโก้ร้าน (JPG/PNG/WEBP ไม่เกิน 2MB · แนะนำพื้นหลังโปร่งใส สูงประมาณ 80–120 px)</label>
        <?php if ($lg): ?><div class="note" style="text-align:left"><img src="../uploads/logo/<?= e($lg) ?>" alt="logo" style="height:56px;max-width:100%;object-fit:contain;vertical-align:middle"> <label class="rad" style="display:inline-block;margin-left:12px"><input type="checkbox" name="rmlogo"> ลบโลโก้ (กลับไปใช้ไอคอน 🌾)</label></div><?php endif ?>
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
        <label>เบอร์โทร</label><input name="phone" value="<?= e(setting('phone')) ?>"><label>LINE ID</label><input name="line" value="<?= e(setting('line')) ?>">
        <label>เปลี่ยนรหัสผ่านแอดมิน (เว้นว่างถ้าไม่เปลี่ยน)</label><input type="password" name="pw" autocomplete="new-password"><button class="btn mt">บันทึก</button>
    </form>
</div>

<div class="card mt" id="banners">
    <div class="row" style="justify-content:space-between"><b>🖼️ แบนเนอร์สไลด์หน้าแรก</b><button type="button" class="btn sm" onclick="bform()">+ เพิ่มแบนเนอร์</button></div>
    <p class="mu">แนะนำรูปแนวนอนสัดส่วน 16:6 เช่น 1600×600 px (JPG/PNG/WEBP ไม่เกิน 5MB) · แสดงเรียงตามลำดับเลขน้อยไปมาก · ถ้าไม่มีแบนเนอร์ หน้าแรกจะแสดง Hero แบบเดิม</p>
    <table class="tb">
        <tr>
            <th>รูป</th>
            <th>ข้อความ</th>
            <th>ลำดับ</th>
            <th>สถานะ</th>
            <th></th>
        </tr>
        <?php
        $rows = q('SELECT * FROM banners ORDER BY sort_order,id')->fetchAll();
        if (!$rows) echo '<tr><td colspan="5" class="mu">ยังไม่มีแบนเนอร์</td></tr>';
        foreach ($rows as $b)
            echo '<tr><td><img src="../uploads/banners/' . e($b['image']) . '" style="width:120px;height:auto;border-radius:8px"></td><td><b>' . e($b['title']) . '</b><br><span class="mu">' . e($b['subtitle']) . '</span></td><td>' . $b['sort_order'] . '</td><td>' . ($b['active'] ? '<span style="color:var(--ok)">แสดง</span>' : '<span class="mu">ซ่อน</span>') . '</td><td class="r" style="white-space:nowrap"><button type="button" class="btn sm line" onclick="bform(' . e(json_encode($b)) . ')">แก้ไข</button> ' . af('btog', $b['id'], $b['active'] ? 'ซ่อน' : 'แสดง', 'line') . ' ' . af('bdel', $b['id'], 'ลบ', 'bad') . '</td></tr>';
        ?>
    </table>
</div>

<dialog id="bd" class="dlg">
    <form method="post" enctype="multipart/form-data">
        <?= csf() ?><input type="hidden" name="a" value="bsave"><input type="hidden" name="id" id="b_id" value="0">
        <div class="dh"><b id="b_t">เพิ่มแบนเนอร์</b><button type="button" class="dx" onclick="this.closest('dialog').close()">×</button></div>
        <div id="b_cur" class="mb"></div>
        <label id="b_cl">รูปแบนเนอร์</label><input type="file" name="img" id="b_file" accept="image/*">
        <div class="cols2">
            <div><label>หัวข้อ (ไม่บังคับ)</label><input name="title" id="b_title"></div>
            <div><label>ข้อความรอง (ไม่บังคับ)</label><input name="subtitle" id="b_sub"></div>
            <div><label>ลิงก์เมื่อกดที่รูป (เช่น products.php?cat=1)</label><input name="link" id="b_link"></div>
            <div><label>ลำดับ</label><input type="number" name="sort" id="b_sort" value="0"></div>
        </div>
        <label class="rad"><input type="checkbox" name="active" id="b_act" checked> แสดงบนหน้าแรก</label>
        <div class="row mt"><button class="btn">บันทึก</button><button type="button" class="btn line" onclick="this.closest('dialog').close()">ยกเลิก</button></div>
    </form>
</dialog>
<script>
    const dlgB = document.getElementById('bd');
    dlgB.addEventListener('click', e => {
        if (e.target === dlgB) dlgB.close();
    });

    function bform(d) {
        d = d || {};
        const g = i => document.getElementById(i);
        g('b_t').textContent = d.id ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์';
        g('b_id').value = d.id || 0;
        g('b_title').value = d.title || '';
        g('b_sub').value = d.subtitle || '';
        g('b_link').value = d.link || '';
        g('b_sort').value = d.sort_order || 0;
        g('b_act').checked = d.id ? +d.active === 1 : true;
        g('b_file').value = '';
        g('b_file').required = !d.id;
        g('b_cl').textContent = d.id ? 'เปลี่ยนรูป (เว้นว่างถ้าใช้รูปเดิม)' : 'รูปแบนเนอร์';
        g('b_cur').innerHTML = d.image ? '<img src="../uploads/banners/' + d.image + '" style="max-width:100%;max-height:140px;border-radius:8px">' : '';
        dlgB.showModal();
    }
</script>
<?php afoot();
