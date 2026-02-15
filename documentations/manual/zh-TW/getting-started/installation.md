# 安裝指南

本指南說明如何在任何伺服器上安裝 **WNCMS**,包括 aaPanel、CyberPanel、VPS 或本地開發環境。

## 創建新文件夾

您可以將 WNCMS 安裝到一個空文件夾中:

```bash
mkdir my-project
cd my-project
```

## 下載 WNCMS

```bash
composer create-project secretwebmaster/wncms .temp --no-interaction
```

這會將完整的 Laravel + WNCMS 結構放置在 `.temp/` 中。

## 複製文件

某些面板(aaPanel、共享主機)已經創建了默認的 `public/` 文件夾。
我們必須**安全地合併** WNCMS,而不刪除上傳或伺服器文件。

```bash
# 複製所有根文件
cp -r .temp/* .
cp -r .temp/.* . 2>/dev/null

# 安全地合併公共資源
mkdir -p public
cp -rf .temp/public/* public/ 2>/dev/null

# 刪除臨時文件夾
rm -rf .temp
```

此方法不需要 rsync 並適用於所有系統。如果您的伺服器已經有 rsync,您可以使用 rsync。原理是將所有文件移動到上一級。

## 安裝依賴項

```bash
composer install --no-interaction --prefer-dist
composer update secretwebmaster/wncms-core -W

# 或者
# 如果您以 root 用戶身份運行並希望在沒有交互的情況下以編程方式安裝。您可以運行此命令

# COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
# COMPOSER_ALLOW_SUPERUSER=1 composer update secretwebmaster/wncms-core -W
```

## 運行安裝程序

WNCMS 提供了完整的命令行安裝程序:

```
php artisan wncms:install \
    {db_driver} {db_host} {db_port} {db_name} {db_user} {db_pass} \
    --app_name="YourApp" \
    --app_url="https://your-domain.com" \
    --app_locale="en" \
    --force_https \
    --domain="your-domain.com" \
    --site_name="Your Site Name"
```

`--app_locale` 用於在安裝時設定預設語言。
- 如果未提供，安裝器會使用 `config('app.locale')`。
- 如果提供了不支援的語言代碼，安裝器會回退到設定中的預設語言。

在運行安裝程序之前,創建一個空的 MySQL 數據庫並準備好您的連接詳細信息。

**MySQL 數據庫信息示例**

| 字段      | 示例值         | 描述             |
| --------- | -------------- | ---------------- |
| db_driver | mysql          | 數據庫驅動       |
| db_host   | 127.0.0.1      | 數據庫伺服器主機 |
| db_port   | 3306           | 默認 MySQL 端口  |
| db_name   | example_com    | 您的數據庫名稱   |
| db_user   | example_com    | 數據庫用戶名     |
| db_pass   | ABCDCF12345678 | 數據庫密碼       |

**然後運行:**

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
    --app_name="example" \
    --app_url="https://example.com" \
    --app_locale="en" \
    --force_https \
    --domain="example.com" \
    --site_name="我的網站"
```

安裝程序將自動:

- 配置 `.env`
- 生成密鑰
- 運行遷移
- 創建管理員用戶
- 設置網站設置
- 如果指定則啟用 HTTPS

## 配置網站根目錄

將您的 Web 根目錄設置為:

```
/my-project/public
```

> [!TIP]
> 對於 aaPanel / BT 用戶:
> 打開 **網站 → 網站配置 → 域名**,然後將 **文檔根目錄** 設置為:
> `/www/wwwroot/my-project/public`

## 權限

確保這些目錄具有正確的權限。以 `www` 用戶為例。如果您通過 `root` 用戶安裝但在 `www` 或 `www-data` 用戶上運行網站,它將拋出錯誤。

```bash
chmod -R 775 storage bootstrap/cache public/media
chown -R www:www .
```

## 完成

您現在擁有一個完全可用的 **WNCMS** 網站,包括後台、主題、設置、API 和套件支持。

**前台:**

```
https://your-domain.com
```

**後台:**

```
https://your-domain.com/panel/login
```

> [!WARNING] 提醒
> 您必須在安裝後更改密碼。
> 默認用戶: `admin@demo.com`
> 默認密碼: `wncms.cc`

## 下一步

- **用戶指南:** `/user/overview`
- **開發者指南:** `/developer/overview`
- **套件開發:** `/package/overview`
- **API 文檔:** `/api/overview`

## 平台特定指南

- **aaPanel/BT 面板:** 請參閱 [aaPanel 安裝指南](./aapanel.md)
- **Docker:** 請參閱 [Docker 安裝指南](./docker.md)
