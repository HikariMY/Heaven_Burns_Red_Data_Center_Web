<?php
require_once __DIR__.'/auth_guard.php';
require_once __DIR__.'/db.php';
$pdo=(new DB())->connect();

$act = $_GET['act'] ?? '';
$id  = (int)($_GET['id'] ?? 0);
if ($id>0 && in_array($act,['approve','reject','delete'],true)) {
  if ($act==='delete') {
    // ลบไฟล์รูปด้วย
    $s=$pdo->prepare("SELECT image1,image2 FROM guides WHERE id=?"); $s->execute([$id]); $r=$s->fetch(PDO::FETCH_ASSOC);
    $pdo->prepare("DELETE FROM guides WHERE id=?")->execute([$id]);
    foreach (['image1','image2'] as $k) {
      if (!empty($r[$k]) && strpos($r[$k],'uploads/guides/')===0) {
        $abs = __DIR__.'/'.$r[$k];
        if (is_file($abs)) @unlink($abs);
      }
    }
  } else {
    $pdo->prepare("UPDATE guides SET status=? WHERE id=?")->execute([$act==='approve'?'approved':'rejected',$id]);
  }
  header('Location: admin_guides.php'); exit;
}

$status = $_GET['status'] ?? '';
$mode   = $_GET['mode'] ?? '';
$q      = trim($_GET['q'] ?? '');

$w=[]; $p=[];
if ($status!=='') { $w[]='status=?'; $p[]=$status; }
if ($mode  !=='') { $w[]='mode=?';   $p[]=$mode; }
if ($q     !=='') { $w[]='(title LIKE ? OR author_name LIKE ? OR body LIKE ?)'; $like='%'.$q.'%'; array_push($p,$like,$like,$like); }

$sql="SELECT id,title,mode,author_name,status,created_at FROM guides";
if ($w) $sql.=" WHERE ".implode(' AND ',$w);
$sql.=" ORDER BY created_at DESC, id DESC";
$st=$pdo->prepare($sql); $st->execute($p); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Guides</title>
<link rel="stylesheet" href="admin.css"><link rel="stylesheet" href="admin_theme.css">
<style>
  .wrap{max-width:1100px;margin:16px auto;padding:0 16px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #f1f5f9;border-radius:12px;overflow:hidden}
  th,td{padding:10px;border-bottom:1px solid #f8fafc;text-align:left}
  th{background:#ffe4ea}
  .btn{display:inline-block;padding:6px 10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff}
  .toolbar{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px}
  input,select{padding:6px 8px;border:1.5px solid #e5e7eb;border-radius:8px}
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
      <a class="side-item" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs</span></a>
      <a class="side-item" href="admin_guides.php"><span class="ico">✍️</span><span class="label">Guides</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>Guides (ตรวจ/อนุมัติ)</h1>

    <form class="toolbar" method="get">
      <select name="status">
        <option value="">สถานะ: ทั้งหมด</option>
        <?php foreach(['pending'=>'รอตรวจ','approved'=>'อนุมัติ','rejected'=>'ปฏิเสธ'] as $k=>$v): ?>
          <option value="<?=$k?>" <?=$status===$k?'selected':''?>><?=$v?></option>
        <?php endforeach; ?>
      </select>
      <select name="mode">
        <option value="">โหมด: ทั้งหมด</option>
        <?php foreach(['PVE','PVP','RAID','EVENT'] as $m): ?>
          <option <?=$mode===$m?'selected':''?>><?=$m?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" placeholder="ค้นหา..." value="<?=htmlspecialchars($q)?>">
      <button class="btn">กรอง</button>
      <a class="btn" href="admin_guides.php">ล้าง</a>
    </form>

    <table>
      <thead><tr><th>#</th><th>หัวข้อ</th><th>โหมด</th><th>ผู้เขียน</th><th>สถานะ</th><th>เวลา</th><th>จัดการ</th></tr></thead>
      <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="7" class="muted">ยังไม่มีข้อมูล</td></tr>
        <?php else: foreach($rows as $r): ?>
          <tr>
            <td><?=$r['id']?></td>
            <td><?=htmlspecialchars($r['title'])?></td>
            <td><?=htmlspecialchars($r['mode'])?></td>
            <td><?=htmlspecialchars($r['author_name'])?></td>
            <td><?=htmlspecialchars($r['status'])?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td>
              <a class="btn" href="admin_guides.php?act=approve&id=<?=$r['id']?>">อนุมัติ</a>
              <a class="btn" href="admin_guides.php?act=reject&id=<?=$r['id']?>">ปฏิเสธ</a>
              <a class="btn" href="admin_guides.php?act=delete&id=<?=$r['id']?>" onclick="return confirm('ลบไกด์นี้?')">ลบ</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </main>
</body>
</html>
