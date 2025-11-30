<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

$order = null;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = trim($_POST["code"]);

    if ($code !== "") {
        $sql = "SELECT o.OrderID, o.取票代碼, o.總金額, p.付款狀態 AS 付款狀態, o.訂購時間,
                 s.播放日期, s.開始時間, m.片名, t.廳名
          FROM 訂單 o
          LEFT JOIN 付款 p ON o.OrderID = p.OrderID
          JOIN 場次 s ON o.ShowTimeID = s.ShowTimeID
          JOIN movie m ON s.MovieID = m.MovieID
          JOIN 影廳 t ON s.TheaterID = t.TheaterID
          WHERE o.取票代碼 = :code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':code' => $code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error = "❌ 查無此取票代碼，請確認輸入是否正確。";
        }
    } else {
        $error = "⚠️ 請輸入取票代碼。";
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
    <a class="navbar-brand fw-bold" href="index.php">🎬 Cinema System</a>
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
      <div class="input-group">
        <input type="text" name="code" class="form-control" placeholder="請輸入取票代碼" required>
        <button class="btn btn-primary">查詢</button>
      </div>
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
