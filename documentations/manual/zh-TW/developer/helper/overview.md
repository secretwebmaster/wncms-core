# Helper 總覽

## `gss()`

讀取系統設定值。

```php
gss('site_name');
gss('theme_options_cache_time', 86400);
```

### 分組 key 語法

`gss()` 支援 `group:key` 的分組讀取格式。

```php
gss('wncms-users-telegram-option:enable_telegram_id', true);
```

規則：
- 不含 `:` 時，使用 `group = null` 查詢。
- 含 `:` 時，會拆分為 `group` 與 `key` 查詢。
- 以 `:` 開頭的 key 視為無效，直接回傳 fallback。

## `uss()`

更新系統設定值。

```php
uss('site_name', 'WNCMS');
uss('wncms-users-telegram-option:enable_telegram_id', '1');
```

使用分組語法時，會將 `group` 與 `key` 分別寫入 `settings` 資料表。

## Plugin loader

使用 `Wncms\Plugins\PluginLoader` 載入外掛生命週期實例。

```php
$plugin = app(\Wncms\Plugins\PluginLoader::class)->load('wncms-users-telegram-option');

$pluginId = $plugin->getPluginId();
$plugin->activate();
$plugin->deactivate();
$pluginPath = $plugin->getRootPath();
```
