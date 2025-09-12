<?php
require_once 'auth_guard.php'; require_once 'db.php';
$pdo = (new DB())->connect();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name_th'] ?? '');
  $icon = null;

  if (!empty($_FILES['icon']['name']) && is_uploaded_file($_FILES['icon']['tmp_name'])) {
    $dir='icon/'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    $ext=strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
    $fname='acc_type_'.date('Ymd_His').'_'.mt_rand(1000,9999).'.'.$ext;
    if (move_uploaded_file($_FILES['icon']['tmp_name'],$dir.$fname)) $icon=$dir.$fname;
  }

  if ($id) {
    if ($icon) {
      $pdo->prepare("UPDATE accessory_types SET name_th=?, icon=? WHERE id=?")->execute([$name,$icon,$id]);
    } else {
      $pdo->prepare("UPDATE accessory_types SET name_th=? WHERE id=?")->execute([$name,$id]);
    }
  } else {
    $pdo->prepare("INSERT INTO accessory_types(name_th,icon) VALUES(?,?)")->execute([$name,$icon ?? 'icon/placeholder.png']);
  }
  header('Location: admin_accessory_types.php?ok=1'); exit;
}

$edit=(int)($_GET['edit']??0); $row=null;
if($edit){ $s=$pdo->prepare("SELECT * FROM accessory_types WHERE id=?"); $s->execute([$edit]); $row=$s->fetch(PDO::FETCH_ASSOC); }
$types=$pdo->query("SELECT * FROM accessory_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<body class="sb-hover">
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
      <a class="side-item active" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="admin_news.php"><span class="ico">📰</span><span class="label">News</span></a>
      <a class="side-item" href="admin_guides.php"><span class="ico">✍️</span><span class="label">Guides</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>
  <main class="admin-main">
    <h1>Accessory Types</h1>
    <?php if(!empty($_GET['ok'])) echo '<div class="msg">บันทึกสำเร็จ</div>'; ?>

    <form method="post" enctype="multipart/form-data" style="background:#fff;padding:16px;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.08)">
      <input type="hidden" name="id" value="<?= $row['id'] ?? '' ?>">
      <label>ชื่อประเภท</label>
      <input name="name_th" required value="<?= htmlspecialchars($row['name_th'] ?? '') ?>">
      <label>ไอคอน (ออปชัน)</label>
      <input type="file" name="icon">
      <button class="btn" type="submit">บันทึก</button>
    </form>

    <h2 style="margin-top:14px">รายการประเภท</h2>
    <table>
      <tr><th>ID</th><th>ไอคอน</th><th>ชื่อ</th><th>จัดการ</th></tr>
      <?php foreach($types as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?php if($t['icon']): ?><img src="<?= htmlspecialchars($t['icon']) ?>" style="width:28px;height:28px"><?php endif; ?></td>
          <td><?= htmlspecialchars($t['name_th']) ?></td>
          <td>
            <a class="btn btn-edit" href="admin_accessory_types.php?edit=<?= $t['id'] ?>">แก้ไข</a>
            <a class="btn btn-del" href="admin_accessory_types_delete.php?id=<?= $t['id'] ?>" onclick="return confirm('ลบประเภทนี้? จะลบไม่ได้ถ้ามีการใช้อยู่')">ลบ</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </main>
</body>
