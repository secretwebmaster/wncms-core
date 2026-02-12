# 系统需求

在安装 **WNCMS** 之前，请确保您的伺服器或本地环境满足以下列出的最低技术要求。

## 伺服器环境

| 需求           | 推荐版本                   | 最低版本                   |
| -------------- | -------------------------- | -------------------------- |
| **PHP**        | 8.2 或更高版本             | 8.2                        |
| **Web 伺服器** | Nginx / Apache 2.4+        | 任何支持 PHP 的 Web 伺服器 |
| **数据库**     | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.0                  |
| **Composer**   | 2.6 或更高版本             | 2.3                        |

> WNCMS 基于 **Laravel 12** 构建，因此任何与 Laravel 12 兼容的环境都应该可以工作。

## Nginx 重写规则

```
rewrite ^/.*\.blade\.php$ /__block_blade_403 last;

location = /__block_blade_403 {
    return 403;
}

location / {
    try_files $uri $uri/ /index.php$is_args$query_string;
}

```

## PHP 扩展

确保在您的 `php.ini` 中启用以下 PHP 扩展:

- `fileinfo`
- `exif`
- `mbstring`
- `opcache` (可选但推荐)
- `redis` (可选但推荐)

## PHP 允许的函数

如果在禁用函数列表中，请移除

- putenv
- proc_open

## 可选但推荐

| 组件           | 用途           |
| -------------- | -------------- |
| **Redis**      | 缓存和队列优化 |
| **Supervisor** | 队列工作器管理 |

## 文件权限

以下目录必须对 Web 伺服器用户**可写**:

```
storage/
bootstrap/cache/
public/media/

```

一旦您的环境满足这些要求，您就可以[安装 WNCMS](./installation)了。
