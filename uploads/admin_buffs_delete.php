<?php
// admin_buffs_delete.php
require_once 'auth_guard.php';
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
  // ดึง icon เพื่อพิจารณาลบไฟล์
  $st = $pdo->prepare("SELECT icon FROM buffs WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  $pdo->prepare("DELETE FROM buffs WHERE id=?")->execute([$id]);

  // ลบไฟล์รูป (ถ้าอยู่ใต้ uploads/buffs/)
  if ($row && !empty($row['icon'])) {
    $p = $row['icon'];
    if (strpos($p, 'uploads/buffs/') === 0) {
      $full = __DIR__ . '/' . $p;
      if (is_file($full)) @unlink($full);
    }
  }
}
header('Location: admin_buffs.php?msg=deleted');
exit;
