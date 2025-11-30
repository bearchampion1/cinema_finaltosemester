<?php
require_once 'config.php';

// 接收參數（支援多種命名與 POST/GET）
$orderID = '';
$pickupCode = '';
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
}

// 再從 GET 取得（常見於 redirect 或 GET 表單）
if (empty($orderID)) {
  $orderID = pickFirst($_GET, ['id', 'order', 'OrderID', 'Order', 'OID']);
}
if (empty($pickupCode)) {
  $pickupCode = pickFirst($_GET, ['code', 'PickupCode', 'pickup', '取票代碼']);
}

// 如果已經有 OrderID 但沒有取票代碼，從資料庫查詢（容錯）
if (!empty($orderID) && empty($pickupCode)) {
  try {
    $s = $pdo->prepare("SELECT 取票代碼 FROM 訂單 WHERE OrderID = :id LIMIT 1");
    $s->execute([':id' => $orderID]);
    $c = $s->fetchColumn();
    if ($c) $pickupCode = $c;
  } catch (Exception $e) {
    // 忽略查詢錯誤，保持原本行為
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
  <h3 class="text-success mb-3">🎫 付款完成！</h3>
  <p>您的取票代碼：<strong><?= htmlspecialchars($pickupCode) ?></strong></p>
  <p>訂單編號：<?= htmlspecialchars($orderID) ?></p>

  <?php if (empty($pickupCode) || empty($orderID)): ?>
    <div class="mt-3 alert alert-warning">
      <strong>診斷資訊（暫時顯示）</strong>
      <pre style="text-align:left; white-space:pre-wrap;">GET: <?php echo htmlspecialchars(json_encode($_GET, JSON_UNESCAPED_UNICODE)); ?>
POST: <?php echo htmlspecialchars(json_encode($_POST, JSON_UNESCAPED_UNICODE)); ?></pre>
    </div>
  <?php endif; ?>
  <a href="order_check.php" class="btn btn-primary mt-3">查詢購票紀錄</a>
</div>
</body>
</html>
