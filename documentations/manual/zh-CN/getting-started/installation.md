# 安装指南

本指南说明如何在任何伺服器上安装 **WNCMS**,包括 aaPanel、CyberPanel、VPS 或本地开发环境。

## 创建新文件夹

您可以将 WNCMS 安装到一个空文件夹中:

```bash
mkdir my-project
cd my-project
```

## 下载 WNCMS

```bash
composer create-project secretwebmaster/wncms .temp --no-interaction
```

这会将完整的 Laravel + WNCMS 结构放置在 `.temp/` 中。

## 复制文件

某些面板(aaPanel、共享主机)已经创建了默认的 `public/` 文件夹。
我们必须**安全地合并** WNCMS,而不删除上传或伺服器文件。

```bash
# 复制所有根文件
cp -r .temp/* .
cp -r .temp/.* . 2>/dev/null

# 安全地合并公共资源
mkdir -p public
cp -rf .temp/public/* public/ 2>/dev/null

# 删除临时文件夹
rm -rf .temp
```

此方法不需要 rsync 并适用于所有系统。如果您的伺服器已经有 rsync,您可以使用 rsync。原理是将所有文件移动到上一级。

## 安装依赖项

```bash
composer install --no-interaction --prefer-dist
composer update secretwebmaster/wncms-core -W

# 或者
# 如果您以 root 用户身份运行并希望在没有交互的情况下以编程方式安装。您可以运行此命令

# COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
# COMPOSER_ALLOW_SUPERUSER=1 composer update secretwebmaster/wncms-core -W
```

> [!NOTE]
> 主题资源不再在每次 Composer update 时自动同步。
> 如果需要手动重新安装核心主题资源，请执行:
>
> ```bash
> php artisan wncms:install-default-theme --force
> ```

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
> 您必须在安装后更改密码。
> 默认用户: `admin@demo.com`
> 默认密码: `wncms.cc`

## 下一步

- **用户指南:** `/user/overview`
- **开发者指南:** `/developer/overview`
- **套件开发:** `/package/overview`
- **API 文档:** `/api/overview`

## 平台特定指南

- **aaPanel/BT 面板:** 请参阅 [aaPanel 安装指南](./aapanel.md)
- **Docker:** 请参阅 [Docker 安装指南](./docker.md)
