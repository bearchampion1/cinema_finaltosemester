<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 手動載入 PHPMailer 類別
require 'PHPMailer 7.0.1 source code/PHPMailer-PHPMailer-03badf8/src/Exception.php';
require 'PHPMailer 7.0.1 source code/PHPMailer-PHPMailer-03badf8/src/PHPMailer.php';
require 'PHPMailer 7.0.1 source code/PHPMailer-PHPMailer-03badf8/src/SMTP.php';

/**
 * 發送訂票通知 Email
 * 
 * @param string $customerEmail 顧客 Email
 * @param string $customerName 顧客姓名
 * @param string $ticketCode 取票代碼
 * @param string $orderID 訂單編號
 * @param array $movieInfo 電影資訊 (片名, 類型, 廳名, 播放日期, 開始時間)
 * @param array $seatList 座位清單
 * @param float $totalAmount 總金額
 * @return bool 是否發送成功
 */
function sendTicketEmail($customerEmail, $customerName, $ticketCode, $orderID, $movieInfo, $seatList, $totalAmount) {
    $mail = new PHPMailer(true);
    
    try {
        // 載入 Email 設定
        $config = require 'email_config.php';
        
        // ========== SMTP 設定 ==========
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port       = $config['smtp_port'];
        $mail->CharSet    = $config['charset'];
        
        // 除錯模式（可選）
        if ($config['debug']) {
            $mail->SMTPDebug = 2;
        }
        
        // ========== 寄件人與收件人 ==========
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($customerEmail, $customerName);
        
        // ========== Email 主旨 ==========
        $mail->Subject = "🎬 訂票成功通知 - 取票代碼：{$ticketCode}";
        
        // ========== 組合座位清單 ==========
        $seatNames = array_map(function($s) { return $s['name']; }, $seatList);
        $seatListText = implode(', ', $seatNames);
        $seatBadges = '';
        foreach ($seatList as $s) {
            $seatBadges .= "<span style='display: inline-block; background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; margin: 2px; font-size: 14px;'>" . htmlspecialchars($s['name']) . "</span> ";
        }
        
        // ========== Email HTML 內容 ==========
        $mail->isHTML(true);
        
        
        // 純文字版取票代碼（已移除 QR Code 功能）
        $ticketCodeDisplay = "
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 15px rgba(102,126,234,0.3);'>
                <div style='font-size: 14px; color: rgba(255,255,255,0.9); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;'>取票代碼</div>
                <div style='font-family: \"Courier New\", monospace; font-size: 36px; font-weight: bold; letter-spacing: 6px; color: #fff; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);'>" . htmlspecialchars($ticketCode) . "</div>
                <div style='font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 12px;'>請憑此代碼至櫃檯或自助機取票</div>
            </div>
        ";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='zh-Hant'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Microsoft JhengHei', '微軟正黑體', Arial, sans-serif; 
                    background-color: #f8f9fa; 
                    margin: 0; 
                    padding: 20px; 
                    line-height: 1.6;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #ffffff; 
                    border-radius: 10px; 
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
                    overflow: hidden;
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0 0 10px 0; 
                    font-size: 32px; 
                    font-weight: bold;
                }
                .header p { 
                    margin: 0; 
                    font-size: 16px; 
                    opacity: 0.95;
                }
                .content { 
                    padding: 40px 30px; 
                }
                .greeting { 
                    font-size: 16px; 
                    color: #495057; 
                    margin-bottom: 10px;
                }
                .ticket-code { 
                    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
                    border: 3px dashed #ffc107; 
                    border-radius: 12px; 
                    padding: 30px; 
                    text-align: center; 
                    margin: 30px 0;
                    box-shadow: 0 2px 8px rgba(255,193,7,0.2);
                }
                .ticket-code h2 { 
                    color: #856404; 
                    margin: 0 0 15px 0; 
                    font-size: 24px; 
                }
                .ticket-code .code { 
                    font-size: 40px; 
                    font-weight: bold; 
                    color: #d39e00; 
                    letter-spacing: 5px; 
                    font-family: 'Courier New', monospace;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
                }
                .ticket-code .hint { 
                    margin: 15px 0 0 0; 
                    color: #856404; 
                    font-size: 14px;
                }
                .info-section { 
                    margin: 25px 0; 
                    padding: 20px; 
                    background-color: #f8f9fa; 
                    border-radius: 8px; 
                    border-left: 4px solid #667eea;
                }
                .info-section h3 { 
                    margin-top: 0; 
                    margin-bottom: 15px;
                    color: #495057; 
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                }
                .info-row { 
                    padding: 10px 0; 
                    border-bottom: 1px solid #dee2e6; 
                    display: flex;
                    align-items: flex-start;
                }
                .info-row:last-child { 
                    border-bottom: none; 
                }
                .info-label { 
                    font-weight: bold; 
                    color: #495057; 
                    min-width: 110px;
                    flex-shrink: 0;
                }
                .info-value { 
                    color: #212529; 
                    flex: 1;
                }
                .qr-section { 
                    text-align: center; 
                    margin: 30px 0; 
                    padding: 25px; 
                    background: linear-gradient(135deg, #e7f3ff 0%, #cfe2ff 100%); 
                    border-radius: 10px;
                }
                .qr-section h3 { 
                    margin-top: 0; 
                    color: #0056b3; 
                    font-size: 20px;
                }
                .qr-section img { 
                    max-width: 200px; 
                    border: 4px solid white; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .button { 
                    display: inline-block; 
                    padding: 14px 35px; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 25px; 
                    margin: 10px 5px; 
                    font-weight: bold;
                    box-shadow: 0 4px 10px rgba(102,126,234,0.3);
                    transition: all 0.3s;
                }
                .alert-box { 
                    margin-top: 30px; 
                    padding: 20px; 
                    background-color: #fff3cd; 
                    border-radius: 8px; 
                    border-left: 4px solid #ffc107;
                }
                .alert-box strong { 
                    color: #856404; 
                    font-size: 16px;
                }
                .alert-box ul { 
                    margin: 10px 0 0 0; 
                    padding-left: 25px; 
                    color: #856404;
                }
                .alert-box li { 
                    margin: 8px 0;
                }
                .footer { 
                    background-color: #f8f9fa; 
                    padding: 25px; 
                    text-align: center; 
                    color: #6c757d; 
                    font-size: 14px; 
                    border-top: 1px solid #dee2e6;
                }
                .footer p { 
                    margin: 5px 0;
                }
                .highlight { 
                    color: #dc3545; 
                    font-weight: bold; 
                    font-size: 18px;
                }
                .success-badge { 
                    background-color: #28a745; 
                    color: white; 
                    padding: 6px 12px; 
                    border-radius: 4px; 
                    font-size: 14px; 
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 訂票成功！</h1>
                    <p>感謝您選擇我們的電影院</p>
                </div>
                
                <div class='content'>
                    <p class='greeting'>親愛的 <strong>" . htmlspecialchars($customerName) . "</strong>，您好：</p>
                    <p>您的電影票已成功訂購！以下是您的訂票詳細資訊：</p>
                    
                    <div class='ticket-code'>
                        <h2>📱 取票代碼</h2>
                        <div class='code'>" . htmlspecialchars($ticketCode) . "</div>
                        <p class='hint'>請憑此代碼至櫃檯或自助取票機取票</p>
                    </div>
                    
                    <div class='info-section'>
                        <h3>🎬 電影資訊</h3>
                        <div class='info-row'>
                            <span class='info-label'>電影名稱：</span>
                            <span class='info-value'>" . htmlspecialchars($movieInfo['片名']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>類型：</span>
                            <span class='info-value'>" . htmlspecialchars($movieInfo['類型']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>影廳：</span>
                            <span class='info-value'>" . htmlspecialchars($movieInfo['廳名']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>播放日期：</span>
                            <span class='info-value'>" . htmlspecialchars($movieInfo['播放日期']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>開始時間：</span>
                            <span class='info-value'>" . htmlspecialchars($movieInfo['開始時間']) . "</span>
                        </div>
                    </div>
                    
                    <div class='info-section'>
                        <h3>💺 訂購座位</h3>
                        <div style='padding: 10px 0;'>" . $seatBadges . "</div>
                    </div>
                    
                    <div class='info-section'>
                        <h3>💰 訂單資訊</h3>
                        <div class='info-row'>
                            <span class='info-label'>訂單編號：</span>
                            <span class='info-value'>" . htmlspecialchars($orderID) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>總金額：</span>
                            <span class='info-value highlight'>NT$ " . number_format($totalAmount, 0) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>付款狀態：</span>
                            <span class='info-value'><span class='success-badge'>✓ 已付款</span></span>
                        </div>
                    </div>
                    
                    <div class='qr-section'>
                        <h3>📲 取票資訊</h3>
                        <p style='color: #495057; margin-bottom: 15px;'>請憑此代碼至櫃檯或自助機取票</p>
                        " . $ticketCodeDisplay . "
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/ticket.php?code=" . urlencode($ticketCode) . "' class='button'>查看電子票券</a>
                    </div>
                    
                    <div class='alert-box'>
                        <strong>⚠️ 重要提醒</strong>
                        <ul>
                            <li>請提前 <strong>15 分鐘</strong>到達影廳取票入場</li>
                            <li>請妥善保管您的取票代碼，遺失恕不補發</li>
                            <li>入場時請出示電子票券或取票代碼</li>
                            <li>如有任何問題，請聯繫客服或至櫃檯諮詢</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>電影院線上訂票系統</strong></p>
                    <p style='margin-top: 10px;'>此為系統自動發送的郵件，請勿直接回覆</p>
                    <p>© 2025 All Rights Reserved</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // 純文字版本 (備用)
        $mail->AltBody = "
訂票成功通知

親愛的 {$customerName}，您好：

您的電影票已成功訂購！

取票代碼：{$ticketCode}

電影資訊：
- 片名：{$movieInfo['片名']}
- 影廳：{$movieInfo['廳名']}
- 日期：{$movieInfo['播放日期']}
- 時間：{$movieInfo['開始時間']}

座位：{$seatListText}

訂單編號：{$orderID}
總金額：NT$ {$totalAmount}
付款狀態：已付款

請提前 15 分鐘到達影廳取票入場。

電影院線上訂票系統
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer 錯誤: {$mail->ErrorInfo}");
        return false;
    }
}
