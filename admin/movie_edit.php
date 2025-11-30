<?php
require_once '../config.php';
if (!isset($_GET['id'])) die("未指定 MovieID");
$id = $_GET['id'];

/* 讀取 */
$stmt = $pdo->prepare("SELECT * FROM `movie` WHERE `MovieID` = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("找不到電影：".htmlspecialchars($id));

/* 更新 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE `movie` 
            SET `片名`=?, `片長`=?, `類型`=?, `分級`=?, `語言`=?, `上映日`=?, `IMG_URL`=? 
            WHERE `MovieID` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['片名'], $_POST['片長'], $_POST['類型'],
        $_POST['分級'], $_POST['語言'], $_POST['上映日'] ?: null,
        $_POST['IMG_URL'] ?? null,
        $id
    ]);
    header("Location: index.php");
    exit;
}
?>
<!doctype html><html lang="zh-Hant"><head>
<meta charset="utf-8"><title>修改電影</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<?php require_once __DIR__ . '/header.php'; ?>
<h2>修改電影：<?= htmlspecialchars($id) ?></h2>
<form method="post" class="row g-3">
  <div class="col-md-4">
    <label class="form-label">片名</label>
    <input class="form-control" name="片名" value="<?= htmlspecialchars($row['片名']) ?>" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">片長(分)</label>
    <input class="form-control" type="number" name="片長" value="<?= htmlspecialchars($row['片長']) ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">類型</label>
    <input class="form-control" name="類型" value="<?= htmlspecialchars($row['類型']) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">分級</label>
    <input class="form-control" name="分級" value="<?= htmlspecialchars($row['分級']) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">語言</label>
    <input class="form-control" name="語言" value="<?= htmlspecialchars($row['語言']) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">上映日</label>
    <input class="form-control" type="date" name="上映日" value="<?= htmlspecialchars($row['上映日']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">海報圖片 URL</label>
    <input class="form-control" type="url" name="IMG_URL" value="<?= htmlspecialchars($row['IMG_URL'] ?? '') ?>" placeholder="https://...">
  </div>
  <div class="col-12">
    <button class="btn btn-success">儲存</button>
    <a class="btn btn-secondary" href="index.php">返回</a>
  </div>
</form>
</body></html>
