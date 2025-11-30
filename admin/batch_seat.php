<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theaterID = trim($_POST['TheaterID']);
    $showtimeID = trim($_POST['ShowTimeID']);
    $rows = intval($_POST['RowCount']);
    $cols = intval($_POST['ColCount']);
    $price = intval($_POST['票價']);
    $type = $_POST['價格類型'] ?: '一般';

    if (!$theaterID || !$showtimeID || $rows <= 0 || $cols <= 0) {
        die("<script>alert('❌ 請輸入正確的影廳ID、場次ID、排數與每排座位數！');history.back();</script>");
    }

    try {
        $pdo->beginTransaction();

        $insert = $pdo->prepare("INSERT  INTO 座位 
            (SeatID, RowNo, SeatNo, 價格類型, 狀態, 更新時間, TheaterID, ShowTimeID, 票價)
            VALUES (:sid, :rno, :sno, :type, '可售', NOW(), :tid, :stid, :price)");

        $count = 0;
        for ($r = 1; $r <= $rows; $r++) {
            $rowNo = chr(64 + $r);
            for ($s = 1; $s <= $cols; $s++) {
                $seatID = sprintf("S_%s_%s_%s%02d", $showtimeID, $theaterID, $rowNo, $s);
                echo $seatID . "<br>";
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

        // ✅ 自動更新場次可用座位數
        $update = $pdo->prepare("
            UPDATE 場次 
            SET 可用座位數 = (
                SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
            )
            WHERE ShowTimeID = :sid
        ");
        $update->execute([':sid' => $showtimeID]);

        $pdo->commit();
        echo "<script>alert('✅ 批量建立成功，共建立 {$count} 個座位！');location.href='index.php';</script>";
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
<title>批量建立座位</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>
<div class="container">
  <h3 class="text-primary mb-4">🎫 批量建立座位</h3>
  <form method="post" class="row g-3">
    <div class="col-md-4"><label class="form-label">影廳ID</label><input class="form-control" name="TheaterID" required></div>
    <div class="col-md-4"><label class="form-label">場次ID</label><input class="form-control" name="ShowTimeID" required></div>
    <div class="col-md-2"><label class="form-label">排數</label><input type="number" class="form-control" name="RowCount" required></div>
    <div class="col-md-2"><label class="form-label">每排座位數</label><input type="number" class="form-control" name="ColCount" required></div>
    <div class="col-md-4"><label class="form-label">票價</label><input type="number" class="form-control" name="票價" value="300" required></div>
    <div class="col-md-4"><label class="form-label">價格類型</label><input class="form-control" name="價格類型" value="一般"></div>
    <div class="col-md-12 text-center mt-3">
      <button class="btn btn-success px-5">批量建立座位</button>
      <a href="index.php" class="btn btn-secondary px-4">返回後台</a>
    </div>
  </form>
</div>
</body>
</html>
<?php