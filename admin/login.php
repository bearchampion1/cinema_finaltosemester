<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

// Ensure admin table exists
ensure_admin_table_exists($pdo);

// If already logged in, redirect
if (is_admin_logged_in() || (!empty($ADMIN_TEST_MODE) && $ADMIN_TEST_MODE)) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Test-mode quick login
    if (!empty($ADMIN_TEST_MODE) && isset($_POST['test_login'])) {
      $_SESSION['is_admin'] = true;
      $_SESSION['admin_user'] = 'TEST_ADMIN';
      header('Location: index.php'); exit;
    }

    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if ($user === '') {
        $error = '請填寫帳號';
    } else {
        // 檢查帳號是否存在
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE username = :u');
        $stmt->execute([':u'=>$user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            $error = '此帳號不存在';
        } elseif (empty($row['password_hash'])) {
            // 帳號存在但未設定密碼，導向設定密碼頁面
            $_SESSION['setup_admin_user'] = $user;
            header('Location: setup_password.php'); exit;
        } elseif ($pass === '') {
            $error = '請填寫密碼';
        } elseif (password_verify($pass, $row['password_hash'])) {
            // 密碼正確，登入成功
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_user'] = $user;
            header('Location: index.php'); exit;
        } else {
            $error = '密碼錯誤';
        }
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>管理員登入</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f8f9fa}</style>
</head>
<body class="p-4">
<div class="container" style="max-width:560px">
  <h3 class="mb-3">後台管理員登入</h3>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-2">
      <label class="form-label">帳號</label>
      <input class="form-control" name="username" required>
    </div>
    <div class="mb-2">
      <label class="form-label" >密碼</label>
      <input class="form-control" type="password" name="password" placeholder="請輸入密碼，新管理員請隨便打一個數字" required>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary">登入</button>
    </div>
  </form>

  <?php if (!empty($ADMIN_TEST_MODE)): ?>
    <hr>
    <form method="post">
      <input type="hidden" name="test_login" value="1">
      <button class="btn btn-warning">測試模式登入（無需帳密）</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
