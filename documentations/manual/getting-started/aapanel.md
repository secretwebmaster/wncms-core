# aaPanel/BT Panel Installation

This guide provides a quick installation method specifically for **aaPanel** and **BT Panel** users.

## Prerequisites

- aaPanel or BT Panel installed
- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer installed
- A domain pointing to your server

## Quick Installation

If you are familiar with the installation flow, you can use this ready-to-use command. Replace the site information, then copy and paste into your terminal.

### Ready-to-Use Command

```bash
# Navigate to your website directory
cd /www/wwwroot/example.com

# Download and setup WNCMS
rm -rf .temp
COMPOSER_ALLOW_SUPERUSER=1 composer create-project secretwebmaster/wncms .temp --no-interaction --prefer-dist

# Copy project files into current directory (avoid cp -i prompts, and avoid copying . / ..)
\cp -rf .temp/. .
\cp -rf .temp/public/. public/

rm -rf .temp
rm -f storage/installed

# Install dependencies (using COMPOSER_ALLOW_SUPERUSER for root)
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
COMPOSER_ALLOW_SUPERUSER=1 composer update -W --no-interaction --prefer-dist

# Run installer (replace with your database credentials)
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
  --app_name="example_com" \
  --app_url="https://example.com" \
  --force_https \
  --domain="example.com" \
  --app_locale="zh_CN" \
  --site_name="My Website"

# Set permissions
chown -R www:www .
chmod -R 775 storage bootstrap/cache public/media
```

## Configuration Steps

### 1. Create Website in aaPanel

1. Log in to your aaPanel
2. Go to **Website → Add Site**
3. Enter your domain name
4. Select **PHP 8.2** or higher
5. Create or select a database
6. Click **Submit**

### 2. Configure Document Root

1. Click on your website in the list
2. Go to **Site Config → Domain**
3. Set **Document Root** to:
   ```
   /www/wwwroot/your-domain.com/public
   ```
4. Click **Save**

### 3. Install SSL Certificate (Recommended)

1. Go to **SSL** tab
2. Choose **Let's Encrypt** for free SSL
3. Enter your email
4. Click **Apply**

### 4. Configure PHP Settings

1. Go to **PHP → PHP 8.2 → Configuration**
2. Ensure these extensions are enabled:
   - `fileinfo`
   - `gd`
   - `mbstring`
   - `openssl`
   - `pdo_mysql`
   - `zip`
   - `curl`
   - `xml`

### 5. Set PHP Limits

In PHP configuration file (`php.ini`):

```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

## Database Setup

### Create Database via aaPanel

1. Go to **Database → Add Database**
2. Enter database name (e.g., `example_com`)
3. Enter username (e.g., `example_com`)
4. Generate or enter a strong password
5. Set **Access Permissions** to `localhost` or `127.0.0.1`
6. Click **Submit**

### Note Your Database Credentials

| Field     | Example Value      | Description          |
| --------- | ------------------ | -------------------- |
| db_driver | mysql              | Database driver      |
| db_host   | 127.0.0.1          | Database server host |
| db_port   | 3306               | Default MySQL port   |
| db_name   | example_com        | Your database name   |
| db_user   | example_com        | Database username    |
| db_pass   | YourStrongPassword | Database password    |

## Installation Command Example

Replace these values with your actual credentials:

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "YourStrongPassword" \
    --app_name="example" \
    --app_url="https://example.com" \
    --force_https \
    --domain="example.com" \
    --app_locale="zh_CN" \
    --site_name="我的網站"
```

## File Permissions

Ensure correct permissions for web server user (`www`):

```bash
# Set ownership
chown -R www:www /www/wwwroot/your-domain.com

# Set directory permissions
find /www/wwwroot/your-domain.com -type d -exec chmod 755 {} \;

# Set file permissions
find /www/wwwroot/your-domain.com -type f -exec chmod 644 {} \;

# Set writable directories
chmod -R 775 /www/wwwroot/your-domain.com/storage
chmod -R 775 /www/wwwroot/your-domain.com/bootstrap/cache
chmod -R 775 /www/wwwroot/your-domain.com/public/media
```

## Common Issues

### Permission Denied Errors

If you encounter permission errors:

```bash
# Check current user
whoami

# If running as root, make sure files are owned by www
chown -R www:www .
```

### Composer Memory Limit

If composer runs out of memory:

```bash
# Increase PHP memory limit temporarily
php -d memory_limit=512M /usr/bin/composer install
```

### Database Connection Failed

1. Verify database credentials in aaPanel
2. Check if MySQL is running: **App Store → Installed → MySQL → Start**
3. Ensure database user has proper permissions

### 500 Internal Server Error

1. Check Laravel logs: `storage/logs/laravel.log`
2. Ensure storage and cache directories are writable
3. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## URL Rewrite

aaPanel usually configures URL rewrite automatically for Laravel. If URLs don't work:

1. Go to **Site Config → URL Rewrite**
2. Select **Laravel** from the template dropdown
3. Click **Save**

The rewrite rule should look like:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Scheduled Tasks (Cron)

Set up Laravel's scheduler:

1. Go to **Cron**
2. Click **Add Cron**
3. Type: **Shell Script**
4. Name: `Laravel Scheduler - your-domain.com`
5. Period: **Every Minute** (N Minutes: 1)
6. Script:
   ```bash
   cd /www/wwwroot/your-domain.com && php artisan schedule:run >> /dev/null 2>&1
   ```

## Queue Worker (Optional)

For background jobs:

1. Go to **Supervisor** (install if not available)
2. Add new program
3. Name: `wncms-queue-worker`
4. Command:
   ```bash
   php /www/wwwroot/your-domain.com/artisan queue:work --sleep=3 --tries=3
   ```
5. User: `www`
6. Auto-start: **Yes**

## Backup Setup

1. Go to **Cron → Add Cron**
2. Select **Backup Site**
3. Choose your website
4. Set backup frequency (e.g., daily at 2 AM)
5. Choose retention period

## Access Your Site

**Frontend:**

```
https://your-domain.com
```

**Backend:**

```
https://your-domain.com/panel/login
```

**Default Credentials:**

- Email: `admin@demo.com`
- Password: `wncms.cc`

> [!WARNING]
> Change the default password immediately after first login!

## Next Steps

- Configure your site settings in `/panel/settings`
- Upload your theme
- Create content
- Set up email configuration
- Configure cache settings

## See Also

- [Installation Guide](./installation.md) - General installation guide
- [Requirements](./requirements.md) - System requirements
- [Docker Installation](./docker.md) - Docker setup guide
