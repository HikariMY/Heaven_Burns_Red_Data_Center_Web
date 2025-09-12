<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

/* ===== helpers ===== */
function status_of($row) {
  $t = new DateTime('today');
  $s = empty($row['start_date']) ? null : new DateTime($row['start_date']);
  $e = empty($row['end_date'])   ? null : new DateTime($row['end_date']);
  if ($s && $t < $s) return 'upcoming';
  if ($s && (!$e || $t <= $e)) return 'ongoing';
  return 'past';
}
function save_image($file) {
  if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $dir = __DIR__ . '/uploads/events/';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $name = 'ev_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
  if (move_uploaded_file($file['tmp_name'], $dir.$name)) {
    return 'uploads/events/' . $name;
  }
  return null;
}

/* ===== CRUD: create/update ===== */
$msg = '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = ['title'=>'','description'=>'','start_date'=>'','end_date'=>'','image'=>''];

if ($edit_id) {
  $s = $pdo->prepare("SELECT * FROM events WHERE id=?");
  $s->execute([$edit_id]);
  $edit = $s->fetch(PDO::FETCH_ASSOC) ?: $edit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id    = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $start = $_POST['start_date'] ?: null;
  $end   = $_POST['end_date'] ?: null;

  $img = null;
  if (!empty($_FILES['image']['name'])) {
    $img = save_image($_FILES['image']);
  }

  if ($id) {
    if ($img) {
      $st = $pdo->prepare("UPDATE events SET title=?, description=?, start_date=?, end_date=?, image=? WHERE id=?");
      $st->execute([$title,$desc,$start,$end,$img,$id]);
    } else {
      $st = $pdo->prepare("UPDATE events SET title=?, description=?, start_date=?, end_date=? WHERE id=?");
      $st->execute([$title,$desc,$start,$end,$id]);
    }
    $msg = 'อัปเดตเรียบร้อย';
    header("Location: admin_news.php?edit=$id&ok=1"); exit;
  } else {
    $st = $pdo->prepare("INSERT INTO events (title,description,start_date,end_date,image) VALUES (?,?,?,?,?)");
    $st->execute([$title,$desc,$start,$end,$img]);
    $msg = 'เพิ่มข่าว/อัปเดตเรียบร้อย';
    header("Location: admin_news.php?ok=1"); exit;
  }
}

/* ===== filters/list ===== */
$q    = trim($_GET['q'] ?? '');
$seg  = $_GET['seg'] ?? ''; // '', upcoming, ongoing, past

// ดึงทั้งหมดก่อนแล้วค่อยคำนวณสถานะเพื่อรองรับฟิลเตอร์ seg
$rows = $pdo->query("SELECT id,title,description,image,start_date,end_date,created_at FROM events ORDER BY id DESC")
            ->fetchAll(PDO::FETCH_ASSOC);

// ติดป้ายสถานะ + ค้นหา + กรอง
$today = new DateTime('today');
$rows = array_values(array_filter(array_map(function($r) use($today){
  $r['_status'] = status_of($r);
  return $r;
}, $rows), function($r) use($q,$seg){
  $pass = true;
  if ($q !== '') {
    $hay = mb_strtolower(($r['title'] ?? '').' '.($r['description'] ?? ''), 'UTF-8');
    $pass = $pass && (mb_strpos($hay, mb_strtolower($q,'UTF-8')) !== false);
  }
  if ($seg !== '' && in_array($seg,['upcoming','ongoing','past'], true)) {
    $pass = $pass && ($r['_status'] === $seg);
  }
  return $pass;
}));

?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — ข่าว/อัปเดตแพทช์</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<style>
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .msg{background:#e8fff0;border:1px solid #a7e0b8;padding:8px 12px;border-radius:8px;margin:10px 0}
  .msg.err{background:#fff2f2;border-color:#f2b8b5}
  .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:#fff;cursor:pointer}
  .btn-edit{color:#0b68e3}
  .btn-del{color:#d0302e}
  .thumb{width:80px;height:48px;object-fit:cover;border:1px solid #eee;border-radius:8px}
  .status{display:inline-block;padding:2px 8px;border-radius:999px;font-weight:900;font-size:.8rem}
  .status.upcoming{background:#dbeafe;color:#1e3a8a}
  .status.ongoing{background:#fee2e2;color:#991b1b}
  .status.past{background:#f3f4f6;color:#111827}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}
  th,td{border-bottom:1px solid #f1f5f9;padding:10px;text-align:left}
  th{background:#ffe4ea}
  input[type=text],input[type=date],textarea,select{width:100%;padding:8px;border:1.5px solid #e5e7eb;border-radius:10px}
</style>
</head>
<body class="sb-hover">
  <aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
      <a class="side-item active" href="admin_news.php"><span class="ico">📰</span><span class="label">News/Events</span></a>
      <a class="side-item" href="admin_events.php"><span class="ico">🗓️</span><span class="label">Slides (เดิม)</span></a>
      <a class="side-item" href="admin_team_comps.php"><span class="ico">👥</span><span class="label">Team Comp</span></a>
      <a class="side-item" href="admin_accessories.php"><span class="ico">💍</span><span class="label">Accessories</span></a>
      <a class="side-item" href="admin_accessory_types.php"><span class="ico">🗂️</span><span class="label">Accessory Types</span></a>
      <a class="side-item" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="admin_guides.php"><span class="ico">✍️</span><span class="label">Guides</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>ข่าว/อัปเดตแพทช์</h1>
    <?php if (!empty($_GET['ok'])): ?><div class="msg">บันทึกสำเร็จ</div><?php endif; ?>

    <div class="grid">
      <!-- form -->
      <form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #eee;border-radius:12px;padding:16px">
        <input type="hidden" name="id" value="<?= $edit_id ?: '' ?>">
        <h2><?= $edit_id ? 'แก้ไข #' . (int)$edit_id : 'เพิ่มข่าว/อัปเดตใหม่' ?></h2>

        <label>หัวข้อ*</label>
        <input type="text" name="title" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>">

        <label>รายละเอียด</label>
        <textarea name="description" rows="6"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>

        <div style="display:flex;gap:12px">
          <div style="flex:1">
            <label>วันเริ่ม</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($edit['start_date'] ?? '') ?>">
          </div>
          <div style="flex:1">
            <label>วันจบ</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($edit['end_date'] ?? '') ?>">
          </div>
        </div>

        <label>รูปภาพ (ปกข่าว)</label>
        <input type="file" name="image" accept="image/*">
        <?php if(!empty($edit['image'])): ?>
          <div style="margin-top:8px"><img class="thumb" src="<?= htmlspecialchars($edit['image']) ?>" alt=""></div>
        <?php endif; ?>

        <div style="margin-top:10px"><button class="btn" type="submit"><?= $edit_id ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่ม' ?></button></div>
      </form>

      <!-- filters + list -->
      <div>
        <h2>รายการ</h2>
        <form method="get" class="toolbar" style="display:flex;gap:8px;flex-wrap:wrap;margin:6px 0 10px">
          <input type="text" name="q" placeholder="ค้นหาหัวข้อ/รายละเอียด..." value="<?= htmlspecialchars($q) ?>">
          <select name="seg">
            <option value="">สถานะ: ทั้งหมด</option>
            <option value="ongoing"  <?= $seg==='ongoing'?'selected':'' ?>>กำลังดำเนินอยู่</option>
            <option value="upcoming" <?= $seg==='upcoming'?'selected':'' ?>>กำลังจะมา</option>
            <option value="past"     <?= $seg==='past'?'selected':'' ?>>ที่ผ่านมา</option>
          </select>
          <button class="btn">กรอง</button>
          <a class="btn" href="admin_news.php">ล้าง</a>
        </form>

        <div style="overflow:auto">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>รูป</th>
                <th>หัวข้อ</th>
                <th>ช่วงเวลา</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="6" class="muted">ยังไม่มีข้อมูล</td></tr>
              <?php else: foreach($rows as $r): ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?php if ($r['image']): ?><img class="thumb" src="<?= htmlspecialchars($r['image']) ?>" alt=""><?php endif; ?></td>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars(($r['start_date'] ?: '-') . ' — ' . ($r['end_date'] ?: '-')) ?></td>
                  <td><span class="status <?= $r['_status'] ?>"><?= $r['_status']==='ongoing'?'กำลังดำเนินอยู่':($r['_status']==='upcoming'?'กำลังจะมา':'ที่ผ่านมา') ?></span></td>
                  <td>
                    <a class="btn btn-edit" href="admin_news.php?edit=<?= (int)$r['id'] ?>">แก้ไข</a>
                    <a class="btn btn-del" href="admin_news_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('ลบรายการนี้?')">ลบ</a>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
