<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['showtime'])) {
    die("❌ 未指定場次 ID");
}

$showtime_id = $_GET['showtime'];

/* 🎬 查詢場次資訊 */
$sql = "SELECT s.`ShowTimeID`, s.`播放日期`, s.`開始時間`, m.`片名`, t.`廳名`
        FROM `場次` s
        JOIN `movie` m ON s.`MovieID` = m.`MovieID`
        JOIN `影廳` t ON s.`TheaterID` = t.`TheaterID`
        WHERE s.`ShowTimeID` = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $showtime_id]);
$show = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$show) die("查無此場次");

/* 計算是否允許購票（距開場 10 分鐘內禁止購票） */
$show_timestamp = strtotime($show['播放日期'] . ' ' . $show['開始時間']);
$time_diff = $show_timestamp - time();
$canBook = ($time_diff > 600); // 600 秒 = 10 分鐘

/* 💺 查詢座位資料 */
$sql = "SELECT * FROM `座位` WHERE `ShowTimeID` = :id ORDER BY `RowNo`, `SeatNo`";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $showtime_id]);
$seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 將座位分群（以 RowNo 為鍵） */
$rows = [];
foreach ($seats as $seat) {
    $rows[$seat['RowNo']][] = $seat;
}
?>

<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>🎟 選座購票 - <?= htmlspecialchars($show['片名']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.seat {
  width: 35px; height: 35px; margin: 3px;
  border-radius: 6px; display: inline-block;
  line-height: 35px; text-align: center;
  font-size: 14px; font-weight: 500;
  cursor: pointer; transition: 0.2s;
}
.seat.available { background: #e2e6ea; }
.seat.selected { background: #0078D7; color: white; }
.seat.occupied { background: #dc3545; color: white; cursor: not-allowed; } /* 🔴 已售紅色 */
</style>
</head>
<body class="p-4">
<div class="container">
  <h2 class="text-center mb-3">🎬 <?= htmlspecialchars($show['片名']) ?></h2>
  <p class="text-center text-muted">
    🏢 <?= htmlspecialchars($show['廳名']) ?>　
    📅 <?= htmlspecialchars($show['播放日期']) ?>　
    ⏰ <?= htmlspecialchars($show['開始時間']) ?>
  </p>
  <hr>

  <?php if (!$canBook): ?>
    <div class="alert alert-warning text-center">⚠️ 此場次距開場不足 10 分鐘，線上訂票已關閉。如需協助請洽櫃檯或管理員。</div>
  <?php endif; ?>

  <!-- 💺 座位顯示區 -->
  <form method="post" action="confirm_order.php">
    <input type="hidden" name="ShowTimeID" value="<?= htmlspecialchars($showtime_id) ?>">
    <input type="hidden" name="selectedSeats" id="selectedSeats">
    <input type="hidden" name="totalAmount" id="totalAmount">

    <div class="text-center mb-3">
      <h5 class="text-secondary">請選擇座位</h5>
      <div class="border rounded bg-white p-3 d-inline-block shadow-sm">
        <?php foreach ($rows as $rowNum => $rowSeats): ?>
          <div class="mb-2">
            <span class="me-2 fw-bold"><?= $rowNum ?>排：</span>
            <?php foreach ($rowSeats as $seat): ?>
              <?php
              $status = $seat['狀態'];
              $isAvailable = ($status == '可售' || $status == '空位');
              ?>
              <div 
                class="seat <?= $isAvailable ? 'available' : 'occupied' ?>"
                data-seatid="<?= htmlspecialchars($seat['SeatID']) ?>"
                data-seatname="<?= $rowNum . '排' . $seat['SeatNo'] . '號' ?>"
                data-price="<?= htmlspecialchars($seat['票價']) ?>"
                <?= $canBook ? 'onclick="toggleSeat(this)"' : '' ?>
                >
                <?= htmlspecialchars($seat['SeatNo']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 🧮 動態顯示資訊 -->
    <div class="text-center mb-3">
      <p class="fs-5">已選座位：<span id="seatCount">0</span> 張</p>
      <p class="fs-5 text-success">總金額：NT$ <span id="totalDisplay">0</span></p>
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-primary px-4" id="submitBtn" disabled>確認購票</button>
      <a href="user_search.php" class="btn btn-secondary px-4">返回查詢</a>
    </div>
  </form>
</div>

<script>
let selected = [];
let total = 0;
const showtimeID = "<?= $showtime_id ?>";
const canBook = <?= $canBook ? 'true' : 'false' ?>;

// === 將座位更新到畫面 ===
function refreshSeats() {
    fetch(`seat_status.php?showtime=${showtimeID}`)
        .then(res => res.json())
        .then(data => {
            if (data.status !== "ok") return;

            data.seats.forEach(seat => {
                let div = document.querySelector(`[data-seatid="${seat.SeatID}"]`);
                if (!div) return;

                // 若自己已選，不覆蓋樣式
                const isSelfSelected = selected.some(s => s.id === seat.SeatID);

                // 更新座位狀態
                if (!isSelfSelected) {
                    if (seat.status === "可售") {
                        div.className = "seat available";
                        div.textContent = seat.SeatNo;
                    }
                    else if (seat.status === "鎖定") {
                        div.className = "seat occupied";
                        div.textContent = `🔒${seat.remaining}`;
                    }
                    else if (seat.status === "已售") {
                        div.className = "seat occupied";
                        div.textContent = seat.SeatNo;
                    }
                }

                // 若已售 → 永遠不能點
                if (seat.status === "已售") {
                    div.style.pointerEvents = "none";
                }
            });
        });
}

// === 每 3 秒自動更新 ===
setInterval(refreshSeats, 3000);


// === 使用者手動點選 ===
function toggleSeat(div) {
  if (!canBook) return; // 若不可購票，直接忽略點選
    if (div.classList.contains("occupied")) return;

    const seatId = div.dataset.seatid;
    const seatName = div.dataset.seatname;
    const price = parseFloat(div.dataset.price || 0);

    // 已選 → 取消
    if (div.classList.contains("selected")) {
        div.classList.remove("selected");
        selected = selected.filter(s => s.id !== seatId);

        // 還原樣式
        div.classList.add("available");
    } 
    // 未選 → 新增
    else {
        div.classList.remove("available");
        div.classList.add("selected");
        selected.push({ id: seatId, name: seatName, price });
    }

    // 計算金額
    total = selected.reduce((sum, s) => sum + s.price, 0);

    // 更新畫面
    document.getElementById("selectedSeats").value = JSON.stringify(selected);
    document.getElementById("totalAmount").value = total;
    document.getElementById("seatCount").textContent = selected.length;
    document.getElementById("totalDisplay").textContent = total;
    document.getElementById("submitBtn").disabled = (selected.length === 0);
}
</script>

</body>
</html>
