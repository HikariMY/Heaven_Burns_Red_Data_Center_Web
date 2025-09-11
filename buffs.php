<?php
// buffs.php — API รายการสำหรับหน้า buffs.html
require_once __DIR__ . '/db.php';
$pdo = (new DB())->connect(); // <<< สำคัญ: ใช้แบบเดียวกับที่คุณแก้แล้ว

header('Content-Type: application/json; charset=utf-8');

try {
  $w = []; $p = [];

  if (isset($_GET['type']) && $_GET['type'] !== '') { $w[]="type = ?";           $p[]=$_GET['type']; }
  if (isset($_GET['cat'])  && $_GET['cat']  !== '') { $w[]="category = ?";       $p[]=$_GET['cat']; }
  if (isset($_GET['dur'])  && $_GET['dur']  !== '') { $w[]="duration_kind = ?";  $p[]=$_GET['dur']; }
  if (isset($_GET['q'])    && trim($_GET['q'])!=='') {
    $q = "%".trim($_GET['q'])."%";
    $w[]="(name_th LIKE ? OR category LIKE ? OR description LIKE ? OR tags LIKE ?)";
    array_push($p,$q,$q,$q,$q);
  }

  $sql = "SELECT id,name_th,icon,type,category,duration_kind,duration_value,`trigger`,description,tags
          FROM buffs";
  if ($w) $sql .= " WHERE ".implode(" AND ", $w);
  $sql .= " ORDER BY id DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($p);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
