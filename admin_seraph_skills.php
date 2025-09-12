<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$msg = ''; $err = '';

/* ---------- helpers ---------- */
function norm_style($s) {
  $s = trim((string)$s);
  $allow = ['ฟัน','ยิง','กระแทก',''];
  return in_array($s,$allow,true) ? $s : '';
}
function norm_elements($str) {
  // รับได้ 0-2 ค่า: ไร้ธาตุ, ไฟ, สายฟ้า, น้ำแข็ง, มืด, แสง
  $allow = ['ไร้ธาตุ','ไฟ','สายฟ้า','น้ำแข็ง','มืด','แสง'];
  $items = array_filter(array_map('trim', explode(',', (string)$str)));
  $picked = [];
  foreach ($items as $e) {
    if (in_array($e,$allow,true) && !in_array($e,$picked,true)) {
      $picked[] = $e;
      if (count($picked) >= 2) break;
    }
  }
  return implode(',', $picked);
}

/* ---------- actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['csrf'] ?? '') === $_SESSION['csrf'])) {

  $action    = $_POST['action'] ?? '';
  $seraph_id = (int)($_POST['seraph_id'] ?? 0);

  if ($action === 'create_skill' || $action === 'update_skill') {
    $tab  = $_POST['tab'] ?? 'skill';
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $ord  = (int)($_POST['order_no'] ?? 0);
    $style_tag   = norm_style($_POST['style_tag'] ?? '');
    $element_tag = norm_elements($_POST['element_tag'] ?? '');
    $allowedTabs = ['skill','passive','resonance','limit_break'];
    if (!in_array($tab,$allowedTabs,true)) $tab = 'skill';
  }

  if ($action === 'create_skill') {
    if ($seraph_id > 0 && $name !== '') {
      $st = $pdo->prepare("
        INSERT INTO seraph_skills(seraph_id,tab,style_tag,element_tag,name,description,order_no)
        VALUES (?,?,?,?,?,?,?)
      ");
      $st->execute([$seraph_id,$tab,$style_tag,$element_tag,$name,$desc,$ord]);
      $msg = 'เพิ่มสกิลเรียบร้อย';
    } else $err = 'กรอกข้อมูลไม่ครบ';
  }
  elseif ($action === 'update_skill') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id>0 && $seraph_id>0 && $name!=='') {
      $st = $pdo->prepare("
        UPDATE seraph_skills
        SET tab=?, style_tag=?, element_tag=?, name=?, description=?, order_no=?
        WHERE id=? AND seraph_id=?
      ");
      $st->execute([$tab,$style_tag,$element_tag,$name,$desc,$ord,$id,$seraph_id]);
      $msg = 'อัปเดตสกิลเรียบร้อย';
    } else $err = 'กรอกข้อมูลไม่ครบ';
  }
  elseif ($action === 'delete_skill') {
    $delId = (int)($_POST['del_id'] ?? 0);
    if ($delId>0) {
      $pdo->prepare("DELETE FROM seraph_skills WHERE id=? LIMIT 1")->execute([$delId]);
      $msg = 'ลบแล้ว';
    }
  }

  header('Location: admin_seraph_skills.php?seraph_id='.$seraph_id.'&msg='.urlencode($msg).'&err='.urlencode($err));
  exit;
}

/* ---------- load lists ---------- */
$seraph_id = isset($_GET['seraph_id']) ? (int)$_GET['seraph_id'] : 0;
$msg = $_GET['msg'] ?? ''; $err = $_GET['err'] ?? '';
$seraphs = $pdo->query("SELECT id,name_th,rarity FROM seraphs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$skills = [];
if ($seraph_id > 0) {
  $st = $pdo->prepare("
    SELECT id,tab,style_tag,element_tag,name,description,order_no
    FROM seraph_skills
    WHERE seraph_id=?
    ORDER BY 
      CASE tab
        WHEN 'skill' THEN 1
        WHEN 'passive' THEN 2
        WHEN 'resonance' THEN 3
        WHEN 'limit_break' THEN 4
        ELSE 5
      END, order_no, id
  ");
  $st->execute([$seraph_id]);
  $skills = $st->fetchAll(PDO::FETCH_ASSOC);
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId && $seraph_id>0) {
  $st = $pdo->prepare("SELECT * FROM seraph_skills WHERE id=? AND seraph_id=?");
  $st->execute([$editId,$seraph_id]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function tabLabel($tab){
  return ['skill'=>'Skill','passive'=>'Passive','resonance'=>'Resonance','limit_break'=>'Limited Break'][$tab] ?? $tab;
}
function styleIcon($s){
  return $s==='ฟัน'?'icon/icon1.png':($s==='ยิง'?'icon/icon2.png':($s==='กระแทก'?'icon/icon3.png':''));
}
function elemIcon($e){
  return [
    'ไร้ธาตุ'=>'icon/em0.png','ไฟ'=>'icon/em1.png','สายฟ้า'=>'icon/em2.png',
    'น้ำแข็ง'=>'icon/em3.png','มืด'=>'icon/em4.png','แสง'=>'icon/em5.png'
  ][$e] ?? '';
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Seraph Skills</title>
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin_theme.css">
  <style>
    .icon-img{width:20px;height:20px;object-fit:contain;vertical-align:-4px}
    .hint{opacity:.7;font-size:.9em;margin-top:4px}
    .tag-wrap{display:flex;gap:6px;flex-wrap:wrap}
  </style>
</head>
<body>
  <aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item active" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
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
    <h1>จัดการสกิล</h1>
    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="grid">
      <form method="get">
        <h2>เลือกตัวละคร</h2>
        <label>Seraph</label>
        <select name="seraph_id" required>
          <option value="">— เลือก —</option>
          <?php foreach ($seraphs as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $seraph_id === (int)$s['id'] ? 'selected' : '' ?>>
              #<?= (int)$s['id'] ?> — <?= htmlspecialchars($s['name_th']) ?> (<?= htmlspecialchars($s['rarity']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">โหลด</button>
      </form>

      <form method="post">
        <h2><?= $editRow ? 'แก้ไขสกิล #'.$editId : 'เพิ่มสกิลให้ตัวที่เลือก' ?></h2>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
        <input type="hidden" name="seraph_id" value="<?= (int)$seraph_id ?>">
        <?php if ($editRow): ?>
          <input type="hidden" name="action" value="update_skill">
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create_skill">
        <?php endif; ?>

        <label>หมวด</label>
        <select name="tab" required>
          <?php
            $tabs = ['skill'=>'Skill','passive'=>'Passive','resonance'=>'Resonance','limit_break'=>'Limited Break'];
            $cur = $editRow['tab'] ?? 'skill';
            foreach($tabs as $v=>$lab){ $sel=$cur===$v?'selected':''; echo "<option value=\"$v\" $sel>$lab</option>"; }
          ?>
        </select>

        <label>ชื่อสกิล</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editRow['name'] ?? '') ?>">

        <label>รายละเอียด</label>
        <textarea name="desc" rows="4"><?= htmlspecialchars($editRow['description'] ?? '') ?></textarea>

        <label>สาย (ออปชัน)</label>
        <select name="style_tag">
          <?php
            $styles = [''=>'— ไม่ระบุ —','ฟัน'=>'ฟัน','ยิง'=>'ยิง','กระแทก'=>'กระแทก'];
            $cur = $editRow['style_tag'] ?? '';
            foreach($styles as $v=>$lab){ $sel=$cur===$v?'selected':''; echo "<option value=\"$v\" $sel>$lab</option>"; }
          ?>
        </select>

        <label>ธาตุ (สูงสุด 2; คั่นด้วย ,)</label>
        <input type="text" name="element_tag" placeholder="เช่น ไฟ หรือ ไฟ,สายฟ้า"
               value="<?= htmlspecialchars($editRow['element_tag'] ?? '') ?>">
        <div class="hint">ตัวเลือกที่รองรับ: ไร้ธาตุ, ไฟ, สายฟ้า, น้ำแข็ง, มืด, แสง</div>

        <label>ลำดับ</label>
        <input type="number" name="order_no" value="<?= (int)($editRow['order_no'] ?? 0) ?>" min="0">

        <div style="display:flex;gap:8px;margin-top:10px">
          <button class="btn" type="submit"><?= $editRow ? 'อัปเดต' : 'บันทึก' ?></button>
          <?php if ($editRow): ?>
            <a class="btn" style="background:#eef2f7;border:1px solid #d7dbe2"
               href="admin_seraph_skills.php?seraph_id=<?= (int)$seraph_id ?>">ยกเลิก</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <h2 style="margin-top:18px">รายการสกิล <?= $seraph_id ? '#'.$seraph_id : '' ?></h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>หมวด</th>
          <th>สาย/ธาตุ</th>
          <th>ชื่อ</th>
          <th>รายละเอียด</th>
          <th>ลำดับ</th>
          <th>จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($skills as $k): ?>
          <tr>
            <td><?= (int)$k['id'] ?></td>
            <td><?= htmlspecialchars(tabLabel($k['tab'])) ?></td>
            <td>
              <?php if (!empty($k['style_tag'])): ?>
                <img class="icon-img" src="<?= styleIcon($k['style_tag']) ?>" title="<?= htmlspecialchars($k['style_tag']) ?>">
              <?php endif; ?>
              <?php
                if (!empty($k['element_tag'])) {
                  foreach (array_slice(array_filter(array_map('trim', explode(',',$k['element_tag']))),0,2) as $el) {
                    $ico = elemIcon($el);
                    if ($ico) echo '<img class="icon-img" src="'.$ico.'" title="'.htmlspecialchars($el).'"> ';
                  }
                }
              ?>
            </td>
            <td><?= htmlspecialchars($k['name']) ?></td>
            <td><?= nl2br(htmlspecialchars($k['description'])) ?></td>
            <td><?= (int)$k['order_no'] ?></td>
            <td>
              <a class="btn-edit" href="admin_seraph_skills.php?seraph_id=<?= (int)$seraph_id ?>&edit=<?= (int)$k['id'] ?>">แก้ไข</a>
              <form method="post" action="" onsubmit="return confirm('ลบสกิลนี้?')" style="display:inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="delete_skill">
                <input type="hidden" name="del_id" value="<?= (int)$k['id'] ?>">
                <input type="hidden" name="seraph_id" value="<?= (int)$seraph_id ?>">
                <button type="submit" class="btn-del">ลบ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$skills): ?>
          <tr><td colspan="7" style="opacity:.7">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</body>
</html>
