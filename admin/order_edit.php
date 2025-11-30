<?php
require_once '../config.php';

if (!isset($_GET['id'])) die("未指定 OrderID");
$id = $_GET['id'];

// ① 查訂單
$stmt = $pdo->prepare("SELECT * FROM 訂單 WHERE OrderID = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die("找不到訂單：" . htmlspecialchars($id));

// ② 查付款（可能 1 筆或 0 筆）
$stmt2 = $pdo->prepare("SELECT * FROM 付款 WHERE OrderID = ?");
$stmt2->execute([$id]);
$payment = $stmt2->fetch(PDO::FETCH_ASSOC);

// ③ 處理訂單修改
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sql = "UPDATE 訂單 
            SET `取票代碼`=?, `總金額`=?, `訂購時間`=?, `ShowTimeID`=?
            WHERE `OrderID`=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['取票代碼'],
        $_POST['總金額'],
        $_POST['訂購時間'] ?: null,
        $_POST['ShowTimeID'],
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
<title>修改訂單</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>

<h2 class="mb-4">修改訂單：<?= htmlspecialchars($id) ?></h2>

<form method="post" class="row g-3">

  <div class="col-md-3">
    <label class="form-label">取票代碼</label>
    <input class="form-control" name="取票代碼" value="<?= htmlspecialchars($order['取票代碼']) ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">總金額</label>
    <input class="form-control" type="number" step="0.01" name="總金額" value="<?= htmlspecialchars($order['總金額']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">訂購時間</label>
    <input class="form-control" type="datetime-local" name="訂購時間"
           value="<?= $order['訂購時間'] ? date('Y-m-d\TH:i', strtotime($order['訂購時間'])) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">ShowTimeID</label>
    <input class="form-control" name="ShowTimeID" value="<?= htmlspecialchars($order['ShowTimeID']) ?>">
  </div>

  <!-- 分隔線 -->
  <div class="col-12"><hr></div>

  <!-- 付款資訊（只讀） -->
  <h4 class="text-primary">付款資訊（只讀）</h4>

  <?php if ($payment): ?>
      <div class="col-md-3">
        <label class="form-label">付款編號</label>
        <input class="form-control" value="<?= htmlspecialchars($payment['PaymentID']) ?>" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label">付款方式</label>
        <input class="form-control" value="<?= htmlspecialchars($payment['付款方式']) ?>" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label">付款金額</label>
        <input class="form-control" value="<?= htmlspecialchars($payment['付款金額']) ?>" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label">付款狀態</label>
        <input class="form-control" value="<?= htmlspecialchars($payment['付款狀態']) ?>" readonly>
      </div>

      <div class="col-md-4 mt-3">
        <label class="form-label">付款時間</label>
        <input class="form-control" value="<?= htmlspecialchars($payment['付款時間']) ?>" readonly>
      </div>

      <div class="col-md-12 mt-2">
        <a href="payment_edit.php?pid=<?= $payment['PaymentID'] ?>" class="btn btn-warning">
          修改付款資料
        </a>
      </div>

  <?php else: ?>
      <p class="text-danger">⚠ 此訂單尚無付款紀錄</p>
  <?php endif; ?>

  <div class="col-12 mt-4">
    <button class="btn btn-success px-4">儲存訂單</button>
    <a class="btn btn-secondary px-4" href="index.php">返回</a>
  </div>

</form>

</body>
</html>
