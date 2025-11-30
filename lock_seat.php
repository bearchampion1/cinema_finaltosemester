<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status"=>"error", "msg"=>"invalid method"]);
    exit;
}

$seatID = $_POST['seatID'] ?? null;

if (!$seatID) {
    echo json_encode(["status"=>"error", "msg"=>"missing seatID"]);
    exit;
}

// 🔒 鎖定秒數
$lock_seconds = 120;

try {
    // 檢查該座位所屬場次是否距開場不足 10 分鐘
    $sstmt = $pdo->prepare("SELECT ShowTimeID FROM 座位 WHERE SeatID = :sid");
    $sstmt->execute([':sid' => $seatID]);
    $srow = $sstmt->fetch(PDO::FETCH_ASSOC);
    if ($srow) {
        $tstmt = $pdo->prepare("SELECT `播放日期`,`開始時間` FROM `場次` WHERE ShowTimeID = :id");
        $tstmt->execute([':id' => $srow['ShowTimeID']]);
        $trow = $tstmt->fetch(PDO::FETCH_ASSOC);
        if ($trow) {
            $show_ts = strtotime($trow['播放日期'] . ' ' . $trow['開始時間']);
            $remain = $show_ts - time();
            if ($remain <= 600) {
                echo json_encode(["status"=>"error","msg"=>"此場次距開場不足 10 分鐘，暫不接受鎖位"]); exit;
            }
        }
    }
    // 查座位狀態
    $stmt = $pdo->prepare("SELECT 狀態, 更新時間 FROM 座位 WHERE SeatID = :sid");
    $stmt->execute([':sid' => $seatID]);
    $seat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seat) {
        echo json_encode(["status"=>"error", "msg"=>"seat not found"]);
        exit;
    }

    // 已售 → 不能鎖
    if ($seat['狀態'] === '已售') {
        echo json_encode(["status"=>"sold"]);
        exit;
    }

    // 若是鎖定 → 判斷是否過期
    if ($seat['狀態'] === '鎖定') {
        $last = strtotime($seat['更新時間']);
        $remain = $lock_seconds - (time() - $last);

        if ($remain > 0) {
            echo json_encode([
                "status"=>"locked",
                "remain"=>$remain
            ]);
            exit;
        }

        // 超過 120 秒 → 自動變回可售（重新鎖定）
    }

    // ⭐ 寫入「鎖定」
    $stmt = $pdo->prepare("
        UPDATE 座位
        SET 狀態='鎖定', 更新時間=NOW()
        WHERE SeatID = :sid
    ");
    $stmt->execute([':sid' => $seatID]);

    echo json_encode([
        "status"=>"ok",
        "msg"=>"locked",
        "remain"=>$lock_seconds
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["status"=>"error", "msg"=>$e->getMessage()]);
    exit;
}
?>
