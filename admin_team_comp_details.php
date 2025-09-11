<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();

/* 1) รับและตรวจสอบ comp_id */
$comp_id = (int)($_GET['comp_id'] ?? 0);
if ($comp_id <= 0) {
  exit('Missing comp_id. โปรดเปิดหน้านี้ผ่านลิงก์จากรายการทีม (admin_team_comps.php).');
}
$chk = $pdo->prepare("SELECT id,title FROM team_comps WHERE id=?");
$chk->execute([$comp_id]);
$comp = $chk->fetch(PDO::FETCH_ASSOC);
if (!$comp) {
  exit('ไม่พบทีมที่ comp_id='.$comp_id.' ในตาราง team_comps');
}

/* 2) โหลดรายการ Seraphs สำหรับ dropdown */
$ser = $pdo->query("SELECT id,name_th FROM seraphs ORDER BY rarity DESC, name_th ASC")
           ->fetchAll(PDO::FETCH_KEY_PAIR);

/* 3) บันทึก (CREATE/UPDATE สั้นๆ: เฉพาะ CREATE ในไฟล์นี้) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $seraph_id = (int)($_POST['seraph_id'] ?? 0);
  $desc      = trim($_POST['description'] ?? '');
  $order_no  = (int)($_POST['order_no'] ?? 0);

  // ตรวจว่ามี seraph จริง
  if ($seraph_id <= 0 || !array_key_exists($seraph_id, $ser)) {
    $err = 'Seraph ไม่ถูกต้อง';
  } elseif ($desc === '') {
    $err = 'กรุณากรอกรายละเอียด';
  } else {
    try {
      $pdo->beginTransaction();
      $ins = $pdo->prepare("INSERT INTO team_comp_details (comp_id, seraph_id, description, order_no)
                            VALUES (?,?,?,?)");
      $ins->execute([$comp_id, $seraph_id, $desc, $order_no]);

      // บันทึก swaps (ถ้ามี)
      $detail_id = (int)$pdo->lastInsertId();
      if (!empty($_POST['swaps']) && is_array($_POST['swaps'])) {
        $insSw = $pdo->prepare("INSERT INTO team_comp_detail_swaps (detail_id, seraph_id) VALUES (?,?)");
        foreach ($_POST['swaps'] as $sid) {
          $sid = (int)$sid;
          if ($sid > 0 && array_key_exists($sid, $ser)) {
            $insSw->execute([$detail_id, $sid]);
          }
        }
      }
      $pdo->commit();
      header('Location: admin_team_comp_details.php?comp_id='.$comp_id.'&ok=1'); exit;
    } catch (Throwable $e) {
      $pdo->rollBack();
      $err = 'บันทึกล้มเหลว: '.$e->getMessage();
    }
  }
}

/* 4) ดึงรายการ details ทั้งหมดของทีมนี้ */
$q = $pdo->prepare("SELECT d.id, d.order_no, d.description, s.name_th
                    FROM team_comp_details d
                    JOIN seraphs s ON s.id = d.seraph_id
                    WHERE d.comp_id=?
                    ORDER BY d.order_no ASC, d.id ASC");
$q->execute([$comp_id]);
$list = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<body class="sb-hover">
  <main class="admin-main">
    <h1>รายละเอียดทีม #<?= $comp_id ?> — <?= htmlspecialchars($comp['title']) ?></h1>
    <?php if(!empty($_GET['ok'])): ?>
      <div class="msg">บันทึกสำเร็จ</div>
    <?php endif; ?>
    <?php if(!empty($err)): ?>
      <div class="msg err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- 5) ฟอร์มสร้างแถว detail: fix action ให้พก comp_id เสมอ -->
    <form method="post" action="admin_team_comp_details.php?comp_id=<?= $comp_id ?>">
      <div class="grid">
        <div>
          <label>Seraph (หลัก)</label>
          <select name="seraph_id" required>
            <option value="">-- เลือก --</option>
            <?php foreach($ser as $sid=>$nm): ?>
              <option value="<?= $sid ?>"><?= htmlspecialchars($nm) ?></option>
            <?php endforeach; ?>
          </select>

          <label>คำอธิบาย</label>
          <textarea name="description" required placeholder="พิมพ์รายละเอียดตามภาพ"></textarea>

          <label>ลำดับแสดง</label>
          <input type="number" name="order_no" value="0">
        </div>

        <div>
          <label>入れ替え候補 (เลือกได้หลายตัว)</label>
          <div style="max-height:300px;overflow:auto;border:1px solid #eee;border-radius:10px;padding:8px">
            <?php foreach($ser as $sid=>$nm): ?>
              <label class="pick-opt" style="margin:4px 6px 4px 0;display:inline-flex;gap:6px;align-items:center">
                <input type="checkbox" name="swaps[]" value="<?= $sid ?>">
                <span><?= htmlspecialchars($nm) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button class="btn" type="submit">บันทึกรายการ</button>
          <a class="btn" href="admin_team_comps.php">← กลับรายการทีม</a>
        </div>
      </div>
    </form>

    <h2 style="margin-top:18px">รายการที่มี</h2>
    <table>
      <tr><th>ลำดับ</th><th>Seraph</th><th>คำอธิบาย</th><th>จัดการ</th></tr>
      <?php foreach($list as $it): ?>
        <tr>
          <td><?= $it['order_no'] ?></td>
          <td><?= htmlspecialchars($it['name_th']) ?></td>
          <td><?= nl2br(htmlspecialchars(mb_strimwidth($it['description'],0,160,'…'))) ?></td>
          <td>
            <a class="btn-edit" href="admin_team_comp_details_edit.php?comp_id=<?= $comp_id ?>&id=<?= $it['id'] ?>">แก้ไข</a>
            <a class="btn-del" href="admin_team_comp_details_delete.php?comp_id=<?= $comp_id ?>&id=<?= $it['id'] ?>" onclick="return confirm('ลบรายการนี้?')">ลบ</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </main>
</body>
