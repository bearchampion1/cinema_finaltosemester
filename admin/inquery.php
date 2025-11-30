<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

/* =================== 搜尋條件處理 =================== */
$where = "1=1";
$params = [];

if (!empty($_GET['keyword'])) {
    $where .= " AND (m.`片名` LIKE :kw OR t.`廳名` LIKE :kw OR s.`ShowTimeID` LIKE :kw)";
    $params[':kw'] = "%{$_GET['keyword']}%";
}

if (!empty($_GET['start_date'])) {
    $where .= " AND s.`播放日期` >= :start";
    $params[':start'] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
    $where .= " AND s.`播放日期` <= :end";
    $params[':end'] = $_GET['end_date'];
}

/* =================== 匯出 CSV =================== */
if (isset($_GET['export']) && $_GET['export'] === '1') {
    $sql = "SELECT s.`ShowTimeID`, s.`播放日期`, s.`開始時間`, 
                   m.`片名`, t.`廳名`, s.`可用座位數`
            FROM `場次` s
            JOIN `movie` m ON s.`MovieID` = m.`MovieID`
            JOIN `影廳` t ON s.`TheaterID` = t.`TheaterID`
            WHERE $where ORDER BY s.`播放日期`, s.`開始時間`";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cinema_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ShowTimeID', '播放日期', '開始時間', '片名', '廳名', '可用座位數']);
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
}

/* =================== 查詢顯示資料 =================== */
$sql = "SELECT s.`ShowTimeID`, s.`播放日期`, s.`開始時間`, 
               m.`片名`, t.`廳名`, s.`可用座位數`
        FROM `場次` s
        JOIN `movie` m ON s.`MovieID` = m.`MovieID`
        JOIN `影廳` t ON s.`TheaterID` = t.`TheaterID`
        WHERE $where
        ORDER BY s.`播放日期`, s.`開始時間`";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🎬 電影院訂票系統 - 查詢與匯出</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>
<div class="container">
<h1>🎟 場次查詢與匯出</h1>
<p class="text-muted">可依片名、影廳、場次ID 或日期區間篩選；支援匯出 CSV。</p>

<!-- 搜尋表單 -->
<form method="get" class="row g-3 mb-4">
  <div class="col-md-3">
    <label class="form-label">關鍵字（片名/影廳/場次ID）</label>
    <input type="text" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">開始日期</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">結束日期</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" class="form-control">
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-primary w-100">🔍 搜尋</button>
  </div>
  <div class="col-md-2 align-self-end">
    <a class="btn btn-success w-100"
       href="?keyword=<?= urlencode($_GET['keyword'] ?? '') ?>&start_date=<?= urlencode($_GET['start_date'] ?? '') ?>&end_date=<?= urlencode($_GET['end_date'] ?? '') ?>&export=1">
       📤 匯出 CSV
    </a>
  </div>
</form>

<!-- 顯示結果 -->
<table class="table table-bordered table-striped">
<thead class="table-dark">
  <tr>
    <th>ShowTimeID</th>
    <th>播放日期</th>
    <th>開始時間</th>
    <th>片名</th>
    <th>影廳</th>
    <th>可用座位數</th>
  </tr>
</thead>
<tbody>
<?php if (empty($rows)): ?>
  <tr><td colspan="6" class="text-center text-muted">查無資料</td></tr>
<?php else: ?>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['ShowTimeID']) ?></td>
    <td><?= htmlspecialchars($r['播放日期']) ?></td>
    <td><?= htmlspecialchars($r['開始時間']) ?></td>
    <td><?= htmlspecialchars($r['片名']) ?></td>
    <td><?= htmlspecialchars($r['廳名']) ?></td>
    <td><?= htmlspecialchars($r['可用座位數']) ?></td>
  </tr>
  <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<a href="index.php" class="btn btn-secondary mt-3">⬅ 返回後台</a>
</div>
</body>
</html>
