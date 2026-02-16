# Helper 总览

## 旧版 Helper 迁移

部分旧版全域 helper 正逐步迁移至 manager/core 方法。

- 废弃 helper 会先采用「整段注释」方式观察兼容性。
- 新代码优先使用 `wncms()->{manager}()->...` 与 `wncms()->{coreMethod}(... )`。
- URL 协议 helper 迁移：
  - 使用 `wncms()->addHttp($link)` 取代旧版 `wncms_add_http($link)`。
  - 使用 `wncms()->addHttps($link)` 取代旧版 `wncms_add_https($link)`。
- 图片 MIME helper 迁移：
  - 使用 `wncms()->getImageType($path)` 取代旧版 `getSeoImageType($path)`。
- 模型文案 helper 迁移：
  - 使用 `wncms()->getModelWord($modelName, $action)` 取代旧版 `wncms_model_word($modelName, $action)`。
- Tag 文案 helper 移除：
  - `wncms_tag_word(...)` 已移除，请直接组合翻译键。
- 表名转模型名 helper 移除：
  - `wncms_get_model_name_from_table_name(...)` 已移除（未使用）。
- 旧版 helper 归档：
  - 注释的旧版 helper 代码已集中到 `helpers/deprecated.php` 的 `//% Depracated soon` 区块。

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
