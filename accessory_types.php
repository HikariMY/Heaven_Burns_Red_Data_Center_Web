<?php
require_once 'db.php';
$pdo = (new DB())->connect();
$rows = $pdo->query("SELECT id, name_th, icon FROM accessory_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
