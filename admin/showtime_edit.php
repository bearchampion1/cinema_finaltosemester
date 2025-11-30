<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['id'])) die("未指定 ShowTimeID");
$id = $_GET['id'];

/* 讀取場次資料 */
$stmt = $pdo->prepare("SELECT * FROM `場次` WHERE `ShowTimeID` = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("找不到場次：" . htmlspecialchars($id));

/* 下拉資料：電影＆影廳 */
$movies = $pdo->query("SELECT `MovieID`,`片名` FROM `movie` ORDER BY `片名`")->fetchAll(PDO::FETCH_ASSOC);
$theaters = $pdo->query("SELECT `TheaterID`,`廳名` FROM `影廳` ORDER BY `TheaterID`")->fetchAll(PDO::FETCH_ASSOC);

/* 更新處理 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE `場次`
            SET `播放日期`=?, `開始時間`=?, `MovieID`=?, `TheaterID`=?, `可用座位數`=?
            WHERE `ShowTimeID` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['播放日期'],
        $_POST['開始時間'],
        $_POST['MovieID'],
        $_POST['TheaterID'],
        $_POST['可用座位數'],
        $id
    ]);

    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>修改場次</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>
<h2 class="mb-3">🎬 修改場次：<?= htmlspecialchars($id) ?></h2>

<!-- 🔥 自動產生座位按鈕 -->
<div class="mb-4">
  <a class="btn btn-primary"
     href="seat_auto_generate.php?id=<?= urlencode($id) ?>">
    🪄 自動產生座位
  </a>
</div>

<form method="post" class="row g-3">

  <div class="col-md-3">
    <label class="form-label">播放日期</label>
    <input class="form-control" type="date" name="播放日期"
           value="<?= htmlspecialchars($row['播放日期']) ?>" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">開始時間</label>
    <input class="form-control" type="time" name="開始時間"
           value="<?= htmlspecialchars($row['開始時間']) ?>" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">電影</label>
    <select class="form-select" name="MovieID">
      <?php foreach ($movies as $m): ?>
        <option value="<?= htmlspecialchars($m['MovieID']) ?>"
                <?= $m['MovieID']==$row['MovieID']?'selected':'' ?>>
          <?= htmlspecialchars($m['片名']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">影廳</label>
    <select class="form-select" name="TheaterID">
      <?php foreach ($theaters as $t): ?>
        <option value="<?= htmlspecialchars($t['TheaterID']) ?>"
                <?= $t['TheaterID']==$row['TheaterID']?'selected':'' ?>>
          <?= htmlspecialchars($t['廳名']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">可用座位數</label>
    <input class="form-control" type="number" name="可用座位數"
           value="<?= htmlspecialchars($row['可用座位數']) ?>" required>
  </div>

  <div class="col-12">
    <button class="btn btn-success">儲存</button>
    <a class="btn btn-secondary" href="index.php">返回</a>
  </div>

</form>

</body>
</html>
