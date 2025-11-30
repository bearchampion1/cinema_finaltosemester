<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

/* -------------------------------------------------
   ① 若沒有 GET → 出場次選單
---------------------------------------------------*/
if (!isset($_GET['showtime'])) {

    // 取得所有場次
    $showlist = $pdo->query("
        SELECT s.ShowTimeID, s.播放日期, s.開始時間, m.片名, t.廳名, s.TheaterID
        FROM 場次 s
        JOIN movie m ON s.MovieID = m.MovieID
        JOIN 影廳 t ON s.TheaterID = t.TheaterID
        ORDER BY s.播放日期, s.開始時間
    ")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>選擇場次 - 自動生成座位</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>

<h3 class="mb-3">⚙ 自動生成座位 - 選擇場次</h3>

<form method="get">
    <label class="form-label fw-bold">請選擇場次：</label>
    <select class="form-select mb-3" name="showtime" required>
        <option value="">請選擇場次</option>
        <?php foreach ($showlist as $s): ?>
        <option value="<?= $s['ShowTimeID'] ?>">
            <?= $s['ShowTimeID'] ?> - <?= $s['片名'] ?>（<?= $s['廳名'] ?> / <?= $s['播放日期'] ?> <?= $s['開始時間'] ?>）
        </option>
        <?php endforeach; ?>
    </select>

    <button class="btn btn-primary">進入座位自動生成</button>
    <a href="index.php" class="btn btn-secondary">返回</a>
</form>

</body>
</html>
<?php
exit;
}

/* -------------------------------------------------
   ② 取得 GET 場次後 → 查資料 + 初始化變數（修正錯誤核心）
---------------------------------------------------*/
$showtimeID = $_GET['showtime'];  // ✔ 定義
$theaterID = null;                // ✔ 先定義
$existCount = 0;                  // ✔ 先定義

// 讀場次資訊
$sql = "SELECT s.*, m.片名, t.廳名
        FROM 場次 s
        JOIN movie m ON s.MovieID = m.MovieID
        JOIN 影廳 t ON s.TheaterID = t.TheaterID
        WHERE s.ShowTimeID = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $showtimeID]);
$show = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$show) die("查無此場次");

// 取得影廳 ID（✔ 防止 undefined）
$theaterID = $show['TheaterID'];

// 查該場次座位數（✔ 防止 undefined）
$stmt = $pdo->prepare("SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid");
$stmt->execute([':sid' => $showtimeID]);
$existCount = $stmt->fetchColumn();

// ----------------------------
// 處理表單：自動生成座位
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = intval($_POST['RowCount'] ?? 0);
    $cols = intval($_POST['ColCount'] ?? 0);
    $price = intval($_POST['票價'] ?? 0);
    $type = trim($_POST['價格類型'] ?? '一般');
    $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === '1';

    if ($rows <= 0 || $cols <= 0) {
        die("<script>alert('請輸入正確的排數與每排座位數');history.back();</script>");
    }

    try {
        $pdo->beginTransaction();

        if ($overwrite) {
            $del = $pdo->prepare("DELETE FROM 座位 WHERE ShowTimeID = :sid");
            $del->execute([':sid' => $showtimeID]);
        }

        $insert = $pdo->prepare("INSERT INTO 座位 
            (SeatID, RowNo, SeatNo, 價格類型, 狀態, 更新時間, TheaterID, ShowTimeID, 票價)
            VALUES (:sid, :rno, :sno, :type, '可售', NOW(), :tid, :stid, :price)");

        $count = 0;
        for ($r = 1; $r <= $rows; $r++) {
            $rowNo = chr(64 + $r);
            for ($s = 1; $s <= $cols; $s++) {
                $seatID = sprintf("S_%s_%s_%s%02d", $showtimeID, $theaterID, $rowNo, $s);
                $insert->execute([
                    ':sid' => $seatID,
                    ':rno' => $rowNo,
                    ':sno' => $s,
                    ':type' => $type,
                    ':tid' => $theaterID,
                    ':stid' => $showtimeID,
                    ':price' => $price
                ]);
                $count++;
            }
        }

        // 同步更新場次可用座位數
        $update = $pdo->prepare("UPDATE 場次 SET 可用座位數 = (
            SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
        ) WHERE ShowTimeID = :sid");
        $update->execute([':sid' => $showtimeID]);

        $pdo->commit();
        echo "<script>alert('✅ 自動建立完成，共建立 {$count} 個座位！');location.href='seat_auto_generate.php?showtime=" . urlencode($showtimeID) . "';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("❌ 錯誤：" . $e->getMessage());
    }
}

/* -------------------------------------------------
   ③ 顯示資訊：電影、影廳、已存在座位
---------------------------------------------------*/
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>自動生成座位</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h3 class="mb-3">⚙ 自動生成座位</h3>

<div class="alert alert-info">
  🎬 電影：<?= $show['片名'] ?><br>
  🏢 影廳：<?= $show['廳名'] ?>（ID: <?= $theaterID ?>）<br>
  🎫 目前已有座位數：<b><?= $existCount ?></b> 個
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">自動生成座位設定</h5>
        <form method="post" class="row g-3">
            <input type="hidden" name="ShowTimeID" value="<?= htmlspecialchars($showtimeID) ?>">
            <input type="hidden" name="TheaterID" value="<?= htmlspecialchars($theaterID) ?>">
            <div class="col-md-3">
                <label class="form-label">排數</label>
                <input type="number" name="RowCount" class="form-control" value="10" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">每排座位數</label>
                <input type="number" name="ColCount" class="form-control" value="12" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">票價</label>
                <input type="number" name="票價" class="form-control" value="300" min="0" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">價格類型</label>
                <input type="text" name="價格類型" class="form-control" value="一般">
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="overwrite" name="overwrite">
                    <label class="form-check-label" for="overwrite">如果已有座位，先刪除再建立（覆寫）</label>
                </div>
                <div class="form-text">座位編號範例：S_{ShowTimeID}_{TheaterID}_A01</div>
            </div>

            <div class="col-12 text-center mt-2">
                <button class="btn btn-success px-4">開始自動生成座位</button>
                <a class="btn btn-secondary px-4" href="index.php">返回後台</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
