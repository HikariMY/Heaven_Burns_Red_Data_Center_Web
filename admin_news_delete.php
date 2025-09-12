<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
  // ดึงพาธภาพเพื่อพิจารณาลบไฟล์
  $s = $pdo->prepare("SELECT image FROM events WHERE id=?");
  $s->execute([$id]);
  $row = $s->fetch(PDO::FETCH_ASSOC);

  $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);

  if ($row && !empty($row['image'])) {
    $rel = $row['image'];
    if (strpos($rel, 'uploads/events/') === 0) {
      $abs = __DIR__ . '/' . $rel;
      if (is_file($abs)) @unlink($abs);
    }
  }
}
header('Location: admin_news.php?ok=1');
exit;
