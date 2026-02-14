# Helper 总览

## `gss()`

读取系统设定值。

```php
gss('site_name');
gss('theme_options_cache_time', 86400);
```

### 分组 key 语法

`gss()` 支援 `group:key` 的分组读取格式。

```php
gss('wncms-users-telegram-option:enable_telegram_id', true);
```

规则：
- 不含 `:` 时，使用 `group = null` 查询。
- 含 `:` 时，会拆分为 `group` 与 `key` 查询。
- 以 `:` 开头的 key 视为无效，直接回传 fallback。

## `uss()`

更新系统设定值。

```php
uss('site_name', 'WNCMS');
uss('wncms-users-telegram-option:enable_telegram_id', '1');
```

使用分组语法时，会将 `group` 与 `key` 分别写入 `settings` 资料表。

## Plugin loader

使用 `Wncms\Plugins\PluginLoader` 载入插件生命周期实例。

```php
$plugin = app(\Wncms\Plugins\PluginLoader::class)->load('wncms-users-telegram-option');

$pluginId = $plugin->getPluginId();
$plugin->activate();
$plugin->deactivate();
$pluginPath = $plugin->getRootPath();
```
