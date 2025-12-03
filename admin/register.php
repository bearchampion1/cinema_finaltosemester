<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

ensure_admin_table_exists($pdo);

// 必須先登入才能修改密碼
require_admin();

$error = '';
$success = '';
$current_user = $_SESSION['admin_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $new_pass2 = $_POST['new_password2'] ?? '';
    
    if ($old_pass === '' || $new_pass === '') {
        $error = '請填寫所有欄位';
    } elseif ($new_pass !== $new_pass2) {
        $error = '兩次新密碼不一致';
    } elseif (strlen($new_pass) < 6) {
        $error = '新密碼至少需要 6 個字元';
    } else {
        // 驗證舊密碼
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE username = :u');
        $stmt->execute([':u' => $current_user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($old_pass, $row['password_hash'])) {
            // 舊密碼正確，更新新密碼
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE admin_users SET password_hash = :h WHERE username = :u');
            $upd->execute([':h' => $new_hash, ':u' => $current_user]);
            $success = '密碼修改成功！';
        } else {
            $error = '舊密碼錯誤';
        }
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>修改密碼</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<?php include 'header.php'; ?>
<div class="container" style="max-width:560px">
  <h3 class="mb-3">🔐 修改管理員密碼</h3>
  <div class="alert alert-info">
    <strong>當前帳號：</strong> <?= htmlspecialchars($current_user) ?>
  </div>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">舊密碼 <span class="text-danger">*</span></label>
      <input class="form-control" type="password" name="old_password" required>
    </div>
    <div class="mb-3">
      <label class="form-label">新密碼 <span class="text-danger">*</span></label>
      <input class="form-control" type="password" name="new_password" minlength="6" required>
      <small class="text-muted">至少 6 個字元</small>
    </div>
    <div class="mb-3">
      <label class="form-label">確認新密碼 <span class="text-danger">*</span></label>
      <input class="form-control" type="password" name="new_password2" minlength="6" required>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary">修改密碼</button>
      <a class="btn btn-outline-secondary" href="index.php">返回首頁</a>
    </div>
  </form>
</div>
</body>
</html>
