# Git 上傳注意事項

## ⚠️ 重要：上傳前必做事項

### 1. **敏感資訊保護**

#### 📧 Email 設定檔
**檔案：`email_config.php`**

在上傳 Git 之前，**務必移除或遮蔽**以下敏感資訊：

```php
// ❌ 不要上傳真實的 SMTP 密碼
'smtp_password' => 'your-app-password-here',  // ✓ 使用預設值

// ❌ 不要上傳真實的 Email
'smtp_username' => 'your-email@gmail.com',     // ✓ 使用範例
'from_email' => 'your-email@gmail.com',        // ✓ 使用範例
```

**建議做法：**
```bash
# 方法 1：修改為預設值後再上傳
# 將真實資訊改為範例值

# 方法 2：加入 .gitignore（推薦）
echo "email_config.php" >> .gitignore
```

---

#### 🗄️ 資料庫連線設定
**檔案：`config.php`**

檢查是否包含敏感的資料庫資訊：

```php
// 確保使用通用的範例值
$host = 'localhost';
$dbname = 'movie_booking';  // ✓ 通用名稱
$username = 'root';          // ✓ 預設值
$password = '';              // ✓ 空密碼或範例
```

**如果使用雲端資料庫：**
```bash
# 務必加入 .gitignore
echo "config.php" >> .gitignore
```

---

### 2. **建立 .gitignore 檔案**

在專案根目錄建立 `.gitignore`，排除不需要上傳的檔案：

```gitignore
# 敏感設定檔
email_config.php
config.php

# 快取與臨時檔案
phpqrcode/cache/
*.cache
*.tmp

# 系統檔案
.DS_Store
Thumbs.db
desktop.ini

# IDE 設定
.vscode/
.idea/
*.swp
*.swo

# 日誌檔案
*.log
error_log

# 上傳的檔案（如果有）
uploads/
temp/

# Composer 依賴（如果使用）
vendor/
composer.lock

# 環境變數檔
.env
.env.local
```

---

### 3. **建立設定檔範本**

為敏感檔案建立範本，方便其他開發者設定：

#### `email_config.example.php`
```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'your-email@gmail.com',  // 請填入您的 Gmail
    'smtp_password' => 'your-16-digit-app-password',  // 請填入應用程式密碼
    'from_email' => 'your-email@gmail.com',
    'from_name' => '🎬 電影院訂票系統',
    'charset' => 'UTF-8',
    'debug' => false
];
```

#### `config.example.php`
```php
<?php
$host = 'localhost';
$dbname = 'movie_booking';
$username = 'root';
$password = '';  // 請填入您的資料庫密碼

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}
```

---

### 4. **Git 指令流程**

#### 首次上傳
```bash
# 1. 初始化 Git（如果還沒有）
git init

# 2. 建立 .gitignore
# （使用上面的內容）

# 3. 檢查要上傳的檔案
git status

# 4. 確認沒有敏感資訊後，加入所有檔案
git add .

# 5. 提交
git commit -m "Initial commit: 電影訂票系統"

# 6. 連結遠端倉庫
git remote add origin https://github.com/你的帳號/你的倉庫.git

# 7. 推送
git push -u origin main
```

#### 日常更新
```bash
# 1. 檢查修改
git status

# 2. 查看差異
git diff

# 3. 加入變更
git add .

# 4. 提交（使用有意義的訊息）
git commit -m "描述你的修改"

# 5. 推送
git push
```

---

### 5. **已經上傳敏感資訊？緊急處理**

如果不小心已經上傳了敏感資訊：

#### ⚠️ 立即更換密碼
```bash
# 1. 立即更換 Gmail 應用程式密碼
# 前往：https://myaccount.google.com/apppasswords
# 刪除舊密碼，產生新密碼

# 2. 更換資料庫密碼（如適用）
```

#### 🗑️ 從 Git 歷史中移除
```bash
# 使用 git filter-branch（進階）
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch email_config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 強制推送（會改寫歷史）
git push origin --force --all
```

⚠️ **注意**：改寫歷史會影響所有協作者，請謹慎使用。

---

### 6. **建議的 README.md 設定說明**

在 `README.md` 中加入設定步驟：

```markdown
## 🚀 安裝與設定

### 1. 複製設定檔
```bash
cp email_config.example.php email_config.php
cp config.example.php config.php
```

### 2. 設定資料庫
編輯 `config.php`，填入您的資料庫資訊。

### 3. 設定 Email
編輯 `email_config.php`，填入您的 Gmail SMTP 資訊。
詳見：`如何取得Gmail應用程式密碼.txt`

### 4. 匯入資料庫
```bash
mysql -u root -p movie_booking < projecttosemesterend.sql
```

### 5. 建立管理員
訪問 `admin/create_admin.php` 建立管理員帳號。
```

---

### 7. **其他注意事項**

#### 📝 Commit 訊息規範
使用清楚的提交訊息：

```bash
# ✓ 好的範例
git commit -m "新增: Email 通知功能"
git commit -m "修正: 座位售完時的顯示問題"
git commit -m "移除: QR Code 功能"
git commit -m "更新: 管理員登入流程"

# ✗ 不好的範例
git commit -m "update"
git commit -m "fix bug"
git commit -m "changes"
```

#### 📦 大檔案處理
```bash
# 如果有大型檔案，考慮使用 Git LFS
git lfs install
git lfs track "*.zip"
git lfs track "*.sql"
```

#### 🌿 分支管理
```bash
# 開發新功能時建立分支
git checkout -b feature/新功能名稱

# 完成後合併
git checkout main
git merge feature/新功能名稱
```

---

## ✅ 上傳前檢查清單

在執行 `git push` 之前，確認：

- [ ] 已建立 `.gitignore`
- [ ] `email_config.php` 中沒有真實的 SMTP 密碼
- [ ] `config.php` 中沒有真實的資料庫密碼
- [ ] 已建立 `.example.php` 範本檔案
- [ ] 執行 `git status` 確認要上傳的檔案
- [ ] 使用有意義的 commit 訊息
- [ ] 已更新 `README.md` 說明設定步驟

---

## 🔗 相關資源

- [Git 官方文件](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [.gitignore 產生器](https://www.toptal.com/developers/gitignore)
- [Git LFS](https://git-lfs.github.com/)

---

## 💡 最佳實踐

1. **小步提交**：每完成一個功能就提交一次
2. **定期推送**：避免本地累積太多修改
3. **寫好文件**：README.md 要清楚說明專案設定
4. **保護隱私**：永遠不要上傳密碼或金鑰
5. **測試後再推**：確保程式碼可以正常運作

---

最後更新：2025年12月3日
