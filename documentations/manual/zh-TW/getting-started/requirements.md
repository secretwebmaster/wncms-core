# 系統需求

在安裝 **WNCMS** 之前，請確保您的伺服器或本地環境滿足以下列出的最低技術要求。

## 伺服器環境

| 需求           | 推薦版本                   | 最低版本                   |
| -------------- | -------------------------- | -------------------------- |
| **PHP**        | 8.2 或更高版本             | 8.2                        |
| **Web 伺服器** | Nginx / Apache 2.4+        | 任何支持 PHP 的 Web 伺服器 |
| **數據庫**     | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.0                  |
| **Composer**   | 2.6 或更高版本             | 2.3                        |

> WNCMS 基於 **Laravel 12** 構建，因此任何與 Laravel 12 兼容的環境都應該可以工作。

## Nginx 重寫規則

```
rewrite ^/.*\.blade\.php$ /__block_blade_403 last;

location = /__block_blade_403 {
    return 403;
}

location / {
    try_files $uri $uri/ /index.php$is_args$query_string;
}

```

## PHP 擴展

確保在您的 `php.ini` 中啟用以下 PHP 擴展:

- `fileinfo`
- `exif`
- `mbstring`
- `opcache` (可選但推薦)
- `redis` (可選但推薦)

## PHP 允許的函數

如果在禁用函數列表中，請移除

- putenv
- proc_open

## 可選但推薦

| 組件           | 用途           |
| -------------- | -------------- |
| **Redis**      | 緩存和隊列優化 |
| **Supervisor** | 隊列工作器管理 |

## 文件權限

以下目錄必須對 Web 伺服器用戶**可寫**:

```
storage/
bootstrap/cache/
public/media/

```

一旦您的環境滿足這些要求，您就可以[安裝 WNCMS](./installation)了。
