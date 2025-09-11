<?php
require_once __DIR__ . '/db.php';
$pdo = (new DB())->connect();

header('Content-Type: application/json; charset=utf-8');

try {
  // สั่งเรียงตาม start_date ล่าสุด
  $sql = "SELECT 
            id,
            title,
            description AS description_text,
            image,
            start_date AS start,
            end_date   AS end
          FROM events
          ORDER BY start_date DESC, id DESC";

  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
