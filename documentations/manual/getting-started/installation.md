# Installation Guide

This guide explains how to install **WNCMS** on any server, including aaPanel, CyberPanel, VPS, or local development.

## Create a New Folder

You may install WNCMS into an empty folder:

```bash
mkdir my-project
cd my-project
```

## Download WNCMS

```bash
composer create-project secretwebmaster/wncms .temp --no-interaction
```

This places the full Laravel + WNCMS structure inside `.temp/`.

## Copy Files

Some panels (aaPanel, shared hosting) already create a default `public/` folder.
We must **merge** WNCMS into it safely without deleting uploads or server files.

```bash
# Copy all root files
cp -r .temp/* .
cp -r .temp/.* . 2>/dev/null

# Merge public assets safely
mkdir -p public
cp -rf .temp/public/* public/ 2>/dev/null

# Delete temp folder
rm -rf .temp
```

This method does not require rsync and works on all systems. If your server already have rysnc, you can use rsync. The principle is to move all files one level upper.

## Install Dependencies

```bash
composer install --no-interaction --prefer-dist
composer update secretwebmaster/wncms-core -W

# OR
# if you are running as root user and want to install programmatically without interaction. You can run this

# COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
# COMPOSER_ALLOW_SUPERUSER=1 composer update secretwebmaster/wncms-core -W
```

## Run Installer

WNCMS offers a full command-line installer:

```
php artisan wncms:install \
    {db_driver} {db_host} {db_port} {db_name} {db_user} {db_pass} \
    --app_name="YourApp" \
    --app_url="https://your-domain.com" \
    --force_https \
    --domain="your-domain.com" \
    --site_name="Your Site Name"
```

Before running the installer, create an empty MySQL database and prepare your connection details.

**Example MySQL Database Information**

| Field     | Example Value  | Description          |
| --------- | -------------- | -------------------- |
| db_driver | mysql          | Database driver      |
| db_host   | 127.0.0.1      | Database server host |
| db_port   | 3306           | Default MySQL port   |
| db_name   | example_com    | Your database name   |
| db_user   | example_com    | Database username    |
| db_pass   | ABCDCF12345678 | Database password    |

**Then run:**

```bash
php artisan wncms:install mysql 127.0.0.1 3306 example_com example_com "ABCDCF12345678" \
    --app_name="example" \
    --app_url="https://example.com" \
    --force_https \
    --domain="example.com" \
    --site_name="My Website"
```

The installer will automatically:

- Configure `.env`
- Generate keys
- Run migrations
- Create admin user
- Setup website settings
- Enable HTTPS if specified

## Configure Website Root

Set your web root to:

```
/my-project/public
```

> [!TIP]
> For aaPanel / BT users:
> Open **Website → Site Config → Domain**, then set **Document Root** to:
> `/www/wwwroot/my-project/public`

## Permission

Make sure you have correct permission of these directories. Example for `www` user. It will throw errors when you install via `root` user but run website on `www` or `www-data` user.

```bash
chmod -R 775 storage bootstrap/cache public/media
chown -R www:www .
```

## Complete

You now have a fully working **WNCMS** site with backend, themes, settings, API, and package support.

**Frontend:**

```
https://your-domain.com
```

**Backend:**

```
https://your-domain.com/panel/login
```

> [!WARNING] REMINDER
> You must change the password after installation.
> default user: `admin@demo.com`
> default password: `wncms.cc`

## Next Steps

- **User Guide:** `/user/overview`
- **Developer Guide:** `/developer/overview`
- **Package Development:** `/package/overview`
- **API Documentation:** `/api/overview`

## Platform-Specific Guides

- **aaPanel/BT Panel:** See [aaPanel Installation Guide](./aapanel.md)
- **Docker:** See [Docker Installation Guide](./docker.md)

```

```
