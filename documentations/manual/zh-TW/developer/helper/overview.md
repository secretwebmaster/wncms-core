# Helper 總覽

## 舊版 Helper 遷移

部分舊版全域 helper 正逐步遷移至 manager/core 方法。

- 廢棄 helper 會先以「整段註解」方式觀察相容性。
- 新程式碼優先使用 `wncms()->{manager}()->...` 與 `wncms()->{coreMethod}(... )`。
- URL 協議 helper 遷移：
  - 使用 `wncms()->addHttp($link)` 取代舊版 `wncms_add_http($link)`。
  - 使用 `wncms()->addHttps($link)` 取代舊版 `wncms_add_https($link)`。
- 圖片 MIME helper 遷移：
  - 使用 `wncms()->getImageType($path)` 取代舊版 `getSeoImageType($path)`。
- 模型文案 helper 遷移：
  - 使用 `wncms()->getModelWord($modelName, $action)` 取代舊版 `wncms_model_word($modelName, $action)`。
- Tag 文案 helper 移除：
  - `wncms_tag_word(...)` 已移除，請直接組合翻譯鍵。
- 資料表名稱轉模型名稱 helper 移除：
  - `wncms_get_model_name_from_table_name(...)` 已移除（未使用）。
- 舊版 helper 歸檔：
  - 註解的舊版 helper 程式碼已集中至 `helpers/deprecated.php` 的 `//% Depracated soon` 區塊。

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
