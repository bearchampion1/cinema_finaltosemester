<?php
require_once '../config.php';
if (!isset($_GET['id'])) die("未指定 SeatID");
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM `座位` WHERE `SeatID` = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("找不到座位：" . htmlspecialchars($id));

// 處理 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 修正 RowNo（字母排）
    $inputRow = trim($_POST['RowNo']);
    if (is_numeric($inputRow)) {
        $RowNo = chr(64 + intval($inputRow));  // 1 → A
    } else {
        $RowNo = strtoupper($inputRow);        // a → A
    }

    $sql = "UPDATE `座位` 
            SET `RowNo`=?, `SeatNo`=?, `價格類型`=?, `狀態`=?, 
                `更新時間`=?, `TheaterID`=?, `ShowTimeID`=?, `票價`=? 
            WHERE `SeatID`=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $RowNo,
        $_POST['SeatNo'],
        $_POST['價格類型'],
        $_POST['狀態'],
        $_POST['更新時間'] ?: null,
        $_POST['TheaterID'],
        $_POST['ShowTimeID'],
        $_POST['票價'],
        $id
    ]);

    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>修改座位</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php require_once __DIR__ . '/header.php'; ?>

<h2 class="mb-4">修改座位：<?= htmlspecialchars($id) ?></h2>

<form method="post" class="row g-3">

  <div class="col-md-2">
    <label class="form-label">第幾排</label>
    <select class="form-control" name="RowNo">
        <?php
        // 自動產生 A~Z
        for ($i = 1; $i <= 26; $i++):
            $letter = chr(64 + $i); // 1→A
        ?>
            <option value="<?= $letter ?>" 
                <?= ($row['RowNo'] === $letter ? 'selected' : '') ?>>
                <?= $letter ?>
            </option>
        <?php endfor; ?>
    </select>
</div>


  <div class="col-md-2">
    <label class="form-label">第幾號</label>
    <input class="form-control" type="number" name="SeatNo" value="<?= htmlspecialchars($row['SeatNo']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">價格類型</label>
    <input class="form-control" name="價格類型" value="<?= htmlspecialchars($row['價格類型']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">狀態</label>
    <input class="form-control" name="狀態" value="<?= htmlspecialchars($row['狀態']) ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">更新時間</label>
    <input class="form-control" type="datetime-local" name="更新時間" 
           value="<?= $row['更新時間'] ? date('Y-m-d\TH:i', strtotime($row['更新時間'])) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">TheaterID</label>
    <input class="form-control" name="TheaterID" value="<?= htmlspecialchars($row['TheaterID']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">ShowTimeID</label>
    <input class="form-control" name="ShowTimeID" value="<?= htmlspecialchars($row['ShowTimeID']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">票價</label>
    <input class="form-control" type="number" name="票價" value="<?= htmlspecialchars($row['票價']) ?>">
  </div>

  <div class="col-md-12">
    <button class="btn btn-success px-4">儲存</button>
    <a class="btn btn-secondary px-4" href="index.php">返回</a>
  </div>

</form>

</body>
</html>
