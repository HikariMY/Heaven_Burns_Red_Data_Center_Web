<?php
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

/* ---------- ค่าเริ่มต้น ---------- */
$it = [
  'name_th' => '',
  'name_jp' => '',
  'role' => 'ATTACKER',
  'rarity' => 'SS',
  'element' => '', // จะเก็บ "ไฟ,น้ำแข็ง"
  'style' => 'ฟัน',
  'image' => '',
  'tags' => '',
  'dp' => null, 'hp' => null, 'str_val' => null, 'dex' => null,
  'pdef' => null, 'mdef' => null, 'int_stat' => null, 'luck' => null,
  'tier_rank' => null,
  'obtain_type' => 'normal'
];

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId) {
  $st = $pdo->prepare("SELECT * FROM seraphs WHERE id=?");
  $st->execute([$editId]);
  $it = $st->fetch(PDO::FETCH_ASSOC) ?: $it;
}

$msg = '';
/* ---------- บันทึก ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name_th = trim($_POST['name_th'] ?? '');
  $name_jp = trim($_POST['name_jp'] ?? '');
  $role = $_POST['role'] ?? 'ATTACKER';
  $rarity = $_POST['rarity'] ?? 'SS';
  $style = $_POST['style'] ?? 'ฟัน';
  $tags = trim($_POST['tags'] ?? '');
  $tier_rank   = ($_POST['tier_rank']   === '' ? null : (float)($_POST['tier_rank'] ?? null));
  $obtain_type = $_POST['obtain_type'] ?? 'normal';
  if (!in_array($obtain_type, ['normal','limited','collab'], true)) $obtain_type = 'normal';

  // --- รับธาตุแบบหลายค่า (สูงสุด 2) ---
  $elementArr = $_POST['element'] ?? [];
  if (!is_array($elementArr)) $elementArr = [];
  // ลบซ้ำ/Trim และจำกัด 2 ค่า
  $elementArr = array_values(array_unique(array_map('trim', $elementArr)));
  if (count($elementArr) > 2) $elementArr = array_slice($elementArr, 0, 2);
  // ถ้าผสม "ไร้ธาตุ" กับธาตุอื่น ให้เลือกได้เฉพาะ "ไร้ธาตุ" อย่างเดียว
  if (in_array('ไร้ธาตุ', $elementArr, true) && count($elementArr) > 1) {
    $elementArr = ['ไร้ธาตุ'];
  }
  $element = implode(',', $elementArr);

  $dp = ($_POST['dp'] === '' ? null : (int)($_POST['dp'] ?? null));
  $hp = ($_POST['hp'] === '' ? null : (int)($_POST['hp'] ?? null));
  $str_val = ($_POST['str_val'] === '' ? null : (int)($_POST['str_val'] ?? null));
  $dex = ($_POST['dex'] === '' ? null : (int)($_POST['dex'] ?? null));
  $pdef = ($_POST['pdef'] === '' ? null : (int)($_POST['pdef'] ?? null));
  $mdef = ($_POST['mdef'] === '' ? null : (int)($_POST['mdef'] ?? null));
  $int_stat = ($_POST['int_stat'] === '' ? null : (int)($_POST['int_stat'] ?? null));
  $luck = ($_POST['luck'] === '' ? null : (int)($_POST['luck'] ?? null));

  // รูป
  $imgPath = $it['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    @mkdir(__DIR__ . '/uploads/seraphs', 0777, true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $fname = 'sp_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest = __DIR__ . '/uploads/seraphs/' . $fname;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) $imgPath = 'uploads/seraphs/' . $fname;
  }

  if ($editId) {
    $sql = "UPDATE seraphs
            SET name_th=?, name_jp=?, role=?, rarity=?, element=?, style=?, image=?, tags=?,
                dp=?, hp=?, str_val=?, dex=?, pdef=?, mdef=?, int_stat=?, luck=?, tier_rank=?, obtain_type=?
            WHERE id=?";
    $pdo->prepare($sql)->execute([
      $name_th,$name_jp,$role,$rarity,$element,$style,$imgPath,$tags,
      $dp,$hp,$str_val,$dex,$pdef,$mdef,$int_stat,$luck,$tier_rank,$obtain_type,
      $editId
    ]);
    $msg = 'อัปเดตเรียบร้อย';
  } else {
    $sql = "INSERT INTO seraphs
            (name_th,name_jp,role,rarity,element,style,image,tags,
             dp,hp,str_val,dex,pdef,mdef,int_stat,luck,tier_rank,obtain_type)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([
      $name_th,$name_jp,$role,$rarity,$element,$style,$imgPath,$tags,
      $dp,$hp,$str_val,$dex,$pdef,$mdef,$int_stat,$luck,$tier_rank,$obtain_type
    ]);
    $msg = 'เพิ่มแล้ว';
  }

  // refresh ค่าในฟอร์ม
  if ($editId) {
    $st = $pdo->prepare("SELECT * FROM seraphs WHERE id=?");
    $st->execute([$editId]);
    $it = $st->fetch(PDO::FETCH_ASSOC) ?: $it;
  } else {
    $it = [
      'name_th'=>'','name_jp'=>'','role'=>'ATTACKER','rarity'=>'SS','element'=>'','style'=>'ฟัน','image'=>'','tags'=>'',
      'dp'=>null,'hp'=>null,'str_val'=>null,'dex'=>null,'pdef'=>null,'mdef'=>null,'int_stat'=>null,'luck'=>null,'tier_rank'=>null,'obtain_type'=>'normal'
    ];
  }
}

/* ---------- รายการ ---------- */
$list = $pdo->query("SELECT id,name_th,role,rarity,element,style,image FROM seraphs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- helper icons ---------- */
function styleIcon($s){ return $s==='ฟัน'?'icon/icon1.png':($s==='ยิง'?'icon/icon2.png':'icon/icon3.png'); }
function elemIcon($e){
  return [
    'ไร้ธาตุ'=>'icon/em0.png','ไฟ'=>'icon/em1.png','สายฟ้า'=>'icon/em2.png',
    'น้ำแข็ง'=>'icon/em3.png','มืด'=>'icon/em4.png','แสง'=>'icon/em5.png'
  ][$e] ?? 'icon/em5.png';
}
function rarityIcon($r){ $m=['SSR'=>'icon/SSR.png','SS'=>'icon/SS.png','S'=>'icon/S.png','A'=>'icon/A.png']; return $m[$r]??'icon/S.png'; }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Seraphs</title>
<link rel="stylesheet" href="admin.css?v=7">
<link rel="stylesheet" href="admin_theme.css">
</head>
<body>
  <aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item active" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
      <a class="side-item" href="admin_events.php"><span class="ico">🗓️</span><span class="label">Events</span></a>
      <a class="side-item" href="admin_team_comps.php"><span class="ico">👥</span><span class="label">Team Comp</span></a>
      <a class="side-item" href="admin_accessories.php"><span class="ico">💍</span><span class="label">Accessories</span></a>
      <a class="side-item" href="admin_accessory_types.php"><span class="ico">🗂️</span><span class="label">Accessory Types</span></a>
      <a class="side-item" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="admin_news.php"><span class="ico">📰</span><span class="label">News</span></a>
      <a class="side-item" href="admin_guides.php"><span class="ico">✍️</span><span class="label">Guides</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>จัดการ Seraph</h1>
    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="grid">
      <!-- ฟอร์ม -->
      <form method="post" enctype="multipart/form-data">
        <h2><?= $editId ? 'แก้ไข #'.$editId : 'เพิ่มตัวละครใหม่' ?></h2>

        <label>ชื่อ (TH)</label><input type="text" name="name_th" required value="<?= htmlspecialchars($it['name_th']) ?>">
        <label>ชื่อ (JP)</label><input type="text" name="name_jp" value="<?= htmlspecialchars($it['name_jp']) ?>">

        <label>บทบาท</label>
        <select name="role">
          <?php foreach (['ATTACKER','BREAKER','DEBUFFER','BUFFER','BLASTER','HEALER','DEFENDER','ADMIRAL','RIDER'] as $v): ?>
            <option <?= $it['role']===$v?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>

        <label>เรต</label>
        <div class="pick-row">
          <?php foreach (['SSR','SS','S','A'] as $rar): ?>
            <label class="pick-opt">
              <input type="radio" name="rarity" value="<?= $rar ?>" <?= ($it['rarity']??'')===$rar?'checked':'' ?>>
              <span class="icon-wrap lg"><img class="rarity-chip" src="icon/<?= $rar ?>.png" alt="<?= $rar ?>"></span>
            </label>
          <?php endforeach; ?>
        </div>

        <!-- เปลี่ยนเป็น checkbox: เลือกได้สูงสุด 2 -->
        <label>ธาตุ (เลือกได้สูงสุด 2)</label>
        <div class="pick-row" id="elemPickRow">
          <?php
          $allElems = [
            ['ไร้ธาตุ','em0'],['ไฟ','em1'],['สายฟ้า','em2'],
            ['น้ำแข็ง','em3'],['มืด','em4'],['แสง','em5']
          ];
          $selElems = array_filter(array_map('trim', explode(',', $it['element'] ?? '')));
          foreach ($allElems as [$el,$fn]): ?>
            <label class="pick-opt">
              <input type="checkbox" name="element[]" value="<?= $el ?>" <?= in_array($el,$selElems,true)?'checked':'' ?>>
              <span class="icon-wrap lg"><img class="icon-img" src="icon/<?= $fn ?>.png" alt=""></span><span><?= $el ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <small style="opacity:.7;display:block;margin:-6px 0 10px">
          * ถ้าเลือก “ไร้ธาตุ” จะเลือกธาตุอื่นร่วมไม่ได้
        </small>

        <label>สาย</label>
        <div class="pick-row">
          <?php foreach ([['ฟัน','icon1'],['ยิง','icon2'],['กระแทก','icon3']] as [$st,$fn]): ?>
            <label class="pick-opt">
              <input type="radio" name="style" value="<?= $st ?>" <?= ($it['style']??'')===$st?'checked':'' ?>>
              <span class="icon-wrap lg"><img class="icon-img" src="icon/<?= $fn ?>.png" alt=""></span><span><?= $st ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <label>แท็ก (คั่นด้วย ,)</label><input type="text" name="tags" value="<?= htmlspecialchars($it['tags']) ?>">

        <label>รูปภาพ</label><input type="file" name="image" accept="image/*">
        <?php if (!empty($it['image'])): ?><div style="margin-top:8px"><img class="thumb" src="<?= htmlspecialchars($it['image']) ?>"></div><?php endif; ?>

        <h3 style="margin-top:12px">ค่าสเตตัส</h3>
        <div class="stat-grid">
          <label>Dp</label><input type="number" name="dp" value="<?= htmlspecialchars($it['dp']??'') ?>" min="0" step="1">
          <label>Hp</label><input type="number" name="hp" value="<?= htmlspecialchars($it['hp']??'') ?>" min="0" step="1">
          <label>Str</label><input type="number" name="str_val" value="<?= htmlspecialchars($it['str_val']??'') ?>" min="0" step="1">
          <label>Dex</label><input type="number" name="dex" value="<?= htmlspecialchars($it['dex']??'') ?>" min="0" step="1">
          <label>Pdef</label><input type="number" name="pdef" value="<?= htmlspecialchars($it['pdef']??'') ?>" min="0" step="1">
          <label>Mdef</label><input type="number" name="mdef" value="<?= htmlspecialchars($it['mdef']??'') ?>" min="0" step="1">
          <label>Int</label><input type="number" name="int_stat" value="<?= htmlspecialchars($it['int_stat']??'') ?>" min="0" step="1">
          <label>Luck</label><input type="number" name="luck" value="<?= htmlspecialchars($it['luck']??'') ?>" min="0" step="1">
        </div>

        <label>Tier</label>
        <input type="text" name="tier_rank" value="<?= htmlspecialchars($it['tier_rank'] ?? '') ?>" placeholder="เช่น 0, 1, 1.5, 2 …">
        <small style="opacity:.7;display:block;margin:-6px 0 10px">ตัวเลขน้อย = แรงสุด (Tier0 → Tier1 → …)</small>

        <label>ประเภทการได้มา</label>
        <div class="pick-row">
          <?php foreach([['normal','ถาวร'],['limited','ลิมิต (กรอบทอง)'],['collab','คอลแลป (กรอบไล่สี)']] as [$val,$txt]): ?>
            <label class="pick-opt"><input type="radio" name="obtain_type" value="<?= $val ?>" <?= ($it['obtain_type']??'normal')===$val?'checked':'' ?>><span><?= $txt ?></span></label>
          <?php endforeach; ?>
        </div>

        <button class="btn" type="submit"><?= $editId?'บันทึก':'เพิ่ม' ?></button>
      </form>

      <!-- ตาราง -->
      <div>
        <h2>รายการ</h2>
        <table>
          <thead><tr><th>#</th><th>รูป</th><th>ชื่อ</th><th>บทบาท</th><th>เรต</th><th>ธาตุ</th><th>สาย</th><th>จัดการ</th></tr></thead>
          <tbody>
            <?php foreach ($list as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?php if ($r['image']): ?><img class="thumb" src="<?= htmlspecialchars($r['image']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($r['name_th']) ?></td>
                <td><?= htmlspecialchars($r['role']) ?></td>
                <td><span class="icon-wrap"><img class="rarity-chip" src="<?= rarityIcon($r['rarity']) ?>" alt=""></span></td>
                <td>
                  <?php foreach (array_filter(array_map('trim', explode(',', $r['element'] ?? ''))) as $el): ?>
                    <span class="icon-wrap"><img class="icon-img" src="<?= elemIcon($el) ?>" alt=""></span> <?= htmlspecialchars($el) ?>&nbsp;
                  <?php endforeach; ?>
                </td>
                <td><span class="icon-wrap"><img class="icon-img" src="<?= styleIcon($r['style']) ?>" alt=""></span> <?= htmlspecialchars($r['style']) ?></td>
                <td>
                  <a href="?edit=<?= (int)$r['id'] ?>" class="btn-edit">แก้ไข</a>
                  <a href="admin_seraph_skills.php?seraph_id=<?= (int)$r['id'] ?>" class="btn-edit">สกิล</a>
                  <form method="post" action="admin_seraphs_delete.php" style="display:inline" onsubmit="return confirm('ลบตัวละครนี้?');">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
                    <button type="submit" class="btn-del">ลบ</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    // จำกัดเลือกธาตุได้สูงสุด 2 และกันกรณี "ไร้ธาตุ"
    (function(){
      const row = document.getElementById('elemPickRow'); if(!row) return;
      function enforce(){
        const checks = [...row.querySelectorAll('input[type="checkbox"][name="element[]"]')];
        const picked = checks.filter(c=>c.checked).map(c=>c.value);
        const noneOnly = picked.includes('ไร้ธาตุ');
        checks.forEach(c=>{
          if(noneOnly){
            if(c.value!=='ไร้ธาตุ') c.checked = false, c.disabled = true;
          }else{
            c.disabled = false;
          }
        });
        const count = checks.filter(c=>c.checked).length;
        checks.forEach(c=>{
          if(!c.checked && !noneOnly){
            c.disabled = count >= 2;
          }
        });
      }
      row.addEventListener('change', enforce);
      enforce();
    })();
  </script>
</body>
</html>
