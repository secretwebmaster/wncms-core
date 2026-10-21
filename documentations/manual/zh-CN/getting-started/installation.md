# 安装指南

PHP 8.4 / Laravel 13. New installations only. Use an empty project directory and a dedicated empty database.

```bash
composer create-project laravel/laravel my-project "^13.0" --no-install --no-scripts --no-plugins
cd my-project
composer config allow-plugins false
composer require secretwebmaster/wncms-core:^6.7.0 --no-scripts --no-plugins
php artisan package:discover
php artisan vendor:publish --tag=wncms-core-assets
```

## 运行安装程序

WNCMS 提供了完整的命令行安装程序:

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

`--app_locale` 用于在安装时设置默认语言。
- 如果未提供，安装器会使用 `config('app.locale')`。
- 如果提供了不受支持的语言代码，安装器会回退到配置中的默认语言。
- 在 Laravel 13 全新项目中若尚未存在 `lang/`，安装器会自动创建 `lang/`，并为默认语系与最终解析语系自动生成 `custom.php`。

> [!NOTE]
> 全新 Laravel 项目可以保留默认迁移（`users`、`jobs` 等）。
> `wncms:install` 与这些基础表兼容，并会自动补齐 WNCMS 所需的缺失字段。

在运行安装程序之前,创建一个空的 MySQL 数据库并准备好您的连接详细信息。

**MySQL 数据库信息示例**

| 字段      | 示例值         | 描述             |
| --------- | -------------- | ---------------- |
| db_driver | mysql          | 数据库驱动       |
| db_host   | 127.0.0.1      | 数据库伺服器主机 |
| db_port   | 3306           | 默认 MySQL 端口  |
| db_name   | example_com    | 您的数据库名称   |
| db_user   | example_com    | 数据库用户名     |
| db_pass   | ABCDCF12345678 | 数据库密码       |

**然后运行:**

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
    --app_name="example" \
    --app_url="https://example.com" \
    --app_locale="en" \
    --force_https \
    --domain="example.com" \
    --site_name="我的网站"
```

安装程序将自动:

- 配置 `.env`
- 生成密钥
- 运行迁移
- 检测到 Laravel 默认 `routes/web.php` 欢迎首页路由时自动移除
- 创建管理员用户
- 设置网站设置
- 如果指定则启用 HTTPS

## 配置网站根目录

将您的 Web 根目录设置为:

```
/my-project/public
```

> [!TIP]
> 对于 aaPanel / BT 用户:
> 打开 **网站 → 网站配置 → 域名**,然后将 **文档根目录** 设置为:
> `/www/wwwroot/my-project/public`

## 权限

确保这些目录具有正确的权限。以 `www` 用户为例。如果您通过 `root` 用户安装但在 `www` 或 `www-data` 用户上运行网站,它将抛出错误。

```bash
chmod -R 775 storage bootstrap/cache public/media
chown -R www:www .
```

## 完成

您现在拥有一个完全可用的 **WNCMS** 网站,包括后台、主题、设置、API 和套件支持。

**前台:**

```
https://your-domain.com
```

**后台:**

```
https://your-domain.com/panel/login
```

> [!WARNING] 提醒
> 此全新安装 v6.7.0 需要 PHP 8.4、Laravel 13，以及专用的空白数据库；安装器会重建数据表。CLI 可用 `--admin_email=you@example.com` 指定管理员，密码以隐藏提示输入两次，至少 12 字符、最多 72 个 UTF-8 字节。非交互部署请通过临时且受保护的进程环境提供 `WNCMS_INSTALL_ADMIN_PASSWORD`，完成后移除；不要放在命令参数或版本库。网页向导也要求管理员账号、密码与确认。
> 登录 URL: `https://your-domain.com/panel/login`
> 安装后使用您指定的账号和密码登录，不会创建共享默认管理员或示例 manager。若以官方 Laravel 新项目加入本包后使用网页向导，先执行 `php artisan vendor:publish --tag=wncms-core-assets`。

## 下一步

- **用户指南:** `/user/overview`
- **开发者指南:** `/developer/overview`
- **套件开发:** `/package/overview`
- **API 文档:** `/api/overview`

## 平台特定指南

- **aaPanel/BT 面板:** 请参阅 [aaPanel 安装指南](./aapanel.md)
- **Docker:** 请参阅 [Docker 安装指南](./docker.md)
