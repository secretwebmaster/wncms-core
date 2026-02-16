# Helper Overview

## Legacy Helper Migration

Some legacy global helpers are being phased out in favor of manager/core methods.

- Deprecated helpers may be commented out first for compatibility observation.
- Prefer service access patterns such as `wncms()->{manager}()->...` and `wncms()->{coreMethod}(... )` for new code.
- URL scheme helper migration:
  - Use `wncms()->addHttp($link)` instead of legacy `wncms_add_http($link)`.
  - Use `wncms()->addHttps($link)` instead of legacy `wncms_add_https($link)`.
- Image MIME helper migration:
  - Use `wncms()->getImageType($path)` instead of legacy `getSeoImageType($path)`.
- Model label helper migration:
  - Use `wncms()->getModelWord($modelName, $action)` instead of legacy `wncms_model_word($modelName, $action)`.
- Tag label helper removal:
  - `wncms_tag_word(...)` has been removed. Build labels with translation keys directly.
- Model-name-from-table helper removal:
  - `wncms_get_model_name_from_table_name(...)` has been removed (unused).
- Legacy helper archives:
  - Commented legacy helper blocks are centralized under `helpers/deprecated.php` in the `//% Depracated soon` section.

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
