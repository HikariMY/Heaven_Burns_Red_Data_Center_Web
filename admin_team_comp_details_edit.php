<?php
require_once 'auth_guard.php';
require_once 'db.php';
$pdo = (new DB())->connect();
$comp_id=(int)($_GET['comp_id']??0);
$id=(int)($_GET['id']??0);

$ser = $pdo->query("SELECT id,name_th FROM seraphs ORDER BY rarity DESC,name_th ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

$det = $pdo->prepare("SELECT * FROM team_comp_details WHERE id=? AND comp_id=?");
$det->execute([$id,$comp_id]); $row=$det->fetch(PDO::FETCH_ASSOC);

$sw = $pdo->prepare("SELECT seraph_id FROM team_comp_detail_swaps WHERE detail_id=?");
$sw->execute([$id]); $have = array_column($sw->fetchAll(PDO::FETCH_ASSOC),'seraph_id');
?>
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_theme.css">
<body class="sb-hover">
  <main class="admin-main">
    <h1>แก้ไขรายละเอียดทีม #<?= $comp_id ?></h1>
    <form method="post" action="admin_team_comp_details.php?comp_id=<?= $comp_id ?>">
      <input type="hidden" name="detail_id" value="<?= $row['id'] ?>">
      <div class="grid">
        <div>
          <label>Seraph (หลัก)</label>
          <select name="seraph_id" required>
            <?php foreach($ser as $sid=>$nm): $sel=$sid==($row['seraph_id']??0)?'selected':''; ?>
              <option value="<?= $sid ?>" <?= $sel ?>><?= htmlspecialchars($nm) ?></option>
            <?php endforeach; ?>
          </select>
          <label>คำอธิบาย</label>
          <textarea name="description" required><?= htmlspecialchars($row['description'] ?? '') ?></textarea>
          <label>ลำดับแสดง</label>
          <input type="number" name="order_no" value="<?= (int)($row['order_no']??0) ?>">
        </div>
        <div>
          <label>入れ替え候補</label>
          <div style="max-height:300px;overflow:auto;border:1px solid #eee;border-radius:10px;padding:8px">
            <?php foreach($ser as $sid=>$nm): $ck=in_array($sid,$have)?'checked':''; ?>
              <label class="pick-opt" style="margin:4px 6px 4px 0">
                <input type="checkbox" name="swaps[]" value="<?= $sid ?>" <?= $ck ?>>
                <span class="icon-wrap"></span><span><?= htmlspecialchars($nm) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button class="btn" type="submit">บันทึก</button>
        </div>
      </div>
    </form>
  </main>
</body>
