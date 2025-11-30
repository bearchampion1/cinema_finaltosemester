<?php
require_once '../config.php';
if (!isset($_GET['id'])) die("未指定 TheaterID");
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM `影廳` WHERE `TheaterID` = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("找不到影廳：" . htmlspecialchars($id));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE `影廳` 
            SET `廳名`=?, `類型`=?, `容量`=?, `樓層`=? 
            WHERE `TheaterID`=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['廳名'], $_POST['類型'], $_POST['容量'], $_POST['樓層'], $id
    ]);
    header("Location: index.php");
    exit;
}
?>
<!doctype html><html lang="zh-Hant"><head>
<meta charset="utf-8"><title>修改影廳</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<?php require_once __DIR__ . '/header.php'; ?>
<h2>修改影廳：<?= htmlspecialchars($id) ?></h2>
<form method="post" class="row g-3">
  <div class="col-md-4"><label class="form-label">廳名</label>
    <input class="form-control" name="廳名" value="<?= htmlspecialchars($row['廳名']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">類型</label>
    <input class="form-control" name="類型" value="<?= htmlspecialchars($row['類型']) ?>"></div>
  <div class="col-md-2"><label class="form-label">容量</label>
    <input class="form-control" type="number" name="容量" value="<?= htmlspecialchars($row['容量']) ?>"></div>
  <div class="col-md-2"><label class="form-label">樓層</label>
    <input class="form-control" name="樓層" value="<?= htmlspecialchars($row['樓層']) ?>"></div>
  <div class="col-12">
    <button class="btn btn-success">儲存</button>
    <a class="btn btn-secondary" href="index.php">返回</a>
  </div>
</form>
</body></html>
