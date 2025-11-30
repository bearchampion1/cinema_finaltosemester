<?php
require_once '../config.php';

if (!isset($_GET['pid'])) die("未指定 PaymentID");
$pid = $_GET['pid'];

// ① 讀取付款資料
$stmt = $pdo->prepare("SELECT * FROM `付款` WHERE `PaymentID` = ?");
$stmt->execute([$pid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("找不到付款：" . htmlspecialchars($pid));

// ② POST 更新付款資料
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sql = "UPDATE `付款`
            SET `OrderID`=?, 
                `付款方式`=?, 
                `付款金額`=?, 
                `付款狀態`=?, 
                `付款時間`=?
            WHERE `PaymentID`=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['OrderID'],
        $_POST['付款方式'],
        $_POST['付款金額'],
        $_POST['付款狀態'],
        $_POST['付款時間'] ?: null,
        $pid
    ]);

    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>修改付款資料</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>

<h2 class="mb-4">修改付款：<?= htmlspecialchars($pid) ?></h2>

<form method="post" class="row g-3">

  <div class="col-md-3">
    <label class="form-label">OrderID</label>
    <input class="form-control" name="OrderID" value="<?= htmlspecialchars($row['OrderID']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">付款方式</label>
    <input class="form-control" name="付款方式" value="<?= htmlspecialchars($row['付款方式']) ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">付款金額</label>
    <input class="form-control" type="number" step="0.01"
           name="付款金額" value="<?= htmlspecialchars($row['付款金額']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">付款狀態</label>
    <input class="form-control" name="付款狀態" value="<?= htmlspecialchars($row['付款狀態']) ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">付款時間</label>
    <input class="form-control" type="datetime-local" 
           name="付款時間"
           value="<?= $row['付款時間'] ? date('Y-m-d\TH:i', strtotime($row['付款時間'])) : '' ?>">
  </div>

  <div class="col-12 mt-3">
    <button class="btn btn-success px-4">儲存</button>
    <a class="btn btn-secondary px-4" href="index.php">返回</a>
  </div>

</form>

</body>
</html>
