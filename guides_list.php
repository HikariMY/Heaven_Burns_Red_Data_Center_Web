<?php
require_once __DIR__.'/db.php';
$pdo = (new DB())->connect();
header('Content-Type: application/json; charset=utf-8');

$mode = trim($_GET['mode'] ?? '');
$q    = trim($_GET['q'] ?? '');

$w = ["status='approved'"];
$p = [];
if ($mode !== '') { $w[]="mode=?"; $p[]=$mode; }
if ($q    !== '') { $w[]="(title LIKE ? OR body LIKE ? OR author_name LIKE ?)"; $like='%'.$q.'%'; array_push($p,$like,$like,$like); }

$sql = "SELECT id,title,mode,author_name,LEFT(body,300) AS excerpt,image1,image2,created_at
        FROM guides
        WHERE ".implode(' AND ',$w)."
        ORDER BY created_at DESC, id DESC";
$st = $pdo->prepare($sql);
$st->execute($p);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
