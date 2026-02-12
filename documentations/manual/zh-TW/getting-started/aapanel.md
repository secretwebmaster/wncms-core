# 寶塔/aaPanel 面板安裝

本指南為 **寶塔面板** 和 **aaPanel** 用戶提供快速安裝方法。

## 前置要求

- 已安裝寶塔面板或 aaPanel
- PHP 8.2 或更高版本
- MySQL 5.7+ 或 MariaDB 10.3+
- 已安裝 Composer
- 域名指向您的伺服器

## 快速安裝

如果您熟悉安裝流程,可以使用這個一键安装命令。替換網站信息,然後複製並貼上到您的終端。

### 一键安装命令

```bash
# 導航到您的網站目錄
cd /www/wwwroot/example.com

# 下載並設置 WNCMS
rm -rf .temp
COMPOSER_ALLOW_SUPERUSER=1 composer create-project secretwebmaster/wncms .temp --no-interaction --prefer-dist

# 複製到當前目錄（避免 cp -i 覆蓋提示，並避免複製 . / ..）
\cp -rf .temp/. .
\cp -rf .temp/public/. public/

rm -rf .temp
rm -f storage/installed

# 安裝依賴項(對 root 使用 COMPOSER_ALLOW_SUPERUSER)
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
COMPOSER_ALLOW_SUPERUSER=1 composer update -W --no-interaction --prefer-dist

# 運行安裝程序(替換為您的數據庫憑據)
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
  --app_name="example_com" \
  --app_url="https://example.com" \
  --force_https \
  --domain="example.com" \
  --app_locale="zh_CN" \
  --site_name="我的網站"

# 設置權限
chown -R www:www .
chmod -R 775 storage bootstrap/cache public/media

```

## 配置步驟

### 1. 在寶塔面板中創建網站

1. 登錄到您的寶塔面板
2. 前往 **網站 → 添加站點**
3. 輸入您的域名
4. 選擇 **PHP 8.2** 或更高版本
5. 創建或選擇數據庫
6. 點擊 **提交**

### 2. 配置文檔根目錄

1. 在列表中點擊您的網站
2. 前往 **網站目錄**
3. 將 **運行目錄** 設置為:
   ```
   /public
   ```
4. 點擊 **保存**

### 3. 安裝 SSL 證書(推薦)

1. 前往 **SSL** 標籤
2. 選擇 **Let's Encrypt** 免費 SSL
3. 輸入您的電子郵件
4. 點擊 **申請**

### 4. 配置 PHP 設置

1. 前往 **軟件商店 → PHP 8.2 → 設置**
2. 確保啟用這些擴展:
   - `fileinfo`
   - `gd`
   - `mbstring`
   - `openssl`
   - `pdo_mysql`
   - `zip`
   - `curl`
   - `xml`

### 5. 設置 PHP 限制

在 PHP 配置文件(`php.ini`)中:

```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

## 數據庫設置

### 通過寶塔面板創建數據庫

1. 前往 **數據庫 → 添加數據庫**
2. 輸入數據庫名稱(例如 `example_com`)
3. 輸入用戶名(例如 `example_com`)
4. 生成或輸入強密碼
5. 將 **訪問權限** 設置為 `本地伺服器` 或 `127.0.0.1`
6. 點擊 **提交**

### 記下您的數據庫憑據

| 字段      | 示例值             | 描述             |
| --------- | ------------------ | ---------------- |
| db_driver | mysql              | 數據庫驅動       |
| db_host   | 127.0.0.1          | 數據庫伺服器主機 |
| db_port   | 3306               | 默認 MySQL 端口  |
| db_name   | example_com        | 您的數據庫名稱   |
| db_user   | example_com        | 數據庫用戶名     |
| db_pass   | YourStrongPassword | 數據庫密碼       |

## 安裝命令示例

將這些值替換為您的實際憑據:

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "YourStrongPassword" \
    --app_name="example" \
    --app_url="https://example.com" \
    --force_https \
    --domain="example.com" \
    --app_locale="zh_TW" \
    --site_name="我的網站"
```

## 文件權限

確保 Web 伺服器用戶(`www`)的正確權限:

```bash
# 設置所有權
chown -R www:www /www/wwwroot/your-domain.com

# 設置目錄權限
find /www/wwwroot/your-domain.com -type d -exec chmod 755 {} \;

# 設置文件權限
find /www/wwwroot/your-domain.com -type f -exec chmod 644 {} \;

# 設置可寫目錄
chmod -R 775 /www/wwwroot/your-domain.com/storage
chmod -R 775 /www/wwwroot/your-domain.com/bootstrap/cache
chmod -R 775 /www/wwwroot/your-domain.com/public/media
```

## 常見問題

### 權限被拒絕錯誤

如果遇到權限錯誤:

```bash
# 檢查當前用戶
whoami

# 如果以 root 身份運行,確保文件由 www 擁有
chown -R www:www .
```

### Composer 內存限制

如果 composer 內存不足:

```bash
# 臨時增加 PHP 內存限制
php -d memory_limit=512M /usr/bin/composer install
```

### 數據庫連接失敗

1. 在寶塔面板中驗證數據庫憑據
2. 檢查 MySQL 是否運行: **軟件商店 → 已安裝 → MySQL → 啟動**
3. 確保數據庫用戶具有適當的權限

### 500 內部伺服器錯誤

1. 檢查 Laravel 日誌: `storage/logs/laravel.log`
2. 確保 storage 和 cache 目錄可寫
3. 清除配置緩存:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## URL 重寫

寶塔面板通常會自動為 Laravel 配置 URL 重寫。如果 URL 不起作用:

1. 前往 **網站設置 → 偽靜態**
2. 從模板下拉菜單中選擇 **laravel5**
3. 點擊 **保存**

重寫規則應如下所示:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 計劃任務(Cron)

設置 Laravel 的調度器:

1. 前往 **計劃任務**
2. 點擊 **添加計劃任務**
3. 類型: **Shell 腳本**
4. 名稱: `Laravel 調度器 - your-domain.com`
5. 執行週期: **N 分鐘** (分鐘數: 1)
6. 腳本內容:
   ```bash
   cd /www/wwwroot/your-domain.com && php artisan schedule:run >> /dev/null 2>&1
   ```

## 隊列工作器(可選)

用於後台作業:

1. 前往 **軟件商店** 安裝 **Supervisor**
2. 添加新程序
3. 名稱: `wncms-queue-worker`
4. 運行目錄: `/www/wwwroot/your-domain.com`
5. 啟動命令:
   ```bash
   php artisan queue:work --sleep=3 --tries=3
   ```
6. 運行用戶: `www`
7. 自動啟動: **是**

## 備份設置

1. 前往 **計劃任務 → 添加計劃任務**
2. 選擇 **備份網站**
3. 選擇您的網站
4. 設置備份頻率(例如每天凌晨 2 點)
5. 選擇保留期限

## 訪問您的網站

**前台:**

```
https://your-domain.com
```

**後台:**

```
https://your-domain.com/panel/login
```

**默認憑據:**

- 電子郵件: `admin@demo.com`
- 密碼: `wncms.cc`

> [!WARNING]
> 首次登錄後立即更改默認密碼!

## 下一步

- 在 `/panel/settings` 中配置您的網站設置
- 上傳您的主題
- 創建內容
- 設置電子郵件配置
- 配置緩存設置

## 另請參閱

- [安裝指南](./installation.md) - 通用安裝指南
- [系統要求](./requirements.md) - 系統要求
- [Docker 安裝](./docker.md) - Docker 設置指南
