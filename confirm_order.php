<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

/* -----------------------------------------------------
  1️⃣ 接收參數
----------------------------------------------------- */
if (!isset($_POST['ShowTimeID']) || !isset($_POST['selectedSeats'])) {
    die("❌ 無效的購票資料");
}

$showtimeID = $_POST['ShowTimeID'];
$seatList = json_decode($_POST['selectedSeats'], true);
$totalAmount = floatval($_POST['totalAmount']);

if (!$seatList || count($seatList) == 0) {
    die("❌ 未選擇座位！");
}

/* 伺服器端：檢查是否距開場不足 10 分鐘，若是則拒絕（防止繞過前端） */
$tstmt = $pdo->prepare("SELECT `播放日期`,`開始時間` FROM `場次` WHERE ShowTimeID = :id");
$tstmt->execute([':id' => $showtimeID]);
$trow = $tstmt->fetch(PDO::FETCH_ASSOC);
if ($trow) {
  $show_ts = strtotime($trow['播放日期'] . ' ' . $trow['開始時間']);
  $remain = $show_ts - time();
  if ($remain <= 600) {
    die("❌ 此場次距開場不足 10 分鐘，無法線上訂票。如需協助請洽櫃檯或管理員。");
  }
}

/* -----------------------------------------------------
  2️⃣ 建立訂單 ID ＆ 取票代碼
  說明：資料庫 `訂單`.`OrderID` 為 char(10)，原先使用完整時間戳會被截斷導致重複。
  因此改為產生不超過 10 字元的 ID（O + 9 字元），並檢查是否已存在，重試直到唯一。
----------------------------------------------------- */
$ticketCode = substr(md5(uniqid()), 0, 8);

// 產生 10 字元以內的 OrderID（格式：O + 9 字元）並保證唯一
do {
    $orderID = 'O' . strtoupper(substr(md5(uniqid('', true)), 0, 9));
    $checkId = $pdo->prepare("SELECT 1 FROM 訂單 WHERE OrderID = :id");
    $checkId->execute([':id' => $orderID]);
    $exists = $checkId->fetchColumn();
} while ($exists);

/* -----------------------------------------------------
  3️⃣ 購票交易開始
----------------------------------------------------- */
try {
    $pdo->beginTransaction();

    /* -----------------------------------------------------
      ① 鎖定所有座位
    ----------------------------------------------------- */
    $check = $pdo->prepare("
        SELECT 狀態, 更新時間 
        FROM 座位 
        WHERE SeatID = :sid AND ShowTimeID = :stid
        FOR UPDATE
    ");

    foreach ($seatList as $s) {
        $check->execute([
            ':sid' => $s['id'],
            ':stid' => $showtimeID
        ]);

        $seat = $check->fetch(PDO::FETCH_ASSOC);

        if (!$seat) {
            throw new Exception("❌ 座位不存在：" . $s['id']);
        }

        if ($seat['狀態'] === '已售') {
            throw new Exception("❌ 座位已售出：" . $s['name']);
        }

        if ($seat['狀態'] === '鎖定') {
            $last = strtotime($seat['更新時間']);
            if (time() - $last <= 120) { }
        }
    }

    /* -----------------------------------------------------
      ② 更新座位為已售
    ----------------------------------------------------- */
    $sell = $pdo->prepare("
        UPDATE 座位 
        SET 狀態='已售', 更新時間=NOW()
        WHERE SeatID = :sid AND ShowTimeID = :stid
    ");

    foreach ($seatList as $s) {
        $sell->execute([
            ':sid' => $s['id'],
            ':stid' => $showtimeID
        ]);
    }

    /* -----------------------------------------------------
      ③ 新增訂單
    ----------------------------------------------------- */
    $stmt = $pdo->prepare("
        INSERT INTO 訂單 
        (`OrderID`,`取票代碼`,`總金額`,`訂購時間`,`ShowTimeID`)
        VALUES (:oid, :code, :total, NOW(), :stid)
    ");
    $stmt->execute([
        ':oid' => $orderID,
        ':code' => $ticketCode,
        ':total' => $totalAmount,
        ':stid' => $showtimeID
    ]);

    /* -----------------------------------------------------
      ④ 訂單座位
    ----------------------------------------------------- */
    $os = $pdo->prepare("
        INSERT INTO 訂單座位 (`OrderID`, `SeatID`)
        VALUES (:oid, :sid)
    ");

    foreach ($seatList as $s) {
        $os->execute([
            ':oid' => $orderID,
            ':sid' => $s['id']
        ]);
    }

    /* -----------------------------------------------------
      ⑤ 付款（無交易編號）
      產生不會被截斷的唯一 PaymentID（格式：P + 9 字元）
      */
    do {
        $paymentID = 'P' . strtoupper(substr(md5(uniqid('', true)), 0, 9));
        $chk = $pdo->prepare("SELECT 1 FROM 付款 WHERE PaymentID = :id");
        $chk->execute([':id' => $paymentID]);
        $pexists = $chk->fetchColumn();
      } while ($pexists);

      $pay = $pdo->prepare("
        INSERT INTO `付款`
        (`PaymentID`,`OrderID`,`付款方式`,`付款金額`,`付款狀態`,`付款時間`)
        VALUES (:pid, :oid, '信用卡', :amt, '已付款', NOW())
      ");
      $pay->execute([
        ':pid' => $paymentID,
        ':oid' => $orderID,
        ':amt' => $totalAmount
      ]);

    /* -----------------------------------------------------
      ⑥ 更新可用座位數
    ----------------------------------------------------- */
    $update = $pdo->prepare("
        UPDATE 場次
        SET 可用座位數 = (
            SELECT COUNT(*) 
            FROM 座位 
            WHERE ShowTimeID = :stid AND 狀態='可售'
        )
        WHERE ShowTimeID = :stid
    ");
    $update->execute([':stid' => $showtimeID]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("<h2>❌ 購票失敗</h2><p>{$e->getMessage()}</p>");
}

?>

<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>訂票完成</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">
<div class="container text-center">

  <h2 class="text-success mb-3">🎉 訂票成功！</h2>
  <p class="fs-5">您的取票代碼：<b><?= htmlspecialchars($ticketCode) ?></b></p>

  <div class="my-3">
    <img src="phpqrcode/qrcode.php?text=<?= urlencode($ticketCode) ?>" alt="QR Code">
  </div>

  <h4>訂購座位</h4>
  <p>
    <?php foreach ($seatList as $s) echo htmlspecialchars($s['name']) . "<br>"; ?>
  </p>

  <h4>總金額：NT$ <?= htmlspecialchars($totalAmount) ?></h4>

  <a href="user_search.php" class="btn btn-primary mt-3">返回查詢</a>
</div>
</body>
</html>
