<?php
// คืนหัวทีม + รายละเอียดแถว + swaps
require_once 'db.php';
$pdo = (new DB())->connect();
$id = (int)($_GET['id'] ?? 0);

$head = $pdo->prepare("SELECT id,title,element,cover_image,notes FROM team_comps WHERE id=?");
$head->execute([$id]);
$team = $head->fetch(PDO::FETCH_ASSOC);
if (!$team) { http_response_code(404); exit; }

$members = $pdo->prepare(
  "SELECT m.slot_no, s.id, s.name_th, s.rarity, s.style, s.element, s.image
   FROM team_comp_members m
   JOIN seraphs s ON s.id=m.seraph_id
   WHERE m.comp_id=? ORDER BY m.slot_no ASC"
);
$members->execute([$id]);

$details = $pdo->prepare(
  "SELECT d.id,d.order_no,d.description,
          s.id AS seraph_id, s.name_th, s.rarity, s.style, s.element, s.image
   FROM team_comp_details d
   JOIN seraphs s ON s.id=d.seraph_id
   WHERE d.comp_id=? ORDER BY d.order_no ASC, d.id ASC"
);
$details->execute([$id]);
$detailRows = $details->fetchAll(PDO::FETCH_ASSOC);

$sw = $pdo->prepare(
  "SELECT sw.detail_id,
          s.id, s.name_th, s.rarity, s.style, s.element, s.image
   FROM team_comp_detail_swaps sw
   JOIN seraphs s ON s.id=sw.seraph_id
   WHERE sw.detail_id IN (SELECT d.id FROM team_comp_details d WHERE d.comp_id=?)"
);
$sw->execute([$id]);
$swaps = $sw->fetchAll(PDO::FETCH_ASSOC);
$map = [];
foreach ($swaps as $r) { $map[$r['detail_id']][] = $r; }
foreach ($detailRows as &$r) { $r['swaps'] = $map[$r['id']] ?? []; }

$out = [
  'team' => $team,
  'members' => $members->fetchAll(PDO::FETCH_ASSOC),
  'details' => $detailRows
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out);
