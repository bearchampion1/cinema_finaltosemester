<?php
require_once 'config.php';

// 接收參數（支援多種命名與 POST/GET）
$orderID = '';
$pickupCode = '';
$customerName = '';
$customerEmail = '';
$verified = false;
$error = '';

function pickFirst(array $src, array $keys) {
  foreach ($keys as $k) {
    if (isset($src[$k]) && $src[$k] !== '') return $src[$k];
  }
  return '';
}

// 優先從 POST 取得（例如某些表單會以 POST 傳送）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $orderID = pickFirst($_POST, ['OrderID', 'order', 'id', 'Order', 'OID']);
  $pickupCode = pickFirst($_POST, ['PickupCode', 'pickup', 'code', '取票代碼']);
  $customerName = trim($_POST['customer_name'] ?? '');
  $customerEmail = trim($_POST['customer_email'] ?? '');
}

// 再從 GET 取得（常見於 redirect 或 GET 表單）
if (empty($orderID)) {
  $orderID = pickFirst($_GET, ['id', 'order', 'OrderID', 'Order', 'OID']);
}
if (empty($pickupCode)) {
  $pickupCode = pickFirst($_GET, ['code', 'PickupCode', 'pickup', '取票代碼']);
}
if (empty($customerName)) {
  $customerName = trim($_GET['customer_name'] ?? '');
}
if (empty($customerEmail)) {
  $customerEmail = trim($_GET['customer_email'] ?? '');
}

// 驗證訂單：需要 OrderID/PickupCode + 姓名 + Email
if (!empty($orderID) && !empty($pickupCode) && !empty($customerName) && !empty($customerEmail)) {
  try {
    $stmt = $pdo->prepare("
      SELECT `OrderID`, `取票代碼`, `顧客姓名`, `顧客Email` 
      FROM `訂單` 
      WHERE (`OrderID` = :oid OR `取票代碼` = :code) 
        AND `顧客姓名` = :name 
        AND `顧客Email` = :email
      LIMIT 1
    ");
    $stmt->execute([
      ':oid' => $orderID,
      ':code' => $pickupCode,
      ':name' => $customerName,
      ':email' => $customerEmail
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
      $verified = true;
      $orderID = $order['OrderID'];
      $pickupCode = $order['取票代碼'];
    } else {
      $error = '❌ 訂單資訊不符，請確認姓名、Email 與訂單編號或取票代碼是否正確';
    }
  } catch (Exception $e) {
    $error = '❌ 查詢失敗：' . $e->getMessage();
  }
}

?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>電子票</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container text-center">
  <?php if (!$verified): ?>
    <h3 class="mb-4">🎫 查詢電子票</h3>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="card mx-auto" style="max-width: 500px;">
      <div class="card-body">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">訂單編號或取票代碼 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="OrderID" placeholder="輸入訂單編號" value="<?= htmlspecialchars($orderID) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">姓名 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="customer_name" placeholder="請輸入您的姓名" value="<?= htmlspecialchars($customerName) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="customer_email" placeholder="example@email.com" value="<?= htmlspecialchars($customerEmail) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">查詢票券</button>
        </form>
      </div>
    </div>
    <a href="user_search.php" class="btn btn-secondary mt-3">返回首頁</a>
  <?php else: ?>
    <h3 class="text-success mb-3">🎫 付款完成！</h3>
    <p>您的取票代碼：<strong><?= htmlspecialchars($pickupCode) ?></strong></p>
    <p>訂單編號：<?= htmlspecialchars($orderID) ?></p>
    <a href="order_check.php" class="btn btn-primary mt-3">查詢購票紀錄</a>
  <?php endif; ?>
</div>
</body>
</html>
