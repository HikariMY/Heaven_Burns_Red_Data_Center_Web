<?php

header('Content-Type: application/json; charset=utf-8');

try {
  // แก้ให้ตรงกับฐานข้อมูลที่คุณใช้
  $pdo = new PDO('mysql:host=localhost;dbname=hbr_web_db;charset=utf8mb4', 'root', '');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // หลีกเลี่ยง alias คำว่า desc ซึ่งเป็น keyword
  $sql = "SELECT 
            id, 
            title, 
            description AS description_text, 
            image,
            start_date AS start,
            end_date   AS end
          FROM events
          ORDER BY start_date DESC, id DESC";

  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  // ส่ง JSON ที่อ่านง่ายเวลา debug — หน้าเว็บจะ fallback ได้
  http_response_code(500);
  echo json_encode([
    'error' => true,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
