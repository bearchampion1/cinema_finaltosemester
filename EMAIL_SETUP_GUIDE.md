# 📧 Email 發送功能設定指南

## 問題說明
本地開發環境沒有郵件伺服器,因此 `mail()` 函數無法運作,已暫時停用 Email 功能。

## 解決方案

### 方案 1: 使用 PHPMailer + Gmail SMTP (推薦)

這是最穩定可靠的方式,適用於生產環境。

#### 步驟 1: 安裝 PHPMailer

```bash
# 使用 Composer 安裝
composer require phpmailer/phpmailer

# 或手動下載
# https://github.com/PHPMailer/PHPMailer
```

#### 步驟 2: 建立 Email 發送函數

建立檔案 `send_ticket_email.php`:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendTicketEmail($customerEmail, $customerName, $ticketCode, $orderID, $movieInfo, $seatList, $totalAmount) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP 設定
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';  // 您的 Gmail
        $mail->Password   = 'your-app-password';      // Gmail 應用程式密碼
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // 寄件人與收件人
        $mail->setFrom('your-email@gmail.com', '電影院訂票系統');
        $mail->addAddress($customerEmail, $customerName);
        
        // Email 內容
        $mail->isHTML(true);
        $mail->Subject = "🎬 訂票成功通知 - 取票代碼：{$ticketCode}";
        
        // 組合座位清單
        $seatNames = array_map(function($s) { return $s['name']; }, $seatList);
        $seatListText = implode(', ', $seatNames);
        
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='zh-Hant'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: '微軟正黑體', Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 30px; }
                .ticket-code { background-color: #fff3cd; border: 2px dashed #ffc107; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .ticket-code .code { font-size: 32px; font-weight: bold; color: #d39e00; letter-spacing: 3px; }
                .info-section { margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 8px; }
                .info-row { padding: 8px 0; border-bottom: 1px solid #dee2e6; }
                .info-row:last-child { border-bottom: none; }
                .info-label { font-weight: bold; color: #495057; display: inline-block; width: 120px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 訂票成功！</h1>
                    <p style='margin: 10px 0 0 0;'>感謝您的訂購</p>
                </div>
                
                <div class='content'>
                    <p>親愛的 <strong>{$customerName}</strong>，您好：</p>
                    <p>您的電影票已訂購成功！</p>
                    
                    <div class='ticket-code'>
                        <h2 style='margin: 0 0 10px 0; color: #856404;'>📱 取票代碼</h2>
                        <div class='code'>{$ticketCode}</div>
                        <p style='margin: 10px 0 0 0; color: #856404; font-size: 14px;'>請憑此代碼至櫃檯或自助機取票</p>
                    </div>
                    
                    <div class='info-section'>
                        <h3 style='margin-top: 0; color: #495057;'>🎬 電影資訊</h3>
                        <div class='info-row'>
                            <span class='info-label'>電影名稱：</span>{$movieInfo['片名']}
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>類型：</span>{$movieInfo['類型']}
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>影廳：</span>{$movieInfo['廳名']}
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>播放日期：</span>{$movieInfo['播放日期']}
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>開始時間：</span>{$movieInfo['開始時間']}
                        </div>
                    </div>
                    
                    <div class='info-section'>
                        <h3 style='margin-top: 0; color: #495057;'>💺 訂購座位</h3>
                        <div>{$seatListText}</div>
                    </div>
                    
                    <div class='info-section'>
                        <h3 style='margin-top: 0; color: #495057;'>💰 訂單資訊</h3>
                        <div class='info-row'>
                            <span class='info-label'>訂單編號：</span>{$orderID}
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>總金額：</span><span style='color: #dc3545; font-weight: bold;'>NT$ {$totalAmount}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>付款狀態：</span><span style='color: #28a745; font-weight: bold;'>✓ 已付款</span>
                        </div>
                    </div>
                    
                    <div style='margin-top: 30px; padding: 15px; background-color: #fff3cd; border-radius: 8px;'>
                        <strong>⚠️ 注意事項：</strong>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>請提前 15 分鐘到達影廳取票入場</li>
                            <li>請妥善保管您的取票代碼</li>
                            <li>如有任何問題，請聯繫客服或至櫃檯諮詢</li>
                        </ul>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email 發送失敗: {$mail->ErrorInfo}");
        return false;
    }
}
```

#### 步驟 3: 取得 Gmail 應用程式密碼

1. 登入 Google 帳號
2. 前往 https://myaccount.google.com/security
3. 啟用「兩步驟驗證」
4. 搜尋「應用程式密碼」
5. 選擇「郵件」和裝置類型
6. 複製產生的 16 位密碼

#### 步驟 4: 在 confirm_order.php 中啟用

在 `confirm_order.php` 的交易提交後加入:

```php
// 引入 Email 發送函數
require_once 'send_ticket_email.php';

// 發送 Email
try {
    sendTicketEmail(
        $customerEmail, 
        $customerName, 
        $ticketCode, 
        $orderID, 
        $movieInfo, 
        $seatList, 
        $totalAmount
    );
} catch (Exception $e) {
    // Email 發送失敗不影響訂單
    error_log("Email 通知失敗: " . $e->getMessage());
}
```

---

### 方案 2: 配置 Windows 本地 SMTP (測試用)

僅適用於測試環境,不建議用於生產。

#### 步驟 1: 安裝 sendmail (XAMPP)

如果使用 XAMPP,已內建 sendmail。

#### 步驟 2: 修改 php.ini

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

#### 步驟 3: 修改 sendmail.ini

```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-app-password
force_sender=your-email@gmail.com
```

---

### 方案 3: 使用第三方郵件服務 (推薦生產環境)

- **SendGrid**: https://sendgrid.com/ (免費每日 100 封)
- **Mailgun**: https://www.mailgun.com/ (免費每月 1000 封)
- **Amazon SES**: https://aws.amazon.com/ses/ (便宜大量發送)

---

## 目前狀態

✅ 訂票功能正常運作
✅ 訂票完成頁面顯示完整資訊(取票代碼、QR Code、電影資訊、座位、金額)
❌ Email 通知功能已停用(避免錯誤)

## 建議

對於正式上線環境,建議使用 **PHPMailer + Gmail SMTP** 或 **SendGrid**,提供穩定可靠的郵件發送服務。
