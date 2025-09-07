<?php
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO(
  'mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4',
  'root',
  '',
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$seraph_id = isset($_GET['seraph_id']) ? (int)$_GET['seraph_id'] : 0;
if ($seraph_id <= 0) {
  echo '[]';
  exit;
}
$st = $pdo->prepare("SELECT tab,name,description AS `desc`,order_no FROM seraph_skills WHERE seraph_id=? ORDER BY tab,order_no,id");
$st->execute([$seraph_id]);
echo json_encode($st->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
