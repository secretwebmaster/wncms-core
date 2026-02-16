# Themes 事件

## 前台主题生命周期

#### `wncms.frontend.themes.boot.before`

在当前网站主题启动前触发。

参数：
- `&$themeId` (string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.load.before`

在加载主题路径前触发。可通过引用修改主题 ID 或路径。

参数：
- `&$themeId` (string)
- `&$themePath` (?string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.load.after`

在主题 config/views/translations/functions 加载完成后触发。

参数：
- `$themeId` (string)
- `$themePath` (string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.boot.after`

在主题启动完成后触发。

参数：
- `$themeId` (string)
- `$website` (Website 模型)

## 监听示例

```php
use Illuminate\Support\Facades\Event;

Event::listen('wncms.frontend.themes.load.before', function (&$themeId, &$themePath, $website) {
    // 示例：特定域名强制使用 default 主题
    if (($website?->domain ?? '') === 'example.com') {
        $themeId = 'default';
        $themePath = public_path('themes/default');
    }
});
```
