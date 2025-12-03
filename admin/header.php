<?php
// admin/header.php
// 確保載入驗證並在後台頁面顯示管理員名稱與登出按鈕
require_once __DIR__ . '/auth.php';
// 強制登入
require_admin();

$admin_user = $_SESSION['admin_user'] ?? '管理員';

// 簡單的後台標頭（Bootstrap）
echo '<div class="d-flex justify-content-between align-items-center mb-3">';
echo '<h4 class="m-0">後台管理系統</h4>';
echo '<div class="text-end">';
echo '<span class="me-3">歡迎，' . htmlspecialchars($admin_user, ENT_QUOTES, 'UTF-8') . '</span>';
echo '<a href="register.php" class="btn btn-sm btn-outline-primary me-2">修改密碼</a>';
echo '<a href="logout.php" class="btn btn-sm btn-outline-secondary">登出</a>';
echo '</div>';
echo '</div>';

?>
