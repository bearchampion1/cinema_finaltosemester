<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

// 查詢所有訂單 + 付款紀錄
$sql = "
SELECT o.OrderID, o.取票代碼, o.總金額, o.訂購時間, o.ShowTimeID,
       p.PaymentID, p.付款方式, p.付款金額, p.付款狀態, p.付款時間
FROM 訂單 o
LEFT JOIN 付款 p ON o.OrderID = p.OrderID
ORDER BY o.訂購時間 DESC
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>💳 付款紀錄查詢</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
  <h3 class="text-center mb-4">💳 付款紀錄查詢</h3>
  <table class="table table-bordered table-striped text-center align-middle bg-white shadow-sm">
    <thead class="table-secondary">
      <tr>
        <th>訂單編號</th>
        <th>取票代碼</th>
        <th>總金額</th>
        <th>付款方式</th>
        <th>付款金額</th>
        <th>付款狀態</th>
        <th>付款時間</th>
        <th>場次ID</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['OrderID']) ?></td>
          <td><?= htmlspecialchars($r['取票代碼']) ?></td>
          <td><?= htmlspecialchars($r['總金額']) ?></td>
          <td><?= htmlspecialchars($r['付款方式'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['付款金額'] ?? '-') ?></td>
          <td>
            <?php if ($r['付款狀態'] === '已付款'): ?>
              <span class="badge bg-success">已付款</span>
            <?php elseif ($r['付款狀態'] === '未付款'): ?>
              <span class="badge bg-danger">未付款</span>
            <?php else: ?>
              <span class="badge bg-secondary">-</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['付款時間'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['ShowTimeID']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-center mt-3">
    <a href="index.php" class="btn btn-secondary">返回首頁</a>
  </div>
</div>
</body>
</html>
