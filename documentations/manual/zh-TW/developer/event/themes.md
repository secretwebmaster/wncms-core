# Themes 事件

## 前台主題生命週期

#### `wncms.frontend.themes.boot.before`

在目前網站主題啟動前觸發。

參數：
- `&$themeId` (string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.load.before`

在載入主題路徑前觸發。可透過引用修改主題 ID 或路徑。

參數：
- `&$themeId` (string)
- `&$themePath` (?string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.load.after`

在主題 config/views/translations/functions 載入完成後觸發。

參數：
- `$themeId` (string)
- `$themePath` (string)
- `$website` (Website 模型)

#### `wncms.frontend.themes.boot.after`

在主題啟動完成後觸發。

參數：
- `$themeId` (string)
- `$website` (Website 模型)

## 監聽範例

```php
use Illuminate\Support\Facades\Event;

Event::listen('wncms.frontend.themes.load.before', function (&$themeId, &$themePath, $website) {
    // 範例：特定網域強制使用 default 主題
    if (($website?->domain ?? '') === 'example.com') {
        $themeId = 'default';
        $themePath = public_path('themes/default');
    }
});
```
