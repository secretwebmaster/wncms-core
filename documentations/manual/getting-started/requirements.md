# System Requirements

Before installing **WNCMS**, make sure your server or local environment meets the minimum technical requirements listed below.

## Server Environment

| Requirement    | Recommended Version        | Minimum Version                  |
| -------------- | -------------------------- | -------------------------------- |
| **PHP**        | 8.5                        | 8.4                              |
| **Web Server** | Nginx / Apache 2.4+        | Any web server that supports PHP |
| **Database**   | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.0                        |
| **Composer**   | 2.6 or higher              | 2.3                              |

> WNCMS `v6.3.0` is built on **Laravel 13**, so your environment must be compatible with Laravel 13.

## Nginx rewriite rules

```
rewrite ^/.*\.blade\.php$ /__block_blade_403 last;

location = /__block_blade_403 {
    return 403;
}

location / {
    try_files $uri $uri/ /index.php$is_args$query_string;
}

```

## PHP Extensions

Ensure the following PHP extensions are enabled in your `php.ini`:

- `fileinfo`
- `exif`
- `mbstring`
- `opcache` (Optional but Recommended)
- `redis` (Optional but Recommended)

## PHP Allowd Functions

Remove from disabled function list if any

- `putenv`
- `proc_open`

## Optional but Recommended

| Component      | Purpose                      |
| -------------- | ---------------------------- |
| **Redis**      | Cache and queue optimization |
| **Supervisor** | Queue worker management      |

## File Permissions

The following directories must be **writable** by the web server user:

```
storage/
bootstrap/cache/
public/media/

```

Once your environment meets these requirements, you’re ready to [install WNCMS](./installation).
