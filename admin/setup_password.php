<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

ensure_admin_table_exists($pdo);

// 檢查是否有待設定的帳號
if (empty($_SESSION['setup_admin_user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['setup_admin_user'];
$error = '';
$success = '';

// 再次驗證帳號存在且密碼未設定
$stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE username = :u');
$stmt->execute([':u' => $username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    unset($_SESSION['setup_admin_user']);
    header('Location: login.php');
    exit;
}

if (!empty($row['password_hash'])) {
    // 密碼已經設定過了
    unset($_SESSION['setup_admin_user']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $new_pass2 = $_POST['new_password2'] ?? '';
    
    if ($new_pass === '') {
        $error = '請輸入新密碼';
    } elseif (strlen($new_pass) < 6) {
        $error = '密碼至少需要 6 個字元';
    } elseif ($new_pass !== $new_pass2) {
        $error = '兩次密碼不一致';
    } else {
        // 設定密碼
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE admin_users SET password_hash = :h WHERE username = :u');
        $upd->execute([':h' => $hash, ':u' => $username]);
        
        // 設定成功，自動登入
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $username;
        unset($_SESSION['setup_admin_user']);
        
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>初次設定密碼</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .setup-card { 
      max-width: 500px; 
      margin: 80px auto; 
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      border-radius: 15px;
      overflow: hidden;
    }
    .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }
    .card-header h3 {
      margin: 0;
      font-size: 24px;
      font-weight: bold;
    }
    .card-header p {
      margin: 10px 0 0 0;
      opacity: 0.9;
      font-size: 14px;
    }
    .card-body {
      padding: 30px;
      background: white;
    }
    .welcome-badge {
      display: inline-block;
      background: rgba(255,255,255,0.2);
      padding: 8px 16px;
      border-radius: 20px;
      margin-top: 10px;
      font-size: 16px;
      font-weight: 600;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="setup-card card">
    <div class="card-header">
      <h3>🔐 初次設定密碼</h3>
      <p>歡迎使用後台管理系統</p>
      <div class="welcome-badge">
        👤 <?= htmlspecialchars($username) ?>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-info">
        <strong>🎉 首次登入設定</strong><br>
        您的管理員帳號已建立，請設定您的登入密碼。
      </div>
      
      <?php if ($error): ?>
        <div class="alert alert-danger">
          <strong>❌ 錯誤</strong><br>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="post">
        <div class="mb-3">
          <label class="form-label fw-bold">新密碼 <span class="text-danger">*</span></label>
          <input class="form-control form-control-lg" type="password" name="new_password" minlength="6" required autofocus>
          <small class="text-muted">至少 6 個字元，建議使用英文、數字與符號組合</small>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">確認新密碼 <span class="text-danger">*</span></label>
          <input class="form-control form-control-lg" type="password" name="new_password2" minlength="6" required>
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">
          ✓ 完成設定並登入
        </button>
      </form>
      
      <div class="text-center mt-4">
        <small class="text-muted">
          設定完成後將自動登入後台管理系統
        </small>
      </div>
    </div>
  </div>
</div>
</body>
</html>
