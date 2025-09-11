<?php
// คืนรายการทีม + สมาชิก 6 ช่อง
require_once 'db.php';
$pdo = (new DB())->connect();
$element = $_GET['element'] ?? '';

$sql = "SELECT id,title,element,cover_image,notes FROM team_comps";
$params = [];
if ($element !== '') { $sql .= " WHERE element = ?"; $params[] = $element; }
$sql .= " ORDER BY id DESC";
$st = $pdo->prepare($sql); $st->execute($params); $teams = $st->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($teams as $t) {
  $m = $pdo->prepare(
    "SELECT m.slot_no, s.id, s.name_th, s.rarity, s.style, s.element, s.image
     FROM team_comp_members m
     JOIN seraphs s ON s.id=m.seraph_id
     WHERE m.comp_id=? ORDER BY m.slot_no ASC"
  );
  $m->execute([$t['id']]);
  $out[] = [
    'id' => (int)$t['id'],
    'title' => $t['title'],
    'element' => $t['element'],
    'cover_image' => $t['cover_image'],
    'notes' => $t['notes'],
    'members' => $m->fetchAll(PDO::FETCH_ASSOC)
  ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out);
