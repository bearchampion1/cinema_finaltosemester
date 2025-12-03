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
$customerName = trim($_POST['customer_name'] ?? '');
$customerEmail = trim($_POST['customer_email'] ?? '');

if (!$seatList || count($seatList) == 0) {
    die("❌ 未選擇座位！");
}

if (empty($customerName) || empty($customerEmail)) {
    die("❌ 請填寫完整的顧客資訊（姓名與 Email）");
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    die("❌ Email 格式不正確");
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
        (`OrderID`,`取票代碼`,`總金額`,`訂購時間`,`ShowTimeID`,`顧客姓名`,`顧客Email`)
        VALUES (:oid, :code, :total, NOW(), :stid, :name, :email)
    ");
    $stmt->execute([
        ':oid' => $orderID,
        ':code' => $ticketCode,
        ':total' => $totalAmount,
        ':stid' => $showtimeID,
        ':name' => $customerName,
        ':email' => $customerEmail
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

    /* -----------------------------------------------------
      ⑦ 查詢電影與場次資訊（用於頁面顯示）
    ----------------------------------------------------- */
    $emailQuery = $pdo->prepare("
        SELECT m.片名, m.類型, s.播放日期, s.開始時間, t.廳名
        FROM 場次 s
        JOIN movie m ON s.MovieID = m.MovieID
        JOIN 影廳 t ON s.TheaterID = t.TheaterID
        WHERE s.ShowTimeID = :stid
    ");
    $emailQuery->execute([':stid' => $showtimeID]);
    $movieInfo = $emailQuery->fetch(PDO::FETCH_ASSOC);
    
    /* -----------------------------------------------------
      ⑧ 發送訂票通知 Email
    ----------------------------------------------------- */
    try {
        require_once 'send_ticket_email.php';
        
        $emailSent = sendTicketEmail(
            $customerEmail, 
            $customerName, 
            $ticketCode, 
            $orderID, 
            $movieInfo, 
            $seatList, 
            $totalAmount
        );
        
        if (!$emailSent) {
            error_log("Email 發送失敗 - 訂單：{$orderID}, Email：{$customerEmail}");
        }
    } catch (Exception $emailError) {
        // Email 發送失敗不影響訂單
        error_log("Email 發送異常：" . $emailError->getMessage());
    }

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
  
  <!-- Email 通知 -->
  <div class="alert alert-success mx-auto mb-3" style="max-width: 600px;">
    <strong>📧 訂票確認信已發送</strong><br>
    <small>我們已將取票代碼與完整訂票資訊發送至 <strong><?= htmlspecialchars($customerEmail) ?></strong><br>
    請查收 Email 以取得電子票券與 QR Code</small>
  </div>
  
  <!-- 取票代碼 -->
  <div class="card mx-auto mb-4" style="max-width: 500px;">
    <div class="card-body bg-warning bg-opacity-25">
      <h5 class="card-title text-center">📱 取票代碼</h5>
      <p class="text-center display-4 fw-bold text-primary mb-2"><?= htmlspecialchars($ticketCode) ?></p>
      <p class="text-center text-muted">請憑此代碼至櫃檯或自助機取票</p>
    </div>
  </div>

  <!-- 訂單資訊 -->
  <div class="card mx-auto mb-3" style="max-width: 600px;">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">🎬 電影資訊</h5>
    </div>
    <div class="card-body text-start">
      <div class="row mb-2">
        <div class="col-4 fw-bold">電影名稱：</div>
        <div class="col-8"><?= htmlspecialchars($movieInfo['片名']) ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-4 fw-bold">類型：</div>
        <div class="col-8"><?= htmlspecialchars($movieInfo['類型']) ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-4 fw-bold">影廳：</div>
        <div class="col-8"><?= htmlspecialchars($movieInfo['廳名']) ?></div>
      </div>
      <div class="row mb-2">
        <div class="col-4 fw-bold">播放日期：</div>
        <div class="col-8"><?= htmlspecialchars($movieInfo['播放日期']) ?></div>
      </div>
      <div class="row">
        <div class="col-4 fw-bold">開始時間：</div>
        <div class="col-8"><?= htmlspecialchars($movieInfo['開始時間']) ?></div>
      </div>
    </div>
  </div>

  <!-- 座位與金額 -->
  <div class="card mx-auto mb-3" style="max-width: 600px;">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0">💺 訂購資訊</h5>
    </div>
    <div class="card-body text-start">
      <div class="mb-3">
        <strong>座位：</strong>
        <p class="mb-0">
          <?php foreach ($seatList as $s) echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($s['name']) . '</span>'; ?>
        </p>
      </div>
      <div class="mb-3">
        <strong>訂單編號：</strong> <?= htmlspecialchars($orderID) ?>
      </div>
      <div class="mb-3">
        <strong>顧客姓名：</strong> <?= htmlspecialchars($customerName) ?>
      </div>
      <div class="mb-3">
        <strong>Email：</strong> <?= htmlspecialchars($customerEmail) ?>
      </div>
      <div>
        <strong class="fs-5">總金額：</strong> <span class="text-danger fs-4 fw-bold">NT$ <?= htmlspecialchars($totalAmount) ?></span>
      </div>
      <div class="mt-2">
        <span class="badge bg-success">✓ 已付款</span>
      </div>
    </div>
  </div>

  <!-- 注意事項 -->
  <div class="alert alert-warning mx-auto" style="max-width: 600px; text-align: left;">
    <strong>⚠️ 注意事項：</strong>
    <ul class="mb-0 mt-2">
      <li>請提前 15 分鐘到達影廳取票入場</li>
      <li>請妥善保管您的取票代碼</li>
      <li>可至「查看電子票券」頁面查詢完整票券資訊</li>
    </ul>
  </div>

  <!-- 操作按鈕 -->
  <div class="mt-4">
    <a href="ticket.php?code=<?= urlencode($ticketCode) ?>" class="btn btn-success me-2">查看電子票券</a>
    <a href="user_search.php" class="btn btn-primary">返回查詢</a>
  </div>
</div>
</body>
</html>
