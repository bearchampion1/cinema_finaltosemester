<?php
require_once '../config.php';
// 後台存取控制
require_once __DIR__ . '/auth.php';
// 如果管理員尚未登入，跳轉到登入頁（auth.php 會處理 test mode）
require_admin();

$message = "";

/* ============ 新增處理 ============ */
// 新增電影
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    try {
        $sql = "INSERT INTO `movie` (MovieID, `片名`, `片長`, `類型`, `分級`, `語言`, `上映日`)
                VALUES (:MovieID, :Title, :Length, :Genre, :Rating, :Lang, :ReleaseDate)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':MovieID' => $_POST['MovieID'],
            ':Title' => $_POST['片名'],
            ':Length' => $_POST['片長'],
            ':Genre' => $_POST['類型'],
            ':Rating' => $_POST['分級'],
            ':Lang' => $_POST['語言'],
            ':ReleaseDate' => $_POST['上映日'] ?: null
        ]);
        $message = "✅ 新增電影成功";
    } catch (PDOException $e) {
        $message = "❌ 新增電影失敗：" . $e->getMessage();
    }
}

// 新增場次
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_showtime'])) {
    try {
        $sql = "INSERT INTO `場次` (`ShowTimeID`,`播放日期`,`開始時間`,`MovieID`,`TheaterID`,`可用座位數`)
                VALUES (:id,:date,:time,:movie,:theater,:avail)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['ShowTimeID'],
            ':date' => $_POST['播放日期'],
            ':time' => $_POST['開始時間'],
            ':movie' => $_POST['MovieID'],
            ':theater' => $_POST['TheaterID'],
            ':avail' => $_POST['可用座位數']
        ]);
        $message = "✅ 新增場次成功";
    } catch (PDOException $e) {
        $message = "❌ 新增場次失敗：" . $e->getMessage();
    }
}

// 新增影廳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_theater'])) {
    try {
        $sql = "INSERT INTO `影廳` (`TheaterID`,`廳名`,`類型`,`容量`,`樓層`)
                VALUES (:id,:name,:type,:cap,:floor)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['TheaterID'],
            ':name' => $_POST['廳名'],
            ':type' => $_POST['類型'],
            ':cap' => $_POST['容量'],
            ':floor' => $_POST['樓層']
        ]);
        $message = "✅ 新增影廳成功";
    } catch (PDOException $e) {
        $message = "❌ 新增影廳失敗：" . $e->getMessage();
    }
}

// 新增座位
// 新增座位
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_seat'])) {
    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO `座位` (`SeatID`,`RowNo`,`SeatNo`,`價格類型`,`狀態`,`更新時間`,`TheaterID`,`ShowTimeID`,`票價`)
                VALUES (:id,:row,:no,:ptype,:status,:upd,:theater,:showtime,:price)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['SeatID'],
            ':row' => $_POST['RowNo'],
            ':no' => $_POST['SeatNo'],
            ':ptype' => $_POST['價格類型'],
            ':status' => $_POST['狀態'],
            ':upd' => $_POST['更新時間'] ?: null,
            ':theater' => $_POST['TheaterID'],
            ':showtime' => $_POST['ShowTimeID'],
            ':price' => $_POST['票價']
        ]);

        // ✅ 新增後自動更新可用座位數
        $update = $pdo->prepare("
            UPDATE 場次
            SET 可用座位數 = (
                SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
            )
            WHERE ShowTimeID = :sid
        ");
        $update->execute([':sid' => $_POST['ShowTimeID']]);

        $pdo->commit();
        $message = "✅ 新增座位成功，場次可用座位數已更新";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "❌ 新增座位失敗：" . $e->getMessage();
    }
}


// 新增訂單
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    try {
        $sql = "INSERT INTO `訂單` (`OrderID`,`取票代碼`,`總金額`,`訂購時間`,`ShowTimeID`)
                VALUES (:id,:code,:amt,:otime,:show)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['OrderID'],
            ':code' => $_POST['取票代碼'],
            ':amt' => $_POST['總金額'],
            ':otime' => $_POST['訂購時間'] ?: null,
            ':show' => $_POST['ShowTimeID']
        ]);
        $message = "✅ 新增訂單成功";
    } catch (PDOException $e) {
        $message = "❌ 新增訂單失敗：" . $e->getMessage();
    }
}

// 新增付款
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    try {
        $sql = "INSERT INTO `付款` (`PaymentID`,`OrderID`,`付款方式`,`付款金額`,`付款狀態`,`付款時間`,`交易編號`)
                VALUES (:id,:oid,:method,:amt,:status,:ptime,:tx)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['PaymentID'],
            ':oid' => $_POST['OrderID'],
            ':method' => $_POST['付款方式'],
            ':amt' => $_POST['付款金額'],
            ':status' => $_POST['付款狀態'],
            ':ptime' => $_POST['付款時間'] ?: null,
            ':tx' => $_POST['交易編號']
        ]);
        $message = "✅ 新增付款成功";
    } catch (PDOException $e) {
        $message = "❌ 新增付款失敗：" . $e->getMessage();
    }
}

/* ============ 刪除處理（各表） ============ */
function doDelete(PDO $pdo, string $table, string $pkName, string $id) {
    $sql = "DELETE FROM `$table` WHERE `$pkName` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
}

// 刪除（電影）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_movie'])) {
    try { doDelete($pdo, 'movie', 'MovieID', $_POST['MovieID']); $message = "🗑️ 刪除電影成功"; }
    catch (PDOException $e) { $message = "❌ 刪除電影失敗：" . $e->getMessage(); }
}
// 刪除（場次）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_showtime'])) {
    try { doDelete($pdo, '場次', 'ShowTimeID', $_POST['ShowTimeID']); $message = "🗑️ 刪除場次成功"; }
    catch (PDOException $e) { $message = "❌ 刪除場次失敗：" . $e->getMessage(); }
}
// 刪除（影廳）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_theater'])) {
    try { doDelete($pdo, '影廳', 'TheaterID', $_POST['TheaterID']); $message = "🗑️ 刪除影廳成功"; }
    catch (PDOException $e) { $message = "❌ 刪除影廳失敗：" . $e->getMessage(); }
}
// 刪除（座位）
// 刪除（座位）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_seat'])) {
    try {
        $pdo->beginTransaction();

        // 先查座位對應的場次ID
        $sidStmt = $pdo->prepare("SELECT ShowTimeID FROM 座位 WHERE SeatID = :id");
        $sidStmt->execute([':id' => $_POST['SeatID']]);
        $sid = $sidStmt->fetchColumn();

        // 執行刪除
        doDelete($pdo, '座位', 'SeatID', $_POST['SeatID']);

        // ✅ 自動更新該場次可用座位數
        if ($sid) {
            $update = $pdo->prepare("
                UPDATE 場次
                SET 可用座位數 = (
                    SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid
                )
                WHERE ShowTimeID = :sid
            ");
            $update->execute([':sid' => $sid]);
        }

        $pdo->commit();
        $message = "🗑️ 刪除座位成功，場次可用座位數已更新";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "❌ 刪除座位失敗：" . $e->getMessage();
    }
}

// 刪除（訂單）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_order'])) {
  try {
    $pdo->beginTransaction();
    // 1. 查詢訂單包含的所有座位
    $orderID = $_POST['OrderID'];
    $seatStmt = $pdo->prepare("SELECT SeatID FROM 訂單座位 WHERE OrderID = :oid");
    $seatStmt->execute([':oid' => $orderID]);
    $seats = $seatStmt->fetchAll(PDO::FETCH_COLUMN);
    // 2. 將這些座位狀態設為可售
    $showtimeIDs = [];
    if ($seats && count($seats) > 0) {
      $in = implode(',', array_fill(0, count($seats), '?'));
      $updateStmt = $pdo->prepare("UPDATE 座位 SET 狀態='可售' WHERE SeatID IN ($in)");
      $updateStmt->execute($seats);
      // 取得這些座位所屬場次ID
      $sidStmt = $pdo->prepare("SELECT DISTINCT ShowTimeID FROM 座位 WHERE SeatID IN ($in)");
      $sidStmt->execute($seats);
      $showtimeIDs = $sidStmt->fetchAll(PDO::FETCH_COLUMN);
      // 更新每個場次的可用座位數
      foreach ($showtimeIDs as $sid) {
        $updateAvail = $pdo->prepare("UPDATE 場次 SET 可用座位數 = (SELECT COUNT(*) FROM 座位 WHERE ShowTimeID = :sid AND 狀態='可售') WHERE ShowTimeID = :sid");
        $updateAvail->execute([':sid' => $sid]);
      }
    }
    // 3. 刪除訂單
    doDelete($pdo, '訂單', 'OrderID', $orderID);
    $pdo->commit();
    $message = "🗑️ 刪除訂單成功，座位已恢復可售";
  } catch (PDOException $e) {
    $pdo->rollBack();
    $message = "❌ 刪除訂單失敗：" . $e->getMessage();
  }
}
// 刪除（付款）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_payment'])) {
    try { doDelete($pdo, '付款', 'PaymentID', $_POST['PaymentID']); $message = "🗑️ 刪除付款成功"; }
    catch (PDOException $e) { $message = "❌ 刪除付款失敗：" . $e->getMessage(); }
}

/* ============ 查詢（用於列表＆下拉） ============ */
$movieRows   = $pdo->query("SELECT * FROM `movie` ORDER BY `上映日` DESC")->fetchAll(PDO::FETCH_ASSOC);
$theaterRows = $pdo->query("SELECT * FROM `影廳` ORDER BY `TheaterID`")->fetchAll(PDO::FETCH_ASSOC);
$showRows    = $pdo->query("SELECT s.*, m.`片名` AS `電影名`, t.`廳名` AS `影廳名`
                             FROM `場次` s
                             JOIN `movie` m ON s.`MovieID` = m.`MovieID`
                             JOIN `影廳` t ON s.`TheaterID` = t.`TheaterID`
                             ORDER BY s.`播放日期`, s.`開始時間`")->fetchAll(PDO::FETCH_ASSOC);
$seatRows    = $pdo->query("SELECT * FROM `座位` ORDER BY `ShowTimeID`,`TheaterID`,`RowNo`,`SeatNo`")->fetchAll(PDO::FETCH_ASSOC);
$orderRows = $pdo->query("
    SELECT o.*, p.付款狀態 
    FROM 訂單 o
    LEFT JOIN 付款 p ON o.OrderID = p.OrderID
    ORDER BY o.訂購時間 DESC
")->fetchAll(PDO::FETCH_ASSOC);
$payRows     = $pdo->query("SELECT * FROM `付款` ORDER BY `付款時間` DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>電影院訂票系統後台</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">🎬 電影院訂票系統後台</h1>
    <div>
      <?php if (!empty($_SESSION['admin_user'])): ?>
        <span class="me-2">您好，<?= htmlspecialchars($_SESSION['admin_user']) ?></span>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="logout.php">登出</a>
    </div>
  </div>
  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-movie">電影</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-showtime">場次</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-theater">影廳</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-seat">座位</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-order">訂單</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payment">付款</button></li>
    <li class="nav-item"><a class="nav-link" href="inquery.php">📊 查詢分析</a></li>
</ul>

  <div class="tab-content pt-3">

    <!-- 電影 -->
    <div class="tab-pane fade show active" id="tab-movie">
      <h3>新增電影</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_movie" value="1">
        <div class="col-md-2"><input class="form-control" name="MovieID" placeholder="MovieID" required></div>
        <div class="col-md-2"><input class="form-control" name="片名" placeholder="片名" required></div>
        <div class="col-md-2"><input class="form-control" type="number" name="片長" placeholder="片長(分)" required></div>
        <div class="col-md-2"><input class="form-control" name="類型" placeholder="類型"></div>
        <div class="col-md-2"><input class="form-control" name="分級" placeholder="分級"></div>
        <div class="col-md-2"><input class="form-control" name="語言" placeholder="語言"></div>
        <div class="col-md-3"><input class="form-control" type="date" name="上映日" placeholder="上映日"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <h3 class="mt-4">電影列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr><th>MovieID</th><th>片名</th><th>片長</th><th>類型</th><th>分級</th><th>語言</th><th>上映日</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($movieRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['MovieID']) ?></td>
            <td><?= htmlspecialchars($r['片名']) ?></td>
            <td><?= htmlspecialchars($r['片長']) ?></td>
            <td><?= htmlspecialchars($r['類型']) ?></td>
            <td><?= htmlspecialchars($r['分級']) ?></td>
            <td><?= htmlspecialchars($r['語言']) ?></td>
            <td><?= htmlspecialchars($r['上映日']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="movie_edit.php?id=<?= urlencode($r['MovieID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這筆電影？');">
                <input type="hidden" name="del_movie" value="1">
                <input type="hidden" name="MovieID" value="<?= htmlspecialchars($r['MovieID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 場次 -->
    <div class="tab-pane fade" id="tab-showtime">
      <h3>新增場次</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_showtime" value="1">
        <div class="col-md-2"><input class="form-control" name="ShowTimeID" placeholder="ShowTimeID" required></div>
        <div class="col-md-2"><input class="form-control" type="date" name="播放日期" required></div>
        <div class="col-md-2"><input class="form-control" type="time" name="開始時間" required></div>
        <div class="col-md-2">
          <select class="form-select" name="MovieID">
            <?php foreach ($movieRows as $m): ?>
              <option value="<?= htmlspecialchars($m['MovieID']) ?>"><?= htmlspecialchars($m['片名']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="TheaterID">
            <?php foreach ($theaterRows as $t): ?>
              <option value="<?= htmlspecialchars($t['TheaterID']) ?>"><?= htmlspecialchars($t['廳名']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input class="form-control" type="number" name="可用座位數" placeholder="可用座位數" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <h3 class="mt-4">場次列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr><th>ShowTimeID</th><th>播放日期</th><th>開始時間</th><th>電影</th><th>影廳</th><th>可用座位數</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($showRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['ShowTimeID']) ?></td>
            <td><?= htmlspecialchars($r['播放日期']) ?></td>
            <td><?= htmlspecialchars($r['開始時間']) ?></td>
            <td><?= htmlspecialchars($r['電影名']) ?></td>
            <td><?= htmlspecialchars($r['影廳名']) ?></td>
            <td><?= htmlspecialchars($r['可用座位數']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="showtime_edit.php?id=<?= urlencode($r['ShowTimeID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這場次？');">
                <input type="hidden" name="del_showtime" value="1">
                <input type="hidden" name="ShowTimeID" value="<?= htmlspecialchars($r['ShowTimeID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 影廳 -->
    <div class="tab-pane fade" id="tab-theater">
      <h3>新增影廳</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_theater" value="1">
        <div class="col-md-2"><input class="form-control" name="TheaterID" placeholder="TheaterID" required></div>
        <div class="col-md-2"><input class="form-control" name="廳名" placeholder="廳名" required></div>
        <div class="col-md-2"><input class="form-control" name="類型" placeholder="類型"></div>
        <div class="col-md-2"><input class="form-control" type="number" name="容量" placeholder="容量"></div>
        <div class="col-md-2"><input class="form-control" name="樓層" placeholder="樓層"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <h3 class="mt-4">影廳列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr><th>TheaterID</th><th>廳名</th><th>類型</th><th>容量</th><th>樓層</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($theaterRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['TheaterID']) ?></td>
            <td><?= htmlspecialchars($r['廳名']) ?></td>
            <td><?= htmlspecialchars($r['類型']) ?></td>
            <td><?= htmlspecialchars($r['容量']) ?></td>
            <td><?= htmlspecialchars($r['樓層']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="theater_edit.php?id=<?= urlencode($r['TheaterID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這個影廳？');">
                <input type="hidden" name="del_theater" value="1">
                <input type="hidden" name="TheaterID" value="<?= htmlspecialchars($r['TheaterID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 座位 -->
    <div class="tab-pane fade" id="tab-seat">
      <h3>新增座位</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_seat" value="1">
        <div class="col-md-2"><input class="form-control" name="SeatID" placeholder="SeatID" required></div>
        <div class="col-md-1"><input class="form-control" name="RowNo" placeholder="排"></div>
        <div class="col-md-1"><input class="form-control" name="SeatNo" placeholder="號"></div>
        <div class="col-md-2"><input class="form-control" name="價格類型" placeholder="價格類型"></div>
        <div class="col-md-2"><input class="form-control" name="狀態" placeholder="狀態"></div>
        <div class="col-md-2"><input class="form-control" type="datetime-local" name="更新時間"></div>
        <div class="col-md-2"><input class="form-control" name="TheaterID" placeholder="TheaterID"></div>
        <div class="col-md-2"><input class="form-control" name="ShowTimeID" placeholder="ShowTimeID"></div>
        <div class="col-md-2"><input class="form-control" type="number" name="票價" placeholder="票價"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <div class="mt-3 d-flex gap-3">
    <a href="batch_seat.php" class="btn btn-success">➕ 批量新增座位</a>
    <a href="batch_delete_seat.php" class="btn btn-danger">🗑 批量刪除座位</a>
    <a href="seat_auto_generate.php" class="btn btn-primary">⚙ 自動生成座位</a>
</div>

      <h3 class="mt-4">座位列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr>
          <th>SeatID</th><th>排</th><th>號</th><th>價格類型</th><th>狀態</th><th>更新時間</th>
          <th>TheaterID</th><th>ShowTimeID</th><th>票價</th><th>操作</th>
        </tr></thead>
        <tbody>
        <?php
        // 👇 新增排序，確保顯示穩定
        $seatStmt = $pdo->query("SELECT * FROM 座位 ORDER BY CHAR_LENGTH(RowNo), RowNo, CAST(SeatNo AS UNSIGNED), SeatID");
        $seatRows = $seatStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($seatRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['SeatID']) ?></td>
            <td><?= htmlspecialchars($r['RowNo']) ?></td>
            <td><?= htmlspecialchars($r['SeatNo']) ?></td>
            <td><?= htmlspecialchars($r['價格類型']) ?></td>
            <td><?= htmlspecialchars($r['狀態']) ?></td>
            <td><?= htmlspecialchars($r['更新時間']) ?></td>
            <td><?= htmlspecialchars($r['TheaterID']) ?></td>
            <td><?= htmlspecialchars($r['ShowTimeID']) ?></td>
            <td><?= htmlspecialchars($r['票價']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="seat_edit.php?id=<?= urlencode($r['SeatID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這個座位？');">
                <input type="hidden" name="del_seat" value="1">
                <input type="hidden" name="SeatID" value="<?= htmlspecialchars($r['SeatID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 訂單 -->
    <div class="tab-pane fade" id="tab-order">
      <h3>新增訂單</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_order" value="1">
        <div class="col-md-2"><input class="form-control" name="OrderID" placeholder="OrderID" required></div>
        <div class="col-md-2"><input class="form-control" name="取票代碼" placeholder="取票代碼"></div>
        <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="總金額" placeholder="總金額"></div>
        <div class="col-md-3"><input class="form-control" type="datetime-local" name="訂購時間"></div>
        <div class="col-md-2"><input class="form-control" name="ShowTimeID" placeholder="ShowTimeID"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <h3 class="mt-4">訂單列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr><th>OrderID</th><th>取票代碼</th><th>總金額</th><th>付款狀態</th><th>訂購時間</th><th>ShowTimeID</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($orderRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['OrderID']) ?></td>
            <td><?= htmlspecialchars($r['取票代碼']) ?></td>
            <td><?= htmlspecialchars($r['總金額']) ?></td>
            <td><?= htmlspecialchars($r['付款狀態']) ?></td>
            <td><?= htmlspecialchars($r['訂購時間']) ?></td>
            <td><?= htmlspecialchars($r['ShowTimeID']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="order_edit.php?id=<?= urlencode($r['OrderID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這張訂單？');">
                <input type="hidden" name="del_order" value="1">
                <input type="hidden" name="OrderID" value="<?= htmlspecialchars($r['OrderID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 付款 -->
    <div class="tab-pane fade" id="tab-payment">
      <h3>新增付款</h3>
      <form method="post" class="row g-2">
        <input type="hidden" name="add_payment" value="1">
        <div class="col-md-2"><input class="form-control" name="PaymentID" placeholder="PaymentID" required></div>
        <div class="col-md-2"><input class="form-control" name="OrderID" placeholder="OrderID" required></div>
        <div class="col-md-2"><input class="form-control" name="付款方式" placeholder="付款方式"></div>
        <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="付款金額" placeholder="付款金額"></div>
        <div class="col-md-2"><input class="form-control" name="付款狀態" placeholder="付款狀態"></div>
        <div class="col-md-3"><input class="form-control" type="datetime-local" name="付款時間"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">新增</button></div>
      </form>

      <h3 class="mt-4">付款列表</h3>
      <table class="table table-striped table-bordered">
        <thead><tr><th>PaymentID</th><th>OrderID</th><th>付款方式</th><th>付款金額</th><th>付款狀態</th><th>付款時間</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($payRows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['PaymentID']) ?></td>
            <td><?= htmlspecialchars($r['OrderID']) ?></td>
            <td><?= htmlspecialchars($r['付款方式']) ?></td>
            <td><?= htmlspecialchars($r['付款金額']) ?></td>
            <td><?= htmlspecialchars($r['付款狀態']) ?></td>
            <td><?= htmlspecialchars($r['付款時間']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-warning btn-sm" href="payment_edit.php?pid=<?= urlencode($r['PaymentID']) ?>">修改</a>
              <form method="post" onsubmit="return confirm('確定刪除這筆付款？');">
                <input type="hidden" name="del_payment" value="1">
                <input type="hidden" name="PaymentID" value="<?= htmlspecialchars($r['PaymentID']) ?>">
                <button class="btn btn-danger btn-sm">刪除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
