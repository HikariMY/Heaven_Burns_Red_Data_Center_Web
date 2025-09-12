<?php
require_once __DIR__.'/db.php';
$pdo = (new DB())->connect();
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT id,title,mode,author_name,body,image1,image2,created_at,status FROM guides WHERE id=?");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['status']!=='approved') { http_response_code(404); echo json_encode(['error'=>true]); exit; }
echo json_encode($row, JSON_UNESCAPED_UNICODE);
