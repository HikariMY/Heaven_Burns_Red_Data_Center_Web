<?php
require_once 'auth_guard.php'; require_once 'db.php';
$pdo=(new DB())->connect();
$id=(int)($_GET['id']??0);
if($id){
  // ป้องกันลบถ้ายังถูกใช้งาน
  $c=$pdo->prepare("SELECT COUNT(*) FROM accessories WHERE type_id=?"); $c->execute([$id]);
  if($c->fetchColumn()==0){
    $pdo->prepare("DELETE FROM accessory_types WHERE id=?")->execute([$id]);
  } else {
    // ถ้าต้องการให้ลบได้ ให้ย้าย accessories ไป type อื่นก่อน หรือ cascade ตามต้องการ
  }
}
header('Location: admin_accessory_types.php');
