<?php
/**
 * Email 設定檔範本
 * 
 * 使用說明：
 * 1. 複製此檔案為 email_config.php
 * 2. 填入您的 Gmail SMTP 資訊
 * 3. 詳細步驟請參考「如何取得Gmail應用程式密碼.txt」
 */

return [
    // SMTP 伺服器設定
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',  // 使用 TLS 加密
    
    // Gmail 帳號設定（請填入您的資訊）
    'smtp_username' => 'your-email@gmail.com',        // ← 請修改：您的 Gmail 地址
    'smtp_password' => 'your-16-digit-app-password',  // ← 請修改：Gmail 應用程式密碼（16位）
    
    // 寄件人資訊
    'from_email' => 'your-email@gmail.com',  // ← 請修改：與 smtp_username 相同
    'from_name' => '🎬 電影院訂票系統',      // 可自訂顯示名稱
    
    // 其他設定
    'charset' => 'UTF-8',
    'debug' => false  // 設為 true 可顯示詳細錯誤訊息（僅開發時使用）
];
