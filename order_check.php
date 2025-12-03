<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

$order = null;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = trim($_POST["code"]);
    $customerName = trim($_POST["customer_name"] ?? "");
    $customerEmail = trim($_POST["customer_email"] ?? "");

    if ($code !== "" && $customerName !== "" && $customerEmail !== "") {
        $sql = "SELECT o.OrderID, o.取票代碼, o.總金額, o.顧客姓名, o.顧客Email, p.付款狀態 AS 付款狀態, o.訂購時間,
                 s.播放日期, s.開始時間, m.片名, t.廳名
          FROM 訂單 o
          LEFT JOIN 付款 p ON o.OrderID = p.OrderID
          JOIN 場次 s ON o.ShowTimeID = s.ShowTimeID
          JOIN movie m ON s.MovieID = m.MovieID
          JOIN 影廳 t ON s.TheaterID = t.TheaterID
          WHERE o.取票代碼 = :code AND o.顧客姓名 = :name AND o.顧客Email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $code,
            ':name' => $customerName,
            ':email' => $customerEmail
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error = "❌ 訂單資訊不符，請確認取票代碼、姓名與 Email 是否正確。";
        }
    } else {
        $error = "⚠️ 請填寫完整資訊（取票代碼、姓名、Email）。";
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🎟 購票紀錄查詢</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: "微軟正黑體"; }
.ticket-box {
  max-width: 600px; margin: 40px auto;
  background: white; border-radius: 10px; padding: 25px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<!-- ✅ 導覽列 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎬好秀電影院</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">🏠 首頁</a></li>
        <li class="nav-item"><a class="nav-link" href="user_search.php">🎬 查詢場次</a></li>
        <li class="nav-item"><a class="nav-link active" href="order_check.php">🎟 購票紀錄</a></li>
        <li class="nav-item"><a class="nav-link" href="#">📞 聯絡我們</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="ticket-box">
    <h3 class="text-center text-primary mb-3">🎟 查詢購票紀錄</h3>

    <form method="post" class="mb-4">
      <div class="mb-3">
        <label class="form-label">取票代碼 <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control" placeholder="請輸入取票代碼" required>
      </div>
      <div class="mb-3">
        <label class="form-label">姓名 <span class="text-danger">*</span></label>
        <input type="text" name="customer_name" class="form-control" placeholder="請輸入訂購人姓名" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="customer_email" class="form-control" placeholder="請輸入訂購人 Email" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">查詢訂單</button>
    </form>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title text-primary"><?= htmlspecialchars($order['片名']) ?></h5>
          <p class="card-text">
            🏢 <?= htmlspecialchars($order['廳名']) ?><br>
            📅 <?= htmlspecialchars($order['播放日期']) ?><br>
            ⏰ <?= htmlspecialchars($order['開始時間']) ?><br>
            💰 金額：<?= htmlspecialchars($order['總金額']) ?> 元<br>
            📄 狀態：<?= htmlspecialchars($order['付款狀態']) ?><br>
            📅 訂購時間：<?= htmlspecialchars($order['訂購時間']) ?>
          </p>

          <?php if ($order['付款狀態'] === '已付款'): ?>
            <a href="ticket.php?order=<?= urlencode($order['OrderID']) ?>" 
               class="btn btn-success w-100">📲 查看電子票</a>
          <?php else: ?>
            <a href="payment.php?order=<?= urlencode($order['OrderID']) ?>" 
               class="btn btn-warning w-100">💳 前往付款</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
