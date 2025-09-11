<?php
require_once 'db.php';
$pdo = (new DB())->connect();

$element = $_GET['element'] ?? ($_GET['el'] ?? '');
$stars   = isset($_GET['stars']) && $_GET['stars']!=='' ? (int)$_GET['stars'] : null;
$type_id = isset($_GET['type_id']) && $_GET['type_id']!=='' ? (int)$_GET['type_id'] : null;
$q       = trim($_GET['q'] ?? '');

$sql = "SELECT a.id,a.name_th,a.stars,a.element,a.image,a.type_id,t.name_th AS type_name,t.icon AS type_icon
        FROM accessories a
        JOIN accessory_types t ON t.id=a.type_id
        WHERE 1=1";
$prm = [];
if ($element!=='') { $sql.=" AND a.element=?"; $prm[]=$element; }
if ($stars!==null) { $sql.=" AND a.stars=?";   $prm[]=$stars; }
if ($type_id!==null){$sql.=" AND a.type_id=?"; $prm[]=$type_id; }
if ($q!=='') { $sql.=" AND a.name_th LIKE ?";  $prm[]="%$q%"; }
$sql.=" ORDER BY a.stars DESC,a.id DESC";

$st = $pdo->prepare($sql); $st->execute($prm);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
