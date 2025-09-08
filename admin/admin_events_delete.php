<?php
$pdo = new PDO('mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4', 'root', '');
$id = (int)($_GET['id'] ?? 0);
if ($id) {
  $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
}
header('Location: admin_events.php');
