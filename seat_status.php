<?php
require_once "config.php";
header("Content-Type: application/json; charset=utf-8");

if (!isset($_GET['showtime'])) {
    echo json_encode(["status" => "error", "msg" => "Missing ShowTimeID"]);
    exit;
}

$showtimeID = $_GET['showtime'];

/* 查詢座位 */
$sql = "SELECT SeatID, RowNo, SeatNo, 狀態, 票價, LockUntil
        FROM 座位
        WHERE ShowTimeID = :stid
        ORDER BY RowNo, SeatNo";
$stmt = $pdo->prepare($sql);
$stmt->execute([':stid' => $showtimeID]);
$seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = time();
$data = [];

foreach ($seats as $s) {

    $status = $s['狀態'];
    $lockRemaining = 0;

    // 🟡 若座位有 LockUntil → 判斷是否過期
    if (!empty($s['LockUntil'])) {
        $lockTime = strtotime($s['LockUntil']);
        if ($lockTime > $now) {
            $status = "鎖定";
            $lockRemaining = $lockTime - $now; // 秒數
        } else {
            // 鎖定過期 → 自動還原可售
            $status = "可售";

            $upd = $pdo->prepare("
                UPDATE 座位 
                SET 狀態='可售', LockUntil=NULL
                WHERE SeatID = :sid
            ");
            $upd->execute([':sid' => $s['SeatID']]);
        }
    }

    // 納入回傳資料
    $data[] = [
        "SeatID" => $s['SeatID'],
        "RowNo" => $s['RowNo'],
        "SeatNo" => $s['SeatNo'],
        "price" => $s['票價'],
        "status" => $status,
        "remaining" => $lockRemaining  // 鎖定剩餘秒數
    ];
}

echo json_encode([
    "status" => "ok",
    "seats" => $data
]);
