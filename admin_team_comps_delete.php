<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();
$id = (int)($_GET['id'] ?? 0);
if ($id) { $pdo->prepare("DELETE FROM team_comps WHERE id=?")->execute([$id]); }
header('Location: admin_team_comps.php');
