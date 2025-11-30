<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("❌ 無效的進入方式");

$showtimeID = $_POST['ShowTimeID'];
$total = $_POST['Total'];
$seats = json_decode($_POST['Seats'], true);

if (!$showtimeID || !$total) die("❌ 訂單資料不完整");

// 建立訂單：產生不會被截斷且唯一的 OrderID（格式：O + 9 字元）
$pickup = strtoupper(substr(md5(uniqid()), 0, 6));
do {
  $orderID = 'O' . strtoupper(substr(md5(uniqid('', true)), 0, 9));
  $chk = $pdo->prepare("SELECT 1 FROM 訂單 WHERE OrderID = :id");
  $chk->execute([':id' => $orderID]);
  $exists = $chk->fetchColumn();
} while ($exists);

$sql = "INSERT INTO 訂單 (OrderID, 取票代碼, 總金額, 付款狀態, 訂購時間, ShowTimeID)
        VALUES (:oid, :code, :total, '未付款', NOW(), :sid)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':oid' => $orderID, ':code' => $pickup, ':total' => $total, ':sid' => $showtimeID]);

?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>付款頁面</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container text-center">
  <h3 class="text-primary mb-3">💳 模擬付款</h3>
  <p>應付金額：<strong><?= $total ?></strong> 元</p>

  <form method="post" action="ticket.php">
    <input type="hidden" name="OrderID" value="<?= htmlspecialchars($orderID) ?>">
    <input type="hidden" name="PickupCode" value="<?= htmlspecialchars($pickup) ?>">
    <button class="btn btn-success w-50">付款完成</button>
  </form>

  <a href="user_search.php" class="btn btn-outline-secondary mt-3">取消購票</a>
</div>
</body>
</html>
<?php
// End of payment.php