# Helper Overview

## `gss()`

Read a system setting value.

```php
gss('site_name');
gss('theme_options_cache_time', 86400);
```

### Grouped key syntax

`gss()` supports grouped key lookups using `group:key`.

```php
gss('wncms-users-telegram-option:enable_telegram_id', true);
```

Rules:
- If no `:` exists, lookup uses `group = null`.
- If `:` exists, lookup uses `group` and `key` from the split value.
- Keys starting with `:` are invalid and return fallback.

## `uss()`

Update a system setting value.

```php
uss('site_name', 'WNCMS');
uss('wncms-users-telegram-option:enable_telegram_id', '1');
```

When grouped syntax is used, `group` is stored separately from `key` in `settings` table.

## Plugin loader

Use `Wncms\Plugins\PluginLoader` to load plugin lifecycle instances.

```php
$plugin = app(\Wncms\Plugins\PluginLoader::class)->load('wncms-users-telegram-option');

$pluginId = $plugin->getPluginId();
$plugin->activate();
$plugin->deactivate();
$pluginPath = $plugin->getRootPath();
```
