<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

ensure_admin_table_exists($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if ($user === '' || $pass === '') {
        $error = '請填寫帳號與密碼';
    } elseif ($pass !== $pass2) {
        $error = '兩次密碼不一致';
    } else {
        // 檢查是否存在
        $chk = $pdo->prepare('SELECT 1 FROM admin_users WHERE username = :u');
        $chk->execute([':u'=>$user]);
        if ($chk->fetchColumn()) {
            $error = '此帳號已存在';
        } else {
          $hash = password_hash($pass, PASSWORD_DEFAULT);
          $ins = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :h)');
          $ins->execute([':u'=>$user, ':h'=>$hash]);
          $_SESSION['is_admin'] = true;
          $_SESSION['admin_user'] = $user;
          header('Location: index.php'); exit;
        }
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>註冊管理員帳號</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width:560px">
  <h3 class="mb-3">註冊後台帳號</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-2"><label class="form-label">帳號</label><input class="form-control" name="username" required></div>
    <div class="mb-2"><label class="form-label">密碼</label><input class="form-control" type="password" name="password" required></div>
    <div class="mb-2"><label class="form-label">再次輸入密碼</label><input class="form-control" type="password" name="password2" required></div>
    <div class="d-flex gap-2"><button class="btn btn-primary">註冊並登入</button><a class="btn btn-outline-secondary" href="login.php">返回登入</a></div>
  </form>
</div>
</body>
</html>
