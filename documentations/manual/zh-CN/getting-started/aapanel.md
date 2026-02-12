# 宝塔/aaPanel 面板安装

本指南为 **宝塔面板** 和 **aaPanel** 用户提供快速安装方法。

## 前置要求

- 已安装宝塔面板或 aaPanel
- PHP 8.2 或更高版本
- MySQL 5.7+ 或 MariaDB 10.3+
- 已安装 Composer
- 域名指向您的伺服器

## 快速安装

如果您熟悉安装流程,可以使用这个一鍵安裝命令。替换网站信息,然后复制并贴上到您的终端。

### 一鍵安裝命令

```bash
# 导航到您的网站目录
cd /www/wwwroot/example.com

# 下载并设置 WNCMS
rm -rf .temp
COMPOSER_ALLOW_SUPERUSER=1 composer create-project secretwebmaster/wncms .temp --no-interaction --prefer-dist

# 复制到当前目录（避免 cp -i 覆盖提示，并避免复制 . / ..）
\cp -rf .temp/. .
\cp -rf .temp/public/. public/

rm -rf .temp
rm -f storage/installed

# 安装依赖项(对 root 使用 COMPOSER_ALLOW_SUPERUSER)
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
COMPOSER_ALLOW_SUPERUSER=1 composer update -W --no-interaction --prefer-dist

# 运行安装程序(替换为您的数据库凭据)
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
  --app_name="example_com" \
  --app_url="https://example.com" \
  --force_https \
  --domain="example.com" \
  --app_locale="zh_CN" \
  --site_name="我的网站"

# 设置权限
chown -R www:www .
chmod -R 775 storage bootstrap/cache public/media

```

## 配置步骤

### 1. 在宝塔面板中创建网站

1. 登录到您的宝塔面板
2. 前往 **网站 → 添加站点**
3. 输入您的域名
4. 选择 **PHP 8.2** 或更高版本
5. 创建或选择数据库
6. 点击 **提交**

### 2. 配置文档根目录

1. 在列表中点击您的网站
2. 前往 **网站目录**
3. 将 **运行目录** 设置为:
   ```
   /public
   ```
4. 点击 **保存**

### 3. 安装 SSL 证书(推荐)

1. 前往 **SSL** 标签
2. 选择 **Let's Encrypt** 免费 SSL
3. 输入您的电子邮件
4. 点击 **申请**

### 4. 配置 PHP 设置

1. 前往 **软件商店 → PHP 8.2 → 设置**
2. 确保启用这些扩展:
   - `fileinfo`
   - `gd`
   - `mbstring`
   - `openssl`
   - `pdo_mysql`
   - `zip`
   - `curl`
   - `xml`

### 5. 设置 PHP 限制

在 PHP 配置文件(`php.ini`)中:

```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

## 数据库设置

### 通过宝塔面板创建数据库

1. 前往 **数据库 → 添加数据库**
2. 输入数据库名称(例如 `example_com`)
3. 输入用户名(例如 `example_com`)
4. 生成或输入强密码
5. 将 **访问权限** 设置为 `本地伺服器` 或 `127.0.0.1`
6. 点击 **提交**

### 记下您的数据库凭据

| 字段      | 示例值             | 描述             |
| --------- | ------------------ | ---------------- |
| db_driver | mysql              | 数据库驱动       |
| db_host   | 127.0.0.1          | 数据库伺服器主机 |
| db_port   | 3306               | 默认 MySQL 端口  |
| db_name   | example_com        | 您的数据库名称   |
| db_user   | example_com        | 数据库用户名     |
| db_pass   | YourStrongPassword | 数据库密码       |

## 安装命令示例

将这些值替换为您的实际凭据:

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "YourStrongPassword" \
    --app_name="example" \
    --app_url="https://example.com" \
    --force_https \
    --domain="example.com" \
    --app_locale="zh_TW" \
    --site_name="我的网站"
```

## 文件权限

确保 Web 伺服器用户(`www`)的正确权限:

```bash
# 设置所有权
chown -R www:www /www/wwwroot/your-domain.com

# 设置目录权限
find /www/wwwroot/your-domain.com -type d -exec chmod 755 {} \;

# 设置文件权限
find /www/wwwroot/your-domain.com -type f -exec chmod 644 {} \;

# 设置可写目录
chmod -R 775 /www/wwwroot/your-domain.com/storage
chmod -R 775 /www/wwwroot/your-domain.com/bootstrap/cache
chmod -R 775 /www/wwwroot/your-domain.com/public/media
```

## 常见问题

### 权限被拒绝错误

如果遇到权限错误:

```bash
# 检查当前用户
whoami

# 如果以 root 身份运行,确保文件由 www 拥有
chown -R www:www .
```

### Composer 内存限制

如果 composer 内存不足:

```bash
# 临时增加 PHP 内存限制
php -d memory_limit=512M /usr/bin/composer install
```

### 数据库连接失败

1. 在宝塔面板中验证数据库凭据
2. 检查 MySQL 是否运行: **软件商店 → 已安装 → MySQL → 启动**
3. 确保数据库用户具有适当的权限

### 500 内部伺服器错误

1. 检查 Laravel 日志: `storage/logs/laravel.log`
2. 确保 storage 和 cache 目录可写
3. 清除配置缓存:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## URL 重写

宝塔面板通常会自动为 Laravel 配置 URL 重写。如果 URL 不起作用:

1. 前往 **网站设置 → 伪静态**
2. 从模板下拉菜单中选择 **laravel5**
3. 点击 **保存**

重写规则应如下所示:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 计划任务(Cron)

设置 Laravel 的调度器:

1. 前往 **计划任务**
2. 点击 **添加计划任务**
3. 类型: **Shell 脚本**
4. 名称: `Laravel 调度器 - your-domain.com`
5. 执行周期: **N 分钟** (分钟数: 1)
6. 脚本内容:
   ```bash
   cd /www/wwwroot/your-domain.com && php artisan schedule:run >> /dev/null 2>&1
   ```

## 队列工作器(可选)

用于后台作业:

1. 前往 **软件商店** 安装 **Supervisor**
2. 添加新程序
3. 名称: `wncms-queue-worker`
4. 运行目录: `/www/wwwroot/your-domain.com`
5. 启动命令:
   ```bash
   php artisan queue:work --sleep=3 --tries=3
   ```
6. 运行用户: `www`
7. 自动启动: **是**

## 备份设置

1. 前往 **计划任务 → 添加计划任务**
2. 选择 **备份网站**
3. 选择您的网站
4. 设置备份频率(例如每天凌晨 2 点)
5. 选择保留期限

## 访问您的网站

**前台:**

```
https://your-domain.com
```

**后台:**

```
https://your-domain.com/panel/login
```

**默认凭据:**

- 电子邮件: `admin@demo.com`
- 密码: `wncms.cc`

> [!WARNING]
> 首次登录后立即更改默认密码!

## 下一步

- 在 `/panel/settings` 中配置您的网站设置
- 上传您的主题
- 创建内容
- 设置电子邮件配置
- 配置缓存设置

## 另请参阅

- [安装指南](./installation.md) - 通用安装指南
- [系统要求](./requirements.md) - 系统要求
- [Docker 安装](./docker.md) - Docker 设置指南
