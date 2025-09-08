<?php
// ===== Admin Login (no DB account) =====
session_start();

/* -------------------- ตั้งค่า -------------------- */
// ตั้งค่าบัญชีแอดมินที่นี่ (แก้ได้เอง)
$ADMIN_USERNAME        = 'hbr_web_admin';
// ทางที่ปลอดภัย: ใช้รหัสที่ "เข้ารหัสแล้ว" (bcrypt) ใส่ตรงนี้
$ADMIN_PASSWORD_HASH   = ''; // เช่น: '$2y$10$3QJ3e1...'; ถ้าเว้นว่างจะใช้ตรวจแบบ plaintext ด้านล่าง
// ทางลัด (สำหรับ dev): ตรวจแบบ plaintext ถ้า $ADMIN_PASSWORD_HASH เว้นว่าง
$ADMIN_PASSWORD_PLAIN  = 'rukayukigayforevery_888';

// (ออปชัน) จำกัด IP ที่เข้าได้; เว้น [] ถ้าไม่จำกัด
$IP_ALLOW = []; // ตัวอย่าง: ['203.0.113.10','2001:db8::1234']

/* -------------------- การป้องกันเบื้องต้น -------------------- */
// บล็อก IP ถ้าจำกัดไว้
if ($IP_ALLOW && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $IP_ALLOW, true)) {
  http_response_code(403); echo 'Forbidden'; exit;
}

// ถ้าเข้าสู่ระบบอยู่แล้ว ส่งไปหน้าแอดมิน
if (!empty($_SESSION['is_admin'])) { header('Location: <admin>admin_seraphs.php'); exit; }

// CSRF token
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

// brute-force guard (ง่าย ๆ)
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['login_last']     = $_SESSION['login_last']     ?? 0;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $error = 'เซสชันหมดอายุ ลองใหม่อีกครั้ง';
  } else {
    usleep(200000); // หน่วง 0.2 วินาที กันยิงรัว ๆ
    $now = time();
    if ($_SESSION['login_attempts'] >= 5 && ($now - $_SESSION['login_last'] < 60)) {
      $error = 'พยายามมากเกินไป โปรดลองอีกครั้งใน 1 นาที';
    } else {
      $u = trim($_POST['username'] ?? '');
      $p = $_POST['password'] ?? '';

      $okUser = hash_equals($ADMIN_USERNAME, $u);
      $okPass = false;
      if ($ADMIN_PASSWORD_HASH) {
        $okPass = password_verify($p, $ADMIN_PASSWORD_HASH);
      } else {
        // โหมดง่าย (เทียบ plaintext) — แนะนำให้เปลี่ยนเป็น HASH ในโปรดักชัน
        $okPass = hash_equals($ADMIN_PASSWORD_PLAIN, $p);
      }

      if ($okUser && $okPass) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $u;
        $_SESSION['login_attempts'] = 0;
        session_regenerate_id(true);
        header('Location: admin_seraphs.php'); exit;
      } else {
        $_SESSION['login_attempts']++;
        $_SESSION['login_last'] = $now;
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
      }
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <style>
    :root{
      --bg1:#fde1e6; --bg2:#e9f0ff; --card:rgba(255,255,255,.85); --line:#f0d1d8;
      --text:#111827; --muted:#6b7280; --primary:#e63950; --primary-2:#f43f5e; --ring:rgba(230,57,80,.25);
    }
    *{box-sizing:border-box} html,body{height:100%}
    body{
      margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:var(--text);
      background:
        radial-gradient(1200px 800px at 10% -10%, var(--bg1), transparent),
        radial-gradient(1200px 800px at 110% 110%, var(--bg2), transparent),
        linear-gradient(135deg,#fff,#f7fafc);
      display:grid; place-items:center;
    }
    .login-wrap{ width:100%; max-width:420px; padding:24px; }
    .login-card{
      background:var(--card); backdrop-filter:saturate(150%) blur(8px);
      border:2px solid var(--line); border-radius:20px;
      box-shadow:0 10px 30px rgba(0,0,0,.08); padding:28px 22px;
    }
    .brand{ display:flex; align-items:center; gap:10px; font-weight:900; font-size:22px; margin-bottom:18px; }
    .brand .logo{
      width:40px; height:40px; border-radius:10px; display:grid; place-items:center; font-size:20px;
      background:linear-gradient(135deg,#fff,#ffe0e5); border:1px solid #f0d1d8; box-shadow:0 6px 16px rgba(230,57,80,.15);
    }
    .alert{ background:#fff2f5; border:1px solid #ffd1dc; color:#b91c1c;
      padding:10px 12px; border-radius:10px; font-size:14px; margin-bottom:12px; }
    .field{ margin-bottom:12px; }
    .field label{ display:block; font-size:13px; color:var(--muted); margin:0 0 6px; }
    .control{ position:relative; }
    .control input{
      width:100%; height:44px; padding:0 40px 0 14px;
      border:1px solid #e5e7eb; border-radius:12px; outline:none; font-size:15px; background:#fff;
      transition:border-color .15s, box-shadow .15s;
    }
    .control input:focus{ border-color:var(--primary); box-shadow:0 0 0 4px var(--ring); }
    .toggle{ position:absolute; right:10px; top:50%; transform:translateY(-50%);
      background:transparent; border:0; cursor:pointer; font-size:16px; opacity:.75; }
    .btn-login{
      width:100%; height:46px; border:0; border-radius:12px; cursor:pointer;
      color:#fff; font-weight:800; letter-spacing:.2px; font-size:15px;
      background:linear-gradient(135deg, var(--primary), var(--primary-2));
      box-shadow: 0 10px 20px rgba(230,57,80,.25), inset 0 -2px 0 rgba(0,0,0,.08);
      margin-top:6px;
    }
    .btn-login:hover{ filter:brightness(1.04); }
    .meta{ display:flex; justify-content:space-between; align-items:center; margin-top:10px; font-size:12px; color:var(--muted); }
    .footer{ text-align:center; font-size:12px; color:#9ca3af; margin-top:18px; }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="brand">
        <div class="logo">⚔️</div><div>Seraph Admin</div>
      </div>

      <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">

        <div class="field">
          <label>ชื่อผู้ใช้</label>
          <div class="control"><input type="text" name="username" autocomplete="username" required></div>
        </div>

        <div class="field">
          <label>รหัสผ่าน</label>
          <div class="control">
            <input type="password" name="password" id="pw" autocomplete="current-password" required>
            <button type="button" class="toggle" aria-label="แสดง/ซ่อนรหัสผ่าน"
              onclick="(function(b){const i=document.getElementById('pw'); i.type=(i.type==='password'?'text':'password'); b.textContent=(i.type==='password'?'👁':'🙈');})(this)">👁</button>
          </div>
        </div>

        <button class="btn-login" type="submit">เข้าสู่ระบบ</button>
        <div class="meta">
          <span></span>
          <a href="index.html" style="text-decoration:none;color:#6b7280">← กลับหน้าแรก</a>
        </div>
        <?php if(!$ADMIN_PASSWORD_HASH): ?>
        <?php endif; ?>
      </form>

      <div class="footer">© <?= date('Y') ?> HBR Admin</div>
    </div>
  </div>
</body>
</html>
