<?php
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));

require_once __DIR__.'/db.php';
$pdo = (new DB())->connect();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['csrf']??'')===$_SESSION['csrf']) {
  $title = trim($_POST['title'] ?? '');
  $mode  = trim($_POST['mode'] ?? '');
  $author= trim($_POST['author_name'] ?? '');
  $body  = trim($_POST['body'] ?? '');

  if ($title==='' || $mode==='' || $author==='' || $body==='') {
    $err = 'กรอกข้อมูลให้ครบ';
  } else {
    // อัปโหลดรูป: สูงสุด 2 ไฟล์
    @mkdir(__DIR__.'/uploads/guides',0777,true);
    $up = function($field) {
      if (empty($_FILES[$field]['name'])) return null;
      if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
      // จำกัดชนิด/ขนาด
      $okExt = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
      if (!in_array($ext,$okExt,true)) return null;
      if ($_FILES[$field]['size'] > 2*1024*1024) return null; // 2MB
      $name='guide_'.date('Ymd_His').'_'.mt_rand(1000,9999).'.'.$ext;
      $dest=__DIR__.'/uploads/guides/'.$name;
      if (move_uploaded_file($_FILES[$field]['tmp_name'],$dest)) return 'uploads/guides/'.$name;
      return null;
    };
    $img1 = $up('image1');
    $img2 = $up('image2');

    $st = $pdo->prepare("INSERT INTO guides(title,mode,author_name,body,image1,image2,status) VALUES (?,?,?,?,?,?, 'pending')");
    $st->execute([$title,$mode,$author,$body,$img1,$img2]);
    $msg = 'ส่งไกด์เรียบร้อย! รอแอดมินตรวจสอบก่อนเผยแพร่';
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ส่งไกด์</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<style>
  .wrap{max-width:900px;margin:16px auto;padding:0 16px}
  .card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,.06)}
  label{display:block;margin-top:10px;font-weight:800}
  input[type=text],textarea,select{width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:10px}
  .msg{margin-top:10px;padding:10px;border-radius:10px;background:#ecfeff;border:1px solid #bae6fd}
  .err{background:#fff1f2;border-color:#fecdd3}
</style>
</head>
<body>
  <main class="wrap">
    <h1>✍️ ส่งไกด์</h1>
    <?php if($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>
    <?php if($err): ?><div class="msg err"><?=htmlspecialchars($err)?></div><?php endif; ?>

    <form class="card" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf'],ENT_QUOTES)?>">
      <label>หัวข้อ*</label>
      <input type="text" name="title" required>

      <label>โหมด*</label>
      <select name="mode" required>
        <option value="">— เลือก —</option>
        <option>PVE</option><option>PVP</option><option>RAID</option><option>EVENT</option>
      </select>

      <label>ชื่อผู้เขียน*</label>
      <input type="text" name="author_name" required>

      <label>เนื้อหาไกด์*</label>
      <textarea name="body" rows="10" required placeholder="เล่ารายละเอียด เทคนิค องค์ประกอบทีม ฯลฯ"></textarea>

      <label>ภาพตัวอย่าง (ออปชัน) — อัปโหลดได้สูงสุด 2 รูป, ไฟล์ .jpg .png .webp ≤ 2MB/รูป</label>
      <input type="file" name="image1" accept=".jpg,.jpeg,.png,.webp">
      <input type="file" name="image2" accept=".jpg,.jpeg,.png,.webp">

      <div style="margin-top:12px">
        <button class="btn" type="submit">ส่งไกด์</button>
        <a class="btn" href="guides.html">← กลับหน้า Guides</a>
      </div>
    </form>
  </main>
</body>
</html>
