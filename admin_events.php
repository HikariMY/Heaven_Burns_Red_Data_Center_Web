<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$ev = ['title'=>'','description'=>'','start_date'=>'','end_date'=>'','image'=>''];

if ($editId) {
  $st = $pdo->prepare("SELECT * FROM events WHERE id=?");
  $st->execute([$editId]);
  $ev = $st->fetch(PDO::FETCH_ASSOC) ?: $ev;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $start = $_POST['start_date'] ?: null;
  $end   = $_POST['end_date'] ?: null;

  $imgPath = $ev['image'] ?? null;
  if (!empty($_FILES['image']['name'])) {
    @mkdir(__DIR__.'/uploads/events',0777,true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $fname = 'ev_'.date('Ymd_His').'_'.mt_rand(1000,9999).'.'.$ext;
    $dest = __DIR__.'/uploads/events/'.$fname;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
      $imgPath = 'uploads/events/'.$fname;
    }
  }

  if ($editId) {
    $pdo->prepare("UPDATE events SET title=?, description=?, start_date=?, end_date=?, image=? WHERE id=?")
        ->execute([$title,$desc,$start,$end,$imgPath,$editId]);
    $msg = 'อัปเดตเรียบร้อย';
  } else {
    $pdo->prepare("INSERT INTO events (title,description,start_date,end_date,image) VALUES (?,?,?,?,?)")
        ->execute([$title,$desc,$start,$end,$imgPath]);
    $msg = 'เพิ่มอีเวนต์เรียบร้อย';
  }

  if ($editId) {
    $st = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $st->execute([$editId]);
    $ev = $st->fetch(PDO::FETCH_ASSOC) ?: $ev;
  } else {
    $ev = ['title'=>'','description'=>'','start_date'=>'','end_date'=>'','image'=>''];
  }
}

$list = $pdo->query("SELECT id,title,start_date,end_date,image FROM events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Events</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
</head>
<body>
  <!-- Admin Sidebar -->
  <aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
      <a class="side-item" href="admin_events.php"><span class="ico">🗓️</span><span class="label">Events</span></a>
      <a class="side-item" href="admin_team_comps.php"><span class="ico">👥</span><span class="label">Team Comp</span></a>
      <a class="side-item" href="admin_accessories.php"><span class="ico">💍</span><span class="label">Accessories</span></a>
      <a class="side-item" href="admin_accessory_types.php"><span class="ico">🗂️</span><span class="label">Accessory Types</span></a>
      <a class="side-item" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>จัดการสไลด์อีเวนต์</h1>
    <?php if ($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif;?>

    <div class="grid">
      <!-- ฟอร์ม -->
      <form method="post" enctype="multipart/form-data">
        <h2><?= $editId ? 'แก้ไข #'.$editId : 'เพิ่มอีเวนต์ใหม่' ?></h2>

        <label>หัวข้อ</label>
        <input type="text" name="title" required value="<?=htmlspecialchars($ev['title']??'')?>">

        <label>รายละเอียด</label>
        <textarea name="description"><?=htmlspecialchars($ev['description']??'')?></textarea>

        <div style="display:flex; gap:12px">
          <div style="flex:1">
            <label>วันเริ่มอีเวนต์</label>
            <input type="date" name="start_date" value="<?=htmlspecialchars($ev['start_date']??'')?>">
          </div>
          <div style="flex:1">
            <label>วันหมดอีเวนต์</label>
            <input type="date" name="end_date" value="<?=htmlspecialchars($ev['end_date']??'')?>">
          </div>
        </div>

        <label>รูปภาพ</label>
        <input type="file" name="image" accept="image/*">
        <?php if(!empty($ev['image'])): ?>
          <div style="margin-top:8px"><img class="thumb" src="<?=htmlspecialchars($ev['image'])?>"></div>
        <?php endif; ?>

        <button class="btn" type="submit"><?= $editId ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่ม' ?></button>
      </form>

      <!-- รายการ -->
      <div>
        <h2>รายการอีเวนต์</h2>
        <table>
          <thead><tr><th>#</th><th>หัวข้อ</th><th>ช่วงเวลา</th><th>รูป</th><th>จัดการ</th></tr></thead>
          <tbody>
          <?php foreach($list as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['title']) ?></td>
              <td><?= htmlspecialchars(($r['start_date']??'').' — '.($r['end_date']??'')) ?></td>
              <td><?php if($r['image']): ?><img class="thumb" src="<?=htmlspecialchars($r['image'])?>"><?php endif; ?></td>
              <td>
                <a href="?edit=<?=$r['id']?>">แก้ไข</a>
                &nbsp;|&nbsp;
                <a href="admin_events_delete.php?id=<?=$r['id']?>" onclick="return confirm('ลบอีเวนต์นี้?')">ลบ</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
  (function(){
    const sb = document.querySelector('.admin-sidebar');
    if (!sb) return;
    sb.addEventListener('mouseenter', ()=> document.body.classList.add('sb-hover'));
    sb.addEventListener('mouseleave', ()=> document.body.classList.remove('sb-hover'));
    const pinBtn = sb.querySelector('.sb-pin');
    if (pinBtn){
      pinBtn.addEventListener('click', ()=>{
        document.body.classList.toggle('sb-pinned');
        if (document.body.classList.contains('sb-pinned')) {
          document.body.classList.remove('sb-hover');
        }
      });
    }
  })();
  </script>
</body>
</html>
