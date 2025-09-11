<?php
// buff_detail.php — API รายละเอียด + effects
require_once __DIR__ . '/db.php';
$pdo = (new DB())->connect(); // <<< สำคัญ

header('Content-Type: application/json; charset=utf-8');

try {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

  $stmt = $pdo->prepare("SELECT * FROM buffs WHERE id = ?");
  $stmt->execute([$id]);
  $buff = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$buff) { http_response_code(404); echo json_encode(['error'=>'not found'], JSON_UNESCAPED_UNICODE); exit; }

  $e = $pdo->prepare("SELECT title,`value`,note,order_no FROM buff_effects WHERE buff_id = ? ORDER BY order_no, id");
  $e->execute([$id]);
  $buff['effects'] = $e->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($buff, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
