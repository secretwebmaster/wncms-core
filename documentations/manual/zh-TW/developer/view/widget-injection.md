# View Widget Injection

WNCMS 提供強大的 **View Widget Injection** 系統，允許 packages、plugins 與 themes 在不修改核心檔案的情況下，將 Blade views 注入任何 WNCMS template。

此系統使開發者能夠透過從 Service Provider 註冊 widgets 來動態擴充 backend 與 frontend views。

## 概述

你可以使用以下方式將自訂 Blade views 注入到預定義的「widget positions」：

```php
wncms()->registerViewWidget($key, $view, $data = []);
```

然後，在任何 Blade template 中，使用以下方式渲染已註冊的 widgets：

```blade
@widget('your.widget.key')
```

此架構讓多個 packages 能在執行時將 UI 元件加入相同位置。

## 運作方式

### 1. 在你的 package 或 plugin 中註冊 widget

```php
public function boot()
{
    wncms()->registerViewWidget(
        'wncms.backend.admin.dashboard.above_update_log',
        'my-package::widgets.api_usage',
        [
            'title' => 'API Usage',
            'value' => 123,
        ]
    );
}
```

- **$key** = widget 應該出現的位置
- **$view** = 要渲染的 Blade 檔案
- **$data** = 傳遞給該 Blade view 的變數

### 2. 在 Blade 中使用 `@widget` 渲染 widgets

在任何 WNCMS blade 檔案中：

```blade
@widget('wncms.backend.admin.dashboard.above_update_log')
```

WNCMS 會自動找到為此位置註冊的所有 widgets 並按順序渲染它們。

## 常見使用案例

### Dashboard 擴充

Packages 可以新增：

- API usage stats
- Cache info
- System health
- Custom tools
- Traffic logs
- Marketplace banners

範例：

```php
wncms()->registerViewWidget(
    'wncms.backend.admin.dashboard.above_update_log',
    'my-plugin::widgets.server-status'
);
```

### 自訂 Backend Panels

你可能定義的範例 keys：

- `wncms.backend.users.index.before_table`
- `wncms.backend.posts.form.after_content`
- `wncms.backend.settings.email.after_form`

### Theme & Frontend 擴充

範例：

```blade
@widget('theme.home.after_slider')
```

然後 theme provider 可以註冊：

```php
wncms()->registerViewWidget(
    'theme.home.after_slider',
    'my-theme::blocks.featured-links'
);
```

## 建立你自己的 Widget Positions

你可以透過在 Blade template 中的任何地方加入 `@widget('your.custom.key')` 來建立自訂 widget positions。

在 theme 中的範例：

```blade
<div class="hero">
    @include('theme::parts.hero')

    @widget('theme.hero.after')
</div>
```

現在任何 plugin/theme 都可以將內容注入該位置。

## 從 Package 註冊 Widgets

在你的 package 的 Service Provider 中：

```php
class MyPackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        wncms()->registerViewWidget(
            'wncms.backend.admin.dashboard.above_update_log',
            'my-package::widgets.dashboard_box',
            ['foo' => 'bar']
        );
    }
}
```

確保你的 views 已註冊：

```php
$this->loadViewsFrom(__DIR__ . '/../resources/views', 'my-package');
```

## Blade Directive 實作 (Core)

WNCMS 透過以下方式自動提供 `@widget` directive：

```
Wncms\Providers\ViewServiceProvider
```

Directive 會迴圈遍歷為給定 key 註冊的所有 widgets 並渲染它們。

你**不需要**做任何額外的事情來啟用這個功能。

## 最佳實踐

- 使用唯一、具描述性的 keys，例如：

  - `wncms.backend.admin.dashboard.above_update_log`
  - `theme.default.home.after_banner`
  - `wncms.backend.posts.edit.bottom`

- 避免修改核心 WNCMS views—改用注入
- 保持 widget views 小巧、自包含且無依賴性
- 總是為你的 views 使用 namespace：`my-package::widget.name`

## 總結

| 功能                   | 說明                                   |
| ---------------------- | -------------------------------------- |
| `registerViewWidget()` | 將 widget 註冊到特定的 injection key。 |
| `@widget()`            | 渲染為該 key 註冊的所有 widgets。      |
| Extensible             | 支援無限的第三方 widgets。             |
| Non-intrusive          | 無需編輯核心 WNCMS 或 theme views。    |

Widget Injection 現在是在 WNCMS 中擴充 backend 與 frontend UI 的建議方法。
