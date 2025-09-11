<?php
require_once 'db.php';
$pdo = (new DB())->connect();
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT a.*, t.name_th AS type_name, t.icon AS type_icon
                     FROM accessories a
                     JOIN accessory_types t ON t.id=a.type_id
                     WHERE a.id=?");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit; }

header('Content-Type: application/json; charset=utf-8');
echo json_encode($row);
