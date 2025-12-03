<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

/* 🎬 查詢電影與場次 */
$sql = "SELECT s.ShowTimeID, s.播放日期, s.開始時間, s.可用座位數,
               m.片名, m.類型, t.廳名
        FROM 場次 s
        JOIN movie m ON s.MovieID = m.MovieID
        JOIN 影廳 t ON s.TheaterID = t.TheaterID
        ORDER BY s.播放日期, s.開始時間";
$stmt = $pdo->query($sql);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 🔍 關鍵字搜尋 */
$keyword = $_GET['search'] ?? '';
$params = [];
$where = "";

if (!empty($keyword)) {
    $where = "AND (m.片名 LIKE :kw OR t.廳名 LIKE :kw)";
    $params[':kw'] = "%{$keyword}%";
}

/* 📄 分頁設定 */
$perPage = 10; // 每頁筆數
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/* 🧾 訂單紀錄：需要驗證才能查詢 */
$historyOrders = [];
$orderVerified = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_order'])) {
    $verifyName = trim($_POST['verify_name'] ?? '');
    $verifyEmail = trim($_POST['verify_email'] ?? '');
    
    if (!empty($verifyName) && !empty($verifyEmail)) {
        /* 🧾 訂單總筆數（含驗證） */
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM 訂單 o
            JOIN 場次 s ON o.ShowTimeID = s.ShowTimeID
            JOIN movie m ON s.MovieID = m.MovieID
            JOIN 影廳 t ON s.TheaterID = t.TheaterID
            WHERE o.顧客姓名 = :name AND o.顧客Email = :email $where
        ");
        $verifyParams = array_merge($params, [':name' => $verifyName, ':email' => $verifyEmail]);
        $countStmt->execute($verifyParams);
        $totalRows = $countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalRows / $perPage));

        /* 🧾 查詢訂單紀錄（含電影與影廳，需驗證） */
        $sql = "
            SELECT o.OrderID, o.取票代碼, o.總金額, o.訂購時間,
                   COALESCE(p.付款狀態, '未付款') AS 付款狀態,
                   m.片名, t.廳名
            FROM 訂單 o
            JOIN 場次 s ON o.ShowTimeID = s.ShowTimeID
            JOIN movie m ON s.MovieID = m.MovieID
            JOIN 影廳 t ON s.TheaterID = t.TheaterID
            LEFT JOIN 付款 p ON o.OrderID = p.OrderID
            WHERE o.顧客姓名 = :name AND o.顧客Email = :email $where
            ORDER BY o.訂購時間 DESC
            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($verifyParams);
        $historyOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orderVerified = true;
        
        if (empty($historyOrders)) {
            $orderError = '❌ 查無符合的訂單紀錄，請確認姓名與 Email 是否正確。';
        }
    } else {
        $orderError = '⚠️ 請填寫姓名與 Email 以查詢訂單。';
    }
} else {
    // 未驗證時，不查詢訂單
    $totalRows = 0;
    $totalPages = 1;
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🎬 電影院線上查詢</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: "微軟正黑體"; }
.card { margin-bottom: 1rem; }
.page-link.active {
  background-color: #0d6efd;
  color: white;
}
</style>
</head>
<body>

<!-- ✅ 導覽列 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎬 好秀電影院</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="index.php">🏠 首頁</a></li>
        <li class="nav-item"><a class="nav-link" href="user_search.php">🎬 查詢場次</a></li>
        <li class="nav-item"><a class="nav-link" href="movie_direction.php">🖼 電影一覽</a></li>
        <li class="nav-item"><a class="nav-link" href="order_check.php">🎟 購票紀錄</a></li>
        <li class="nav-item"><a class="nav-link" href="#">📞 聯絡我們</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <h2 class="mb-4 text-center text-primary">🎟 查詢電影場次</h2>

  <?php foreach ($schedules as $r): ?>
    <?php
      // 隱藏已開始或已結束的場次（以 Asia/Taipei 時區比較）
      $tz = new DateTimeZone('Asia/Taipei');
      $dt = DateTime::createFromFormat('Y-m-d H:i:s', $r['播放日期'] . ' ' . $r['開始時間'], $tz);
      if (!$dt) {
          // 若帶秒數格式失敗，嘗試不帶秒數的格式
          $dt = DateTime::createFromFormat('Y-m-d H:i', $r['播放日期'] . ' ' . $r['開始時間'], $tz);
      }
      if (!$dt) continue; // 無法解析則跳過
      $now = new DateTime('now', $tz);
      $cutoff = clone $now;
      $cutoff->modify('+10 minutes'); // 購票關閉的時間點：開場前10分鐘
      if ($dt <= $cutoff) continue; // 若場次在 10 分鐘內或已開始，直接隱藏
      
      // 檢查座位是否已售完
      $soldOut = ($r['可用座位數'] <= 0);
    ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title text-primary"><?= htmlspecialchars($r['片名']) ?></h5>
        <p class="card-text mb-2">
          🎭 類型：<?= htmlspecialchars($r['類型']) ?><br>
          🏢 影廳：<?= htmlspecialchars($r['廳名']) ?><br>
          📅 日期：<?= htmlspecialchars($r['播放日期']) ?><br>
          ⏰ 時間：<?= htmlspecialchars($r['開始時間']) ?><br>
          💺 可用座位數：
          <?php if ($soldOut): ?>
            <span class="text-danger fw-bold">0（已售完）</span>
          <?php else: ?>
            <span class="text-success fw-bold"><?= htmlspecialchars($r['可用座位數']) ?></span>
          <?php endif; ?>
        </p>
        <?php if ($soldOut): ?>
          <button class="btn btn-secondary" disabled>已售完</button>
        <?php else: ?>
          <a href="booking.php?showtime=<?= urlencode($r['ShowTimeID']) ?>" class="btn btn-primary">立即購票</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- 🧾 訂單紀錄 -->
<div class="container mt-5">
  <h3 class="text-primary mb-3 text-center">🧾 過去訂單紀錄</h3>

  <!-- 驗證表單 -->
  <?php if (!$orderVerified): ?>
    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
      <div class="card-body">
        <h5 class="card-title text-center">🔒 驗證身份</h5>
        <p class="text-muted text-center">請輸入姓名與 Email 查詢訂單紀錄</p>
        
        <?php if (!empty($orderError)): ?>
          <div class="alert alert-warning"><?= htmlspecialchars($orderError) ?></div>
        <?php endif; ?>
        
        <form method="post">
          <div class="mb-3">
            <label for="verify_name" class="form-label">👤 姓名</label>
            <input type="text" class="form-control" id="verify_name" name="verify_name" required>
          </div>
          <div class="mb-3">
            <label for="verify_email" class="form-label">📧 Email</label>
            <input type="email" class="form-control" id="verify_email" name="verify_email" required>
          </div>
          <button type="submit" name="verify_order" class="btn btn-primary w-100">驗證並查詢</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- 🔍 搜尋 -->
    <form method="get" class="mb-3 text-center">
      <div class="input-group w-50 mx-auto">
        <input type="text" name="search" class="form-control" placeholder="輸入電影名稱或影廳關鍵字..." value="<?= htmlspecialchars($keyword) ?>">
        <button class="btn btn-primary" type="submit">搜尋</button>
        <?php if (!empty($keyword)): ?>
          <a href="user_search.php" class="btn btn-outline-secondary">清除</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (empty($historyOrders)): ?>
      <p class="text-muted text-center">沒有符合條件的訂單紀錄。</p>
    <?php else: ?>
    <table class="table table-bordered table-hover bg-white shadow-sm">
      <thead class="table-secondary text-center">
        <tr>
          <th>電影名稱</th>
          <th>影廳</th>
          <th>訂單編號</th>
          <th>取票代碼</th>
          <th>總金額</th>
          <th>付款狀態</th>
          <th>訂購時間</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php foreach ($historyOrders as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['片名']) ?></td>
            <td><?= htmlspecialchars($o['廳名']) ?></td>
            <td><?= htmlspecialchars($o['OrderID']) ?></td>
            <td><?= htmlspecialchars($o['取票代碼']) ?></td>
            <td>$<?= htmlspecialchars($o['總金額']) ?></td>
            <td>
              <?php if ($o['付款狀態'] === '已付款'): ?>
                <span class="badge bg-success">已付款</span>
              <?php else: ?>
                <span class="badge bg-danger">未付款</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($o['訂購時間']) ?></td>
            <td>
              <a href="ticket.php?id=<?= urlencode($o['OrderID']) ?>&code=<?= urlencode($o['取票代碼']) ?>" 
                 class="btn btn-sm btn-outline-primary">查看票券</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- 📄 分頁按鈕 -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $keyword ? '&search=' . urlencode($keyword) : '' ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php