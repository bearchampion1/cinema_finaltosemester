<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theaterID = trim($_POST['TheaterID']);
    $showtimeID = trim($_POST['ShowTimeID']);

    if (!$theaterID && !$showtimeID) {
        die("<script>alert('❌ 請至少輸入影廳ID或場次ID！');history.back();</script>");
    }

    try {
        $pdo->beginTransaction();

        if ($showtimeID) {
            $stmt = $pdo->prepare("DELETE FROM 座位 WHERE ShowTimeID = :sid");
            $stmt->execute([':sid' => $showtimeID]);
            $count = $stmt->rowCount();
            $msg = "刪除場次 {$showtimeID} 的座位，共 {$count} 筆。";

            // ✅ 更新該場次的可用座位數
            $update = $pdo->prepare("
                UPDATE 場次 
                SET 可用座位數 = (
                    SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
                )
                WHERE ShowTimeID = :sid
            ");
            $update->execute([':sid' => $showtimeID]);

        } elseif ($theaterID) {
            $stmt = $pdo->prepare("DELETE FROM 座位 WHERE TheaterID = :tid");
            $stmt->execute([':tid' => $theaterID]);
            $count = $stmt->rowCount();
            $msg = "刪除影廳 {$theaterID} 的所有座位，共 {$count} 筆。";

            // ✅ 同步更新該影廳所有場次的可用座位數
            $sidStmt = $pdo->prepare("SELECT ShowTimeID FROM 場次 WHERE TheaterID = :tid");
            $sidStmt->execute([':tid' => $theaterID]);
            foreach ($sidStmt->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                $update = $pdo->prepare("
                    UPDATE 場次 
                    SET 可用座位數 = (
                        SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
                    )
                    WHERE ShowTimeID = :sid
                ");
                $update->execute([':sid' => $sid]);
            }
        }

        $pdo->commit();
        echo "<script>alert('✅ {$msg} 可用座位數已同步更新。');location.href='index.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("❌ 錯誤：" . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>批量刪除座位</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>
<div class="container">
  <h3 class="text-danger mb-4">🗑 批量刪除座位</h3>
  <div class="alert alert-warning">
    ⚠️ 注意：此操作會永久刪除符合條件的所有座位，且自動更新場次可用座位數。
  </div>
  <form method="post" class="row g-3">
    <div class="col-md-6"><label class="form-label">影廳ID（可選）</label><input class="form-control" name="TheaterID" placeholder="例如：T001"></div>
    <div class="col-md-6"><label class="form-label">場次ID（可選）</label><input class="form-control" name="ShowTimeID" placeholder="例如：ST0001"></div>
    <div class="col-md-12 text-center mt-3">
      <button class="btn btn-danger px-5" onclick="return confirm('確定要刪除嗎？此動作無法復原！')">批量刪除</button>
      <a href="index.php" class="btn btn-secondary px-4">返回後台</a>
    </div>
  </form>
</div>
</body>
</html>
