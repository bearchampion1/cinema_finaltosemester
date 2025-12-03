<?php
/**
 * 資料庫設定檔範本
 * 
 * 使用說明：
 * 1. 複製此檔案為 config.php
 * 2. 填入您的資料庫連線資訊
 * 3. 確保資料庫已建立並匯入 SQL 檔案
 */

// 資料庫連線設定（請填入您的資訊）
$host = 'localhost';           // 資料庫主機（通常是 localhost）
$dbname = 'movie_booking';     // 資料庫名稱
$username = 'root';            // 資料庫使用者名稱
$password = '';                // ← 請修改：資料庫密碼

// 字元編碼
$charset = 'utf8mb4';

// PDO 連線選項
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // 啟用例外處理
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // 預設使用關聯陣列
    PDO::ATTR_EMULATE_PREPARES => false,  // 使用真正的預備語句
];

// 建立資料庫連線
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // 錯誤處理（生產環境應記錄到日誌而非直接顯示）
    die("資料庫連線失敗：" . $e->getMessage());
}

// 設定時區（可選）
date_default_timezone_set('Asia/Taipei');

// 啟動 Session（如果尚未啟動）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 管理員測試模式（開發時使用，正式環境請設為 false）
$ADMIN_TEST_MODE = false;
