<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
  require_once __DIR__ . '/db.php';
  $pdo = (new DB())->connect();

  $seraph_id = isset($_GET['seraph_id']) ? (int)$_GET['seraph_id'] : 0;
  if ($seraph_id <= 0) { echo '[]'; exit; }

  $st = $pdo->prepare("
    SELECT tab, name, description AS `desc`, order_no
    FROM seraph_skills
    WHERE seraph_id=?
    ORDER BY tab, order_no, id
  ");
  $st->execute([$seraph_id]);
  echo json_encode($st->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>true,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
