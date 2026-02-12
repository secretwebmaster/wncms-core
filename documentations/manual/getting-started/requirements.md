# System Requirements

Before installing **WNCMS**, make sure your server or local environment meets the minimum technical requirements listed below.

## Server Environment

| Requirement    | Recommended Version        | Minimum Version                  |
| -------------- | -------------------------- | -------------------------------- |
| **PHP**        | 8.2 or higher              | 8.2                              |
| **Web Server** | Nginx / Apache 2.4+        | Any web server that supports PHP |
| **Database**   | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.0                        |
| **Composer**   | 2.6 or higher              | 2.3                              |

> WNCMS is built on **Laravel 12**, so any environment compatible with Laravel 12 should work.

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

- putenv
- proc_open

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
