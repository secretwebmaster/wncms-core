# 安裝指南

PHP 8.4 / Laravel 13. New installations only. Use an empty project directory and a dedicated empty database.

```bash
composer create-project laravel/laravel my-project "^13.0" --no-install --no-scripts --no-plugins
cd my-project
composer config allow-plugins false
composer require secretwebmaster/wncms-core:^6.7.0 --no-scripts --no-plugins
php artisan package:discover
php artisan vendor:publish --tag=wncms-core-assets
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
- 在 Laravel 13 全新專案中若尚未存在 `lang/`，安裝器會自動建立 `lang/`，並為預設語系與最終解析語系自動產生 `custom.php`。

> [!NOTE]
> 全新 Laravel 專案可以保留預設遷移（`users`、`jobs` 等）。
> `wncms:install` 與這些基礎資料表相容，並會自動補齊 WNCMS 所需的缺少欄位。

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
- 偵測到 Laravel 預設 `routes/web.php` 歡迎首頁路由時自動移除
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
> 此全新安裝 v6.7.0 需要 PHP 8.4、Laravel 13，以及專用的空白資料庫；安裝器會重建資料表。CLI 可用 `--admin_email=you@example.com` 指定管理員，密碼以隱藏提示輸入兩次，至少 12 字元、最多 72 個 UTF-8 位元組。非互動部署請透過暫時且受保護的程序環境提供 `WNCMS_INSTALL_ADMIN_PASSWORD`，完成後移除；不要放在命令參數或版本庫。網頁精靈也要求管理員帳密與確認。
> 登入 URL: `https://your-domain.com/panel/login`
> 安裝後使用您指定的帳密登入，不會建立共用預設管理員或示範 manager。若以官方 Laravel 新專案加入本套件後使用網頁精靈，先執行 `php artisan vendor:publish --tag=wncms-core-assets`。

## 下一步

- **用戶指南:** `/user/overview`
- **開發者指南:** `/developer/overview`
- **套件開發:** `/package/overview`
- **API 文檔:** `/api/overview`

## 平台特定指南

- **aaPanel/BT 面板:** 請參閱 [aaPanel 安裝指南](./aapanel.md)
- **Docker:** 請參閱 [Docker 安裝指南](./docker.md)
