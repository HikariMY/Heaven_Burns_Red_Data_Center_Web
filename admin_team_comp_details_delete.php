<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo=(new DB())->connect();
$comp_id=(int)($_GET['comp_id']??0);
$id=(int)($_GET['id']??0);
if($id){
  $pdo->prepare("DELETE FROM team_comp_details WHERE id=? AND comp_id=?")->execute([$id,$comp_id]);
}
header('Location: admin_team_comp_details.php?comp_id='.$comp_id);
