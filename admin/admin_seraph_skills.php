<?php
session_start();
if (empty($_SESSION['is_admin'])) {
  header('Location: <admin>admin_login.php');
  exit;
}
$pdo = new PDO(
  'mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4',
  'root',
  '',
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$msg = '';

$seraph_id = isset($_GET['seraph_id']) ? (int)$_GET['seraph_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['csrf'] ?? '') === $_SESSION['csrf']) {
  $seraph_id = (int)($_POST['seraph_id'] ?? 0);
  $tab = $_POST['tab'] ?? 'skill';
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['desc'] ?? '');
  $ord = (int)($_POST['order_no'] ?? 0);
  if ($seraph_id > 0 && $name !== '') {
    $st = $pdo->prepare("INSERT INTO seraph_skills(seraph_id,tab,name,description,order_no) VALUES (?,?,?,?,?)");
    $st->execute([$seraph_id, $tab, $name, $desc, $ord]);
    $msg = 'เพิ่มสกิลเรียบร้อย';
  } else $msg = 'กรอกข้อมูลไม่ครบ';
}

if (isset($_GET['del'])) {
  $del = (int)$_GET['del'];
  $pdo->prepare("DELETE FROM seraph_skills WHERE id=?")->execute([$del]);
  $msg = 'ลบแล้ว';
}

$seraphs = $pdo->query("SELECT id,name_th,rarity FROM seraphs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$skills = [];
if ($seraph_id > 0) {
  $st = $pdo->prepare("SELECT id,tab,name,description,order_no FROM seraph_skills WHERE seraph_id=? ORDER BY tab,order_no,id");
  $st->execute([$seraph_id]);
  $skills = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Seraph Skills</title>
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <aside class="admin-sidebar">
    <div class="side-head">Admin</div>
    <nav class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">⚔️</span><span class="label">จัดการ Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">📚</span><span class="label">จัดการสกิล</span></a>
      <a class="side-item" href="logout.php"><span class="ico">🚪</span><span class="label">ออกจากระบบ</span></a>
    </nav>
  </aside>

  <main class="admin-main">
    <h1>จัดการสกิล</h1>
    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="grid">
      <form method="get">
        <h2>เลือกตัวละคร</h2>
        <label>Seraph</label>
        <select name="seraph_id" required>
          <option value="">— เลือก —</option>
          <?php foreach ($seraphs as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $seraph_id === $s['id'] ? 'selected' : '' ?>>#<?= (int)$s['id'] ?> — <?= htmlspecialchars($s['name_th']) ?> (<?= htmlspecialchars($s['rarity']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">โหลด</button>
      </form>

      <form method="post">
        <h2>เพิ่มสกิลให้ตัวที่เลือก</h2>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
        <input type="hidden" name="seraph_id" value="<?= (int)$seraph_id ?>">
        <label>หมวด</label>
        <select name="tab">
          <option value="skill">Skill</option>
          <option value="passive">Passive</option>
          <option value="resonance">Resonance</option>
        </select>
        <label>ชื่อสกิล</label><input type="text" name="name" required>
        <label>รายละเอียด</label><textarea name="desc"></textarea>
        <label>ลำดับ</label><input type="number" name="order_no" value="0">
        <button class="btn" type="submit" <?= $seraph_id > 0 ? '' : 'disabled' ?>>บันทึก</button>
      </form>
    </div>

    <h2 style="margin-top:18px">รายการสกิล <?= $seraph_id ? '#' . $seraph_id : '' ?></h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>หมวด</th>
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
            <td><?= htmlspecialchars($k['tab']) ?></td>
            <td><?= htmlspecialchars($k['name']) ?></td>
            <td><?= nl2br(htmlspecialchars($k['description'])) ?></td>
            <td><?= (int)$k['order_no'] ?></td>
            <td><a class="btn-del" href="?seraph_id=<?= (int)$seraph_id ?>&del=<?= (int)$k['id'] ?>" onclick="return confirm('ลบสกิลนี้?')">ลบ</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$skills): ?><tr>
            <td colspan="6" style="opacity:.7">ยังไม่มีข้อมูล</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </main>
</body>

</html>