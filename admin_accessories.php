<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

/* CREATE / UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id    = (int)($_POST['id'] ?? 0);
  $name  = trim($_POST['name_th'] ?? '');
  $stars = (int)($_POST['stars'] ?? 1);
  $element = $_POST['element'] ?? 'ไฟ';
  $type_id = (int)($_POST['type_id'] ?? 0);
  $level = $_POST['level'] !== '' ? (int)$_POST['level'] : null;
  $price = $_POST['price'] !== '' ? (int)$_POST['price'] : null;
  $desc = trim($_POST['description'] ?? '');
  $image = null;

  if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $dir = 'uploads/accessories/'; if (!is_dir($dir)) @mkdir($dir,0777,true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $fname = 'acc_'.date('Ymd_His').'_'.mt_rand(1000,9999).'.'.$ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fname)) $image = $dir.$fname;
  }

  if ($id) {
    if ($image) {
      $pdo->prepare("UPDATE accessories SET name_th=?,stars=?,element=?,type_id=?,level=?,price=?,description=?,image=? WHERE id=?")
          ->execute([$name,$stars,$element,$type_id,$level,$price,$desc,$image,$id]);
    } else {
      $pdo->prepare("UPDATE accessories SET name_th=?,stars=?,element=?,type_id=?,level=?,price=?,description=? WHERE id=?")
          ->execute([$name,$stars,$element,$type_id,$level,$price,$desc,$id]);
    }
  } else {
    $pdo->prepare("INSERT INTO accessories(name_th,stars,element,type_id,level,price,description,image)
                   VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$name,$stars,$element,$type_id,$level,$price,$desc,$image]);
  }
  header('Location: admin_accessories.php?ok=1'); exit;
}

/* LOAD DATA */
$types = $pdo->query("SELECT id,name_th FROM accessory_types ORDER BY id ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

$edit = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($edit) {
  $s = $pdo->prepare("SELECT * FROM accessories WHERE id=?"); $s->execute([$edit]); $editRow = $s->fetch(PDO::FETCH_ASSOC);
}

$rows = $pdo->query("SELECT a.id,a.name_th,a.stars,a.element,t.name_th AS type_name,a.created_at
                     FROM accessories a JOIN accessory_types t ON t.id=a.type_id
                     ORDER BY a.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin – Accessories</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<style>
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .msg{background:#e8fff0;border:1px solid #a7e0b8;padding:8px 12px;border-radius:8px;margin:10px 0}
  .msg.err{background:#fff2f2;border-color:#f2b8b5}
  .btn{display:inline-block;padding:6px 10px;border-radius:8px;border:1px solid #ddd;background:#fff;cursor:pointer}
  .btn-edit{color:#0b68e3}
  .btn-del{color:#d0302e}
  table{width:100%;border-collapse:collapse;margin-top:14px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.1)}
  th,td{border-bottom:1px solid #eee;padding:8px}
  th{background:#f1f5f9}
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
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="admin-main">
    <h1>Accessories</h1>
    <?php if(!empty($_GET['ok'])) echo '<div class="msg">บันทึกสำเร็จ</div>'; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $editRow['id'] ?? '' ?>">
      <div class="grid">
        <div>
          <label>ชื่อ</label>
          <input name="name_th" required value="<?= htmlspecialchars($editRow['name_th'] ?? '') ?>">

          <label>ดาว</label>
          <select name="stars" required>
            <?php for($i=1;$i<=6;$i++): $sel=(int)($editRow['stars']??0)===$i?'selected':''; ?>
              <option value="<?= $i ?>" <?= $sel ?>><?= str_repeat('⭐',$i) ?></option>
            <?php endfor; ?>
          </select>

          <label>ธาตุ</label>
          <select name="element" required>
            <?php $els=['ไฟ','สายฟ้า','น้ำแข็ง','แสง','มืด']; $cur=$editRow['element']??''; foreach($els as $e): ?>
              <option value="<?= $e ?>" <?= $e===$cur?'selected':'' ?>><?= $e ?></option>
            <?php endforeach; ?>
          </select>

          <label>ประเภท</label>
          <select name="type_id" required>
            <option value="">-- เลือก --</option>
            <?php foreach($types as $tid=>$tn): $sel=(int)($editRow['type_id']??0)===$tid?'selected':''; ?>
              <option value="<?= $tid ?>" <?= $sel ?>><?= htmlspecialchars($tn) ?></option>
            <?php endforeach; ?>
          </select>

          <label>รูป</label>
          <input type="file" name="image">
        </div>

        <div>
          <label>เลเวล (ออปชัน)</label>
          <input type="number" name="level" value="<?= htmlspecialchars($editRow['level'] ?? '') ?>">

          <label>ราคา (ออปชัน)</label>
          <input type="number" name="price" value="<?= htmlspecialchars($editRow['price'] ?? '') ?>">

          <label>รายละเอียด/เอฟเฟกต์</label>
          <textarea name="description" rows="6"><?= htmlspecialchars($editRow['description'] ?? '') ?></textarea>

          <button class="btn" type="submit">บันทึก</button>
        </div>
      </div>
    </form>

    <h2 style="margin-top:18px">รายการ</h2>
    <table>
      <tr><th>ID</th><th>ชื่อ</th><th>ดาว</th><th>ธาตุ</th><th>ประเภท</th><th>วันที่</th><th>จัดการ</th></tr>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name_th']) ?></td>
          <td><?= $r['stars'] ?></td>
          <td><?= $r['element'] ?></td>
          <td><?= htmlspecialchars($r['type_name']) ?></td>
          <td><?= $r['created_at'] ?></td>
          <td>
            <a class="btn btn-edit" href="admin_accessories.php?edit=<?= $r['id'] ?>">แก้ไข</a>
            <a class="btn btn-del" href="admin_accessories_delete.php?id=<?= $r['id'] ?>" onclick="return confirm('ลบรายการนี้?')">ลบ</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </main>
</body>
</html>
