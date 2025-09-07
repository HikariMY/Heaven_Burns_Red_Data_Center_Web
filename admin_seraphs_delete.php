<?php
// admin_seraphs_delete.php
session_start();

// ต้องเป็นแอดมินเท่านั้น
if (empty($_SESSION['is_admin'])) {
  header('Location: admin_login.php');
  exit;
}

// ตรวจ CSRF
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
  http_response_code(403);
  exit('CSRF invalid');
}

// รับค่า id
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  header('Location: admin_seraphs.php');
  exit;
}

try {
  $pdo = new PDO('mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4', 'root', '');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // อ่านข้อมูลเดิมเพื่อรู้พาธรูป (ถ้ามี)
  $st = $pdo->prepare("SELECT image FROM seraphs WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    // ถ้าไม่มีแถวนี้แล้ว ให้กลับเฉยๆ
    header('Location: admin_seraphs.php?msg=notfound');
    exit;
  }

  // ลบข้อมูลในฐานข้อมูล
  $del = $pdo->prepare("DELETE FROM seraphs WHERE id=?");
  $del->execute([$id]);

  // ลบไฟล์รูปเฉพาะที่อยู่ในโฟลเดอร์อัปโหลดของเรา (กันพลาด path traversal)
  if (!empty($row['image'])) {
    $rel = $row['image']; // เช่น uploads/seraphs/xxx.jpg
    $abs = __DIR__ . '/' . $rel;
    if (strpos($rel, 'uploads/seraphs/') === 0 && is_file($abs)) {
      @unlink($abs);
    }
  }

  header('Location: admin_seraphs.php?msg=deleted');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Delete error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
}
