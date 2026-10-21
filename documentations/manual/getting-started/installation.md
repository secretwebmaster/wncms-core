# Installation Guide

PHP 8.4 / Laravel 13. New installations only. Use an empty project directory and a dedicated empty database.

```bash
composer create-project laravel/laravel my-project "^13.0" --no-install --no-scripts --no-plugins
cd my-project
composer config allow-plugins false
composer require secretwebmaster/wncms-core:^6.7.0 --no-scripts --no-plugins
php artisan package:discover
php artisan vendor:publish --tag=wncms-core-assets
```

## Run Installer

This v6.7.0 new install requires PHP 8.4 and Laravel 13. Supply your administrator email with `--admin_email=you@example.com`; the CLI asks for the password twice using hidden prompts. The password must contain at least 12 characters and at most 72 UTF-8 bytes. For non-interactive provisioning, provide `WNCMS_INSTALL_ADMIN_PASSWORD` through a temporary protected process environment and remove it afterwards; do not put the administrator password in command arguments or committed files. The web wizard requires the same email, password and confirmation. No shared default administrator or demo manager is created.

When adding this package to a fresh official Laravel application for web installation, run `php artisan vendor:publish --tag=wncms-core-assets` before opening `/install`. Use an empty database dedicated to the new installation; the installer rebuilds its schema.

WNCMS offers a full command-line installer:

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

`--app_locale` sets the default language during installation.
- If omitted, installer uses `config('app.locale')`.
- If an unsupported locale is provided, installer falls back to the configured default locale.
- On Laravel 13 fresh projects where `lang/` does not exist yet, installer will create `lang/` and generate `custom.php` for default locales and the resolved app locale automatically.

> [!NOTE]
> Fresh Laravel projects can keep default Laravel migrations (`users`, `jobs`, etc.).
> `wncms:install` is compatible with those base tables and will extend missing WNCMS columns automatically.

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
    --app_locale="en" \
    --force_https \
    --domain="example.com" \
    --site_name="My Website"
```

The installer will automatically:

- Configure `.env`
- Generate keys
- Run migrations
- Remove Laravel default `routes/web.php` welcome homepage route when detected
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
> Sign in using the administrator email and password you supplied during installation.
> login URL: `https://your-domain.com/panel/login`
> There are no shared default login credentials.

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
