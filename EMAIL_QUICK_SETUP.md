# 📧 Email 功能快速設定指南

## ✅ PHPMailer 已安裝完成

**注意：** PHPMailer 源碼已存在於專案中，無需額外安裝 Composer 或 vendor 套件。

## 🔧 設定步驟

### 步驟 1: 取得 Gmail 應用程式密碼

1. 登入您的 Google 帳號
2. 前往 [Google 帳戶安全性設定](https://myaccount.google.com/security)
3. 確認已啟用「兩步驟驗證」（必須）
4. 搜尋並點選「應用程式密碼」
5. 選擇應用程式：**郵件**
6. 選擇裝置：**Windows 電腦** 或其他
7. 點選「產生」
8. **複製產生的 16 位密碼**（格式：xxxx xxxx xxxx xxxx，去掉空格）

### 步驟 2: 修改設定檔

編輯 `email_config.php` 檔案，修改以下內容：

```php
return [
    // Gmail 帳號資訊（⚠️ 請修改這裡）
    'smtp_username' => 'your-email@gmail.com',      // 改成您的 Gmail
    'smtp_password' => 'abcdabcdabcdabcd',          // 改成剛才複製的 16 位密碼
    
    'from_email'    => 'your-email@gmail.com',      // 改成您的 Gmail
    'from_name'     => '電影院線上訂票系統',
];
```

**範例：**
```php
'smtp_username' => 'cinema2025@gmail.com',
'smtp_password' => 'abcd1234efgh5678',  // 16 位應用程式密碼
'from_email'    => 'cinema2025@gmail.com',
```

### 步驟 3: 測試 Email 功能

完成設定後，進行一次訂票測試：

1. 前往訂票頁面
2. 選擇座位並填寫姓名與 Email
3. 完成訂票
4. 檢查 Email 信箱（包含垃圾郵件資料夾）

## 📋 已整合的功能

✅ **自動發送訂票確認信**
- 取票代碼（大字體顯示）
- 完整電影資訊
- 座位清單
- 訂單詳情
- QR Code 圖片
- 查看電子票券連結

✅ **錯誤處理**
- Email 發送失敗不影響訂票
- 錯誤記錄至 PHP error log

✅ **支援 HTML 與純文字**
- HTML 精美排版
- 純文字版本作為備用

## 🔍 故障排除

### 問題 1: Email 未收到

**檢查項目：**
1. 確認 Gmail 應用程式密碼正確（16 位，不含空格）
2. 檢查垃圾郵件資料夾
3. 確認 Gmail 帳號已啟用「兩步驟驗證」
4. 檢查 PHP error log：`tail -f /path/to/php_error.log`

**除錯模式：**
在 `email_config.php` 啟用除錯：
```php
'debug' => true,  // 改為 true
```

### 問題 2: SMTP 連線失敗

**可能原因：**
- 防火牆封鎖 587 port
- Gmail 安全性設定阻擋

**解決方式：**
1. 檢查防火牆設定
2. 登入 Gmail 檢查「安全性活動」
3. 允許「較不安全的應用程式存取」（不建議）

### 問題 3: 應用程式密碼選項找不到

**原因：** 未啟用兩步驟驗證

**解決：**
1. 前往 [兩步驟驗證設定](https://myaccount.google.com/signinoptions/two-step-verification)
2. 按照步驟啟用
3. 啟用後才會出現「應用程式密碼」選項

## 📁 相關檔案

- `send_ticket_email.php` - Email 發送函數
- `email_config.php` - SMTP 設定檔（⚠️ 需修改）
- `confirm_order.php` - 訂單處理與 Email 發送
- `PHPMailer 7.0.1 source code/` - PHPMailer 源碼目錄

## 🚀 測試建議

**測試用 Email：**
- 使用您自己的 Email 測試
- 測試不同 Email 服務（Gmail、Yahoo、Outlook）
- 檢查 HTML 顯示是否正常
- 確認 QR Code 圖片可正常載入

## 📞 需要協助？

如果遇到問題，請檢查：
1. PHP error log
2. Gmail 帳戶活動記錄
3. `email_config.php` 設定是否正確

---

**設定完成後，Email 功能即可正常運作！** 🎉
