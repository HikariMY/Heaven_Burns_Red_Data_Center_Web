<?php
// admin_team_comps.php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

/* ---------- CREATE / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id      = (int)($_POST['id'] ?? 0);
  $title   = trim($_POST['title'] ?? '');
  $element = $_POST['element'] ?? '';
  $notes   = trim($_POST['notes'] ?? '');
  $cover   = null;

  // upload cover (optional)
  if (!empty($_FILES['cover_image']['name']) && is_uploaded_file($_FILES['cover_image']['tmp_name'])) {
    $dir = 'uploads/team/'; if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
    $fname = 'team_'.date('Ymd_His').'_'.mt_rand(1000,9999).'.'.$ext;
    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dir.$fname)) {
      $cover = $dir.$fname;
    }
  }

  try {
    $pdo->beginTransaction();

    if ($id) {
      // update header
      if ($cover) {
        $pdo->prepare("UPDATE team_comps SET title=?, element=?, notes=?, cover_image=? WHERE id=?")
            ->execute([$title,$element,$notes,$cover,$id]);
      } else {
        $pdo->prepare("UPDATE team_comps SET title=?, element=?, notes=? WHERE id=?")
            ->execute([$title,$element,$notes,$id]);
      }
      // replace members
      $pdo->prepare("DELETE FROM team_comp_members WHERE comp_id=?")->execute([$id]);
      $comp_id = $id;
    } else {
      // create header
      $pdo->prepare("INSERT INTO team_comps(title,element,cover_image,notes) VALUES (?,?,?,?)")
          ->execute([$title,$element,$cover,$notes]);
      $comp_id = (int)$pdo->lastInsertId();
    }

    // insert members 1..6 (Free Slot = 0 -> seraph_id NULL | ข้าม insert ก็ได้)
    $insMem = $pdo->prepare("INSERT INTO team_comp_members(comp_id,slot_no,seraph_id) VALUES (?,?,?)");
    for ($i=1; $i<=6; $i++) {
      $sid = isset($_POST['slot'.$i]) ? (int)$_POST['slot'.$i] : 0; // 0 = Free Slot
      if ($sid > 0) {
        $insMem->execute([$comp_id, $i, $sid]);
      } else {
        // เก็บเป็นแถวว่าง (seraph_id=NULL) หรือจะข้าม insert ก็ได้
        $insMem->execute([$comp_id, $i, null]);
      }
    }

    $pdo->commit();
    header('Location: admin_team_comps.php?ok=1');
    exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    $err = "บันทึกล้มเหลว: ".$e->getMessage();
  }
}

/* ---------- LOAD FOR EDIT FORM ---------- */
$edit = (int)($_GET['edit'] ?? 0);
$editRow = null; $editMembers = array_fill(1, 6, 0); // default เป็น Free Slot
if ($edit) {
  $s = $pdo->prepare("SELECT * FROM team_comps WHERE id=?");
  $s->execute([$edit]);
  $editRow = $s->fetch(PDO::FETCH_ASSOC);

  if ($editRow) {
    $m = $pdo->prepare("SELECT slot_no, seraph_id FROM team_comp_members WHERE comp_id=?");
    $m->execute([$edit]);
    foreach ($m as $r) {
      $slot = (int)$r['slot_no'];
      $editMembers[$slot] = $r['seraph_id'] ? (int)$r['seraph_id'] : 0; // NULL -> 0 (Free)
    }
  }
}

/* ---------- DATA FOR FORM/LIST ---------- */
$ser   = $pdo->query("SELECT id,name_th FROM seraphs ORDER BY rarity DESC, name_th ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$teams = $pdo->query("SELECT id,title,element,created_at FROM team_comps ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin – Team Comps</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<style>
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .msg{background:#e8fff0;border:1px solid #a7e0b8;padding:8px 12px;border-radius:8px;margin:10px 0}
  .msg.err{background:#fff2f2;border-color:#f2b8b5}
  .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:#fff;cursor:pointer}
  .btn-edit{color:#0b68e3}
  .btn-del{color:#d0302e}
  table{width:100%;border-collapse:collapse;margin-top:14px}
  th,td{border-bottom:1px solid #eee;padding:8px}
</style>
</head>
<body class="sb-hover">
<aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
      <a class="side-item" href="admin_events.php"><span class="ico">🗓️</span><span class="label">Events</span></a>
      <a class="side-item" href="admin_team_comps.php"><span class="ico">👥</span><span class="label">Team Comp</span></a>
      <a class="side-item" href="admin_accessories.php"><span class="ico">💍</span><span class="label">Accessories</span></a>
      <a class="side-item" href="admin_accessory_types.php"><span class="ico">🗂️</span><span class="label">Accessory Types</span></a>
      <a class="side-item active" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="admin_news.php"><span class="ico">📰</span><span class="label">News</span></a>
      <a class="side-item" href="admin_guides.php"><span class="ico">✍️</span><span class="label">Guides</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>Team Comps</h1>
    <?php if(!empty($_GET['ok'])): ?><div class="msg">บันทึกสำเร็จ</div><?php endif; ?>
    <?php if(!empty($err)): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?? '' ?>">

      <div class="grid">
        <div>
          <label>ชื่อทีม</label>
          <input name="title" required value="<?= htmlspecialchars($editRow['title'] ?? '') ?>">

          <label>ธาตุ</label>
          <select name="element" required>
            <?php $opts=['ไฟ','สายฟ้า','น้ำแข็ง','แสง','มืด'];
            $cur = $editRow['element'] ?? '';
            foreach($opts as $op){ $sel = ($cur===$op)?'selected':''; echo "<option $sel value=\"$op\">$op</option>"; } ?>
          </select>

          <label>รูปปก (ออปชัน)</label>
          <input type="file" name="cover_image">

          <label>หมายเหตุ/โน้ต</label>
          <textarea name="notes"><?= htmlspecialchars($editRow['notes'] ?? '') ?></textarea>
        </div>

        <div>
          <label>สมาชิกทีม (1–6)</label>
          <?php for($i=1; $i<=6; $i++): $val = $editMembers[$i] ?? 0; ?>
            <div style="margin:6px 0;display:flex;gap:8px;align-items:center">
              <strong style="width:28px">#<?= $i ?></strong>
              <select name="slot<?= $i ?>">
                <option value="0" <?= $val==0?'selected':'' ?>>— Free Slot —</option>
                <?php foreach($ser as $sid=>$nm): ?>
                  <option value="<?= $sid ?>" <?= $sid==$val?'selected':'' ?>><?= htmlspecialchars($nm) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endfor; ?>
          <button class="btn" type="submit">บันทึกทีม</button>
        </div>
      </div>
    </form>

    <h2 style="margin-top:20px">รายการทีม</h2>
    <table>
      <tr><th>ID</th><th>ชื่อทีม</th><th>ธาตุ</th><th>วันที่</th><th>จัดการ</th></tr>
      <?php foreach($teams as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['title']) ?></td>
          <td><?= $t['element'] ?></td>
          <td><?= $t['created_at'] ?></td>
          <td>
            <a class="btn btn-edit" href="admin_team_comp_details.php?comp_id=<?= $t['id'] ?>">รายละเอียดทีม</a>
            <a class="btn btn-edit" href="admin_team_comps.php?edit=<?= $t['id'] ?>">แก้ไข</a>
            <a class="btn btn-del" href="admin_team_comps_delete.php?id=<?= $t['id'] ?>" onclick="return confirm('ลบทีมนี้?')">ลบ</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </main>
</body>
</html>
