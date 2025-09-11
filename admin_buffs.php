<?php
// admin_buffs.php — FULL COPY-PASTE
require_once 'auth_guard.php'; // ตรวจสิทธิ์แอดมิน
require_once 'db.php';
$pdo = (new DB())->connect();

/* ====== อัปโหลด ====== */
$uploadDir = __DIR__ . '/uploads/buffs/';
$uploadUrl = 'uploads/buffs/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

/* ====== สร้าง/อัปเดต ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_buff'])) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

  $name_th = trim($_POST['name_th'] ?? '');
  $type = $_POST['type'] ?? 'buff';
  $category = trim($_POST['category'] ?? '');
  $duration_kind = $_POST['duration_kind'] ?? 'turn';
  $duration_value = ($_POST['duration_value'] === '' ? null : (int)$_POST['duration_value']);
  $trigger = trim($_POST['trigger'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $tags = trim($_POST['tags'] ?? '');

  // ไอคอน (อัปโหลดถ้ามี)
  $iconPath = null;
  if (!empty($_FILES['icon']['name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
    $fname = 'buff_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($_FILES['icon']['tmp_name'], $uploadDir . $fname)) {
      $iconPath = $uploadUrl . $fname;
    }
  }

  if ($id > 0) {
    // update
    if ($iconPath) {
      $stmt = $pdo->prepare("UPDATE buffs SET name_th=?, type=?, category=?, duration_kind=?, duration_value=?, `trigger`=?, description=?, tags=?, icon=? WHERE id=?");
      $ok = $stmt->execute([$name_th,$type,$category,$duration_kind,$duration_value,$trigger,$description,$tags,$iconPath,$id]);
    } else {
      $stmt = $pdo->prepare("UPDATE buffs SET name_th=?, type=?, category=?, duration_kind=?, duration_value=?, `trigger`=?, description=?, tags=? WHERE id=?");
      $ok = $stmt->execute([$name_th,$type,$category,$duration_kind,$duration_value,$trigger,$description,$tags,$id]);
    }
  } else {
    // insert
    $stmt = $pdo->prepare("INSERT INTO buffs (name_th,icon,type,category,duration_kind,duration_value,`trigger`,description,tags) VALUES (?,?,?,?,?,?,?,?,?)");
    $ok = $stmt->execute([$name_th,$iconPath,$type,$category,$duration_kind,$duration_value,$trigger,$description,$tags]);
    $id = $ok ? (int)$pdo->lastInsertId() : 0;
  }

  // effects
  if ($id > 0) {
    if (!empty($_POST['effect_remove'])) {
      foreach ($_POST['effect_remove'] as $rid) {
        $rid = (int)$rid;
        $pdo->prepare("DELETE FROM buff_effects WHERE id=? AND buff_id=?")->execute([$rid,$id]);
      }
    }
    $eff_id   = $_POST['effect_id'] ?? [];
    $eff_t    = $_POST['effect_title'] ?? [];
    $eff_v    = $_POST['effect_value'] ?? [];
    $eff_note = $_POST['effect_note'] ?? [];
    $eff_ord  = $_POST['effect_order'] ?? [];
    $n = max(count($eff_t), count($eff_v), count($eff_note), count($eff_ord));
    for ($i=0; $i<$n; $i++) {
      $eid  = isset($eff_id[$i]) ? (int)$eff_id[$i] : 0;
      $t    = trim($eff_t[$i] ?? '');
      $v    = trim($eff_v[$i] ?? '');
      $note = trim($eff_note[$i] ?? '');
      $ord  = (int)($eff_ord[$i] ?? 0);

      if ($eid === 0 && $t==='' && $v==='' && $note==='') continue;

      if ($eid > 0) {
        $pdo->prepare("UPDATE buff_effects SET title=?, `value`=?, note=?, order_no=? WHERE id=? AND buff_id=?")
            ->execute([$t,$v,$note,$ord,$eid,$id]);
      } else {
        $pdo->prepare("INSERT INTO buff_effects (buff_id,title,`value`,note,order_no) VALUES (?,?,?,?,?)")
            ->execute([$id,$t,$v,$note,$ord]);
      }
    }
  }

  header("Location: admin_buffs.php?msg=".($ok?'saved':'error')."&edit=".$id);
  exit;
}

/* ====== ดึงข้อมูลเพื่อแก้ไข ====== */
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;
$effects = [];
if ($edit_id > 0) {
  $st = $pdo->prepare("SELECT * FROM buffs WHERE id=?");
  $st->execute([$edit_id]);
  $edit = $st->fetch();

  $se = $pdo->prepare("SELECT * FROM buff_effects WHERE buff_id=? ORDER BY order_no, id");
  $se->execute([$edit_id]);
  $effects = $se->fetchAll();
}

/* ====== ค้นหา / รายการ ====== */
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$dur = $_GET['dur'] ?? '';

$w=[]; $p=[];
if ($q!=='') { $w[]="(name_th LIKE ? OR category LIKE ? OR tags LIKE ? OR description LIKE ?)"; $like="%$q%"; array_push($p,$like,$like,$like,$like); }
if ($type!=='') { $w[]="type=?"; $p[]=$type; }
if ($category!=='') { $w[]="category=?"; $p[]=$category; }
if ($dur!=='') { $w[]="duration_kind=?"; $p[]=$dur; }

$sql = "SELECT * FROM buffs";
if ($w) $sql .= " WHERE ".implode(" AND ",$w);
$sql .= " ORDER BY id DESC";

$list = $pdo->prepare($sql);
$list->execute($p);
$rows = $list->fetchAll();

// categories สำหรับ select
$catRows = $pdo->query("SELECT DISTINCT category FROM buffs WHERE category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin • บัพ/ดีบัพ</title>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<style>
.container{max-width:1100px;margin:16px auto;padding:0 16px}
.toolbar{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px}
.chip{padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:999px;background:#fff;font-weight:800;cursor:pointer}
.chip.active{border-color:#e63950;box-shadow:0 3px 10px rgba(230,57,80,.12)}
.table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #f1f5f9;border-radius:12px;overflow:hidden}
th,td{padding:10px;border-bottom:1px solid #f8fafc;text-align:left}
th{background:#ffe4ea}
.actions a{margin-right:8px}
.form-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;box-shadow:0 6px 18px rgba(17,17,26,.08)}
.form-row{display:grid;grid-template-columns:180px 1fr;gap:10px;margin-bottom:10px}
input[type=text], input[type=number], textarea, select{width:100%;padding:8px;border:1.5px solid #e5e7eb;border-radius:10px}
.icon-preview{width:72px;height:72px;border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fafafa}
.icon-preview img{width:100%;height:100%;object-fit:cover}
.effects-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #f1f5f9;border-radius:12px;overflow:hidden;margin-top:8px}
.effects-table th,.effects-table td{padding:8px;border-bottom:1px solid #f8fafc}
.add-row{margin-top:8px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;font-weight:800;cursor:pointer}
.btn-primary{background:#e63950;color:#fff;border-color:#e63950}
.btn-danger{background:#fee2e2;border-color:#fecaca;color:#b91c1c}
.admin-sidebar .side-item.active{background:#ffe4ea;border-color:#fecdd3}
</style>
</head>
<body>
  <aside class="admin-sidebar">
    <div class="side-head">HBR Admin</div>
    <div class="side-list">
      <a class="side-item" href="admin_seraphs.php"><span class="ico">👤</span><span class="label">Seraphs</span></a>
      <a class="side-item" href="admin_seraph_skills.php"><span class="ico">✨</span><span class="label">Skills</span></a>
      <a class="side-item" href="admin_events.php"><span class="ico">🗓️</span><span class="label">Events</span></a>
      <a class="side-item" href="admin_team_comps.php"><span class="ico">👥</span><span class="label">Team Comp</span></a>
      <a class="side-item" href="admin_accessories.php"><span class="ico">💍</span><span class="label">Accessories</span></a>
      <a class="side-item" href="admin_accessory_types.php"><span class="ico">🗂️</span><span class="label">Accessory Types</span></a>
      <a class="side-item active" href="admin_buffs.php"><span class="ico">✨</span><span class="label">Buffs/Debuffs</span></a>
      <a class="side-item" href="logout.php"><span class="ico">⏻</span><span class="label">Logout</span></a>
    </div>
  </aside>

  <main class="container">
    <h1 class="page-title">✨ บัพ/ดีบัพ</h1>

    <!-- ฟิลเตอร์ -->
    <form class="toolbar" method="get" action="">
      <input type="text" name="q" placeholder="ค้นหาชื่อ/คำอธิบาย/แท็ก..." value="<?= htmlspecialchars($q) ?>">
      <select name="type">
        <option value="">ประเภท: ทั้งหมด</option>
        <option value="buff"   <?= $type==='buff'?'selected':'' ?>>บัพ</option>
        <option value="debuff" <?= $type==='debuff'?'selected':'' ?>>ดีบัพ</option>
      </select>
      <select name="category">
        <option value="">หมวด: ทั้งหมด</option>
        <?php foreach ($catRows as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $category===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="dur">
        <option value="">ระยะ: ทั้งหมด</option>
        <option value="turn"      <?= $dur==='turn'?'selected':'' ?>>เทิร์น</option>
        <option value="count"     <?= $dur==='count'?'selected':'' ?>>ครั้ง</option>
        <option value="seconds"   <?= $dur==='seconds'?'selected':'' ?>>วินาที</option>
        <option value="permanent" <?= $dur==='permanent'?'selected':'' ?>>ถาวร</option>
      </select>
      <button class="btn">กรอง</button>
      <a class="btn" href="admin_buffs.php">ล้าง</a>
    </form>

    <!-- ตารางรายการ -->
    <div class="table-wrap" style="overflow:auto;margin-bottom:18px">
      <table class="table">
        <thead>
          <tr>
            <th style="width:60px">ไอคอน</th>
            <th>ชื่อ</th>
            <th>ประเภท</th>
            <th>หมวด</th>
            <th>ระยะ</th>
            <th style="width:110px">จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="muted">ยังไม่มีข้อมูล</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?php if (!empty($r['icon'])): ?><img src="<?= htmlspecialchars($r['icon']) ?>" alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover;border:1px solid #eee"><?php endif; ?></td>
            <td><strong><?= htmlspecialchars($r['name_th']) ?></strong></td>
            <td><?= ($r['type']==='debuff'?'ดีบัพ':'บัพ') ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td><?= htmlspecialchars($r['duration_kind']) ?><?= ($r['duration_value']!==null ? ' · '.(int)$r['duration_value'] : '') ?></td>
            <td class="actions">
              <a class="btn" href="admin_buffs.php?edit=<?= (int)$r['id'] ?>">แก้ไข</a>
              <a class="btn btn-danger" onclick="return confirm('ลบรายการนี้?')" href="admin_buffs_delete.php?id=<?= (int)$r['id'] ?>">ลบ</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ฟอร์ม สร้าง/แก้ไข -->
    <h2><?= $edit ? 'แก้ไข' : 'เพิ่มใหม่' ?></h2>
    <form class="form-card" method="post" action="" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">

      <div class="form-row">
        <label>ชื่อ (TH)*</label>
        <input type="text" name="name_th" required value="<?= htmlspecialchars($edit['name_th'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label>ประเภท*</label>
        <select name="type" required>
          <option value="buff"   <?= (isset($edit['type']) && $edit['type']==='buff')?'selected':'' ?>>บัพ</option>
          <option value="debuff" <?= (isset($edit['type']) && $edit['type']==='debuff')?'selected':'' ?>>ดีบัพ</option>
        </select>
      </div>

      <div class="form-row">
        <label>หมวด</label>
        <input type="text" name="category" value="<?= htmlspecialchars($edit['category'] ?? '') ?>" placeholder="เช่น บูสต์ดาเมจสกิล / ลดพลังป้องกัน">
      </div>

      <div class="form-row">
        <label>ระยะเวลา</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <?php $dk = $edit['duration_kind'] ?? 'turn'; ?>
          <select name="duration_kind">
            <option value="turn"      <?= $dk==='turn'?'selected':'' ?>>เทิร์น</option>
            <option value="count"     <?= $dk==='count'?'selected':'' ?>>ครั้ง</option>
            <option value="seconds"   <?= $dk==='seconds'?'selected':'' ?>>วินาที</option>
            <option value="permanent" <?= $dk==='permanent'?'selected':'' ?>>ถาวร</option>
          </select>
          <input type="number" name="duration_value" min="0" placeholder="ค่า (เว้นว่างได้)" value="<?= htmlspecialchars($edit['duration_value'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <label>Trigger/คีย์เวิร์ด</label>
        <input type="text" name="trigger" value="<?= htmlspecialchars($edit['trigger'] ?? '') ?>" placeholder="เช่น เทิร์น / ครั้ง / เงื่อนไขพิเศษ">
      </div>

      <div class="form-row">
        <label>คำอธิบาย</label>
        <textarea name="description" rows="4" placeholder="รายละเอียดผลโดยรวม"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <label>แท็ก</label>
        <input type="text" name="tags" value="<?= htmlspecialchars($edit['tags'] ?? '') ?>" placeholder="คั่นด้วย , เช่น atk,team,up">
      </div>

      <div class="form-row">
        <label>ไอคอน</label>
        <div>
          <div class="icon-preview"><?php if (!empty($edit['icon'])): ?><img src="<?= htmlspecialchars($edit['icon']) ?>" alt=""><?php endif; ?></div>
          <div style="margin-top:6px"><input type="file" name="icon" accept="image/*"></div>
          <small class="muted">ถ้าไม่อัปโหลด จะใช้รูปเดิม</small>
        </div>
      </div>

      <!-- Effects -->
      <h3 style="margin:12px 0 6px">รายละเอียดผล (Effects)</h3>
      <table class="effects-table" id="effTable">
        <thead>
          <tr>
            <th style="width:34px">ลบ</th>
            <th>ชื่อ</th>
            <th>ค่า</th>
            <th>หมายเหตุ</th>
            <th style="width:70px">ลำดับ</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($effects): foreach ($effects as $e): ?>
          <tr>
            <td><input type="checkbox" name="effect_remove[]" value="<?= (int)$e['id'] ?>"></td>
            <td>
              <input type="hidden" name="effect_id[]" value="<?= (int)$e['id'] ?>">
              <input type="text" name="effect_title[]" value="<?= htmlspecialchars($e['title']) ?>">
            </td>
            <td><input type="text" name="effect_value[]" value="<?= htmlspecialchars($e['value']) ?>"></td>
            <td><input type="text" name="effect_note[]" value="<?= htmlspecialchars($e['note']) ?>"></td>
            <td><input type="number" name="effect_order[]" value="<?= (int)$e['order_no'] ?>"></td>
          </tr>
          <?php endforeach; endif; ?>
          <!-- แถวเปล่าสำหรับเพิ่มใหม่ -->
          <tr>
            <td></td>
            <td><input type="hidden" name="effect_id[]" value="0"><input type="text" name="effect_title[]" placeholder="เช่น พลังโจมตี"></td>
            <td><input type="text" name="effect_value[]" placeholder="+20%"></td>
            <td><input type="text" name="effect_note[]" placeholder="ทีมทั้งหมด"></td>
            <td><input type="number" name="effect_order[]" value="1"></td>
          </tr>
        </tbody>
      </table>

      <div class="add-row">
        <button type="button" class="btn" onclick="addEffRow()">+ เพิ่มแถว</button>
      </div>

      <div style="margin-top:12px">
        <button class="btn btn-primary" type="submit" name="save_buff" value="1">บันทึก</button>
        <?php if ($edit): ?><a class="btn" href="admin_buffs.php">ยกเลิก</a><?php endif; ?>
      </div>
    </form>
  </main>

  <!-- ถ้ามีสคริปต์ลูกเล่น ให้โหลดไว้ท้ายไฟล์ -->
  <script>
    function addEffRow(){
      const tr=document.createElement('tr');
      tr.innerHTML=`
        <td></td>
        <td><input type="hidden" name="effect_id[]" value="0"><input type="text" name="effect_title[]" placeholder="ชื่อผล"></td>
        <td><input type="text" name="effect_value[]" placeholder="ค่า/เปอร์เซ็นต์"></td>
        <td><input type="text" name="effect_note[]" placeholder="หมายเหตุ"></td>
        <td><input type="number" name="effect_order[]" value="1"></td>
      `;
      document.querySelector('#effTable tbody').appendChild(tr);
    }
  </script>
  <script src="admin_buffs.js"></script>
</body>
</html>
