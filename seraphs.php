<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
  $pdo = new PDO(
    'mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  // ----- โหมดอ่านตัวเดียว -----
  if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => true, 'message' => 'invalid id'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $st = $pdo->prepare("SELECT id,name_th,name_jp,role,rarity,element,style,image,tags,
                                dp,hp,str_val,dex,pdef,mdef,int_stat,luck,
                                tier_rank, obtain_type
                         FROM seraphs WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      http_response_code(404);
      echo json_encode(['error' => true, 'message' => 'not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ----- โหมดลิสต์ทั้งหมด (เพิ่ม obtain_type ที่นี่) -----
  $rows = $pdo->query("SELECT id,name_th,name_jp,role,rarity,element,style,image,tags,
                               dp,hp,str_val,dex,pdef,mdef,int_stat,luck,
                               tier_rank, obtain_type
                        FROM seraphs
                        ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
