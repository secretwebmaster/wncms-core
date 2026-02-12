# View Widget Injection

WNCMS 提供强大的 **View Widget Injection** 系统，允许 packages、plugins 与 themes 在不修改核心档案的情况下，将 Blade views 注入任何 WNCMS template。

此系统使开发者能够透过从 Service Provider 注册 widgets 来动态扩充 backend 与 frontend views。

## 概述

你可以使用以下方式将自订 Blade views 注入到预定义的「widget positions」：

```php
wncms()->registerViewWidget($key, $view, $data = []);
```

然后，在任何 Blade template 中，使用以下方式渲染已注册的 widgets：

```blade
@widget('your.widget.key')
```

此架构让多个 packages 能在执行时将 UI 元件加入相同位置。

## 运作方式

### 1. 在你的 package 或 plugin 中注册 widget

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

- **$key** = widget 应该出现的位置
- **$view** = 要渲染的 Blade 档案
- **$data** = 传递给该 Blade view 的变数

### 2. 在 Blade 中使用 `@widget` 渲染 widgets

在任何 WNCMS blade 档案中：

```blade
@widget('wncms.backend.admin.dashboard.above_update_log')
```

WNCMS 会自动找到为此位置注册的所有 widgets 并按顺序渲染它们。

## 常见使用案例

### Dashboard 扩充

Packages 可以新增：

- API usage stats
- Cache info
- System health
- Custom tools
- Traffic logs
- Marketplace banners

范例：

```php
wncms()->registerViewWidget(
    'wncms.backend.admin.dashboard.above_update_log',
    'my-plugin::widgets.server-status'
);
```

### 自订 Backend Panels

你可能定义的范例 keys：

- `wncms.backend.users.index.before_table`
- `wncms.backend.posts.form.after_content`
- `wncms.backend.settings.email.after_form`

### Theme & Frontend 扩充

范例：

```blade
@widget('theme.home.after_slider')
```

然后 theme provider 可以注册：

```php
wncms()->registerViewWidget(
    'theme.home.after_slider',
    'my-theme::blocks.featured-links'
);
```

## 建立你自己的 Widget Positions

你可以透过在 Blade template 中的任何地方加入 `@widget('your.custom.key')` 来建立自订 widget positions。

在 theme 中的范例：

```blade
<div class="hero">
    @include('theme::parts.hero')

    @widget('theme.hero.after')
</div>
```

现在任何 plugin/theme 都可以将内容注入该位置。

## 从 Package 注册 Widgets

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

确保你的 views 已注册：

```php
$this->loadViewsFrom(__DIR__ . '/../resources/views', 'my-package');
```

## Blade Directive 实作 (Core)

WNCMS 透过以下方式自动提供 `@widget` directive：

```
Wncms\Providers\ViewServiceProvider
```

Directive 会回圈遍历为给定 key 注册的所有 widgets 并渲染它们。

你**不需要**做任何额外的事情来启用这个功能。

## 最佳实践

- 使用唯一、具描述性的 keys，例如：

  - `wncms.backend.admin.dashboard.above_update_log`
  - `theme.default.home.after_banner`
  - `wncms.backend.posts.edit.bottom`

- 避免修改核心 WNCMS views—改用注入
- 保持 widget views 小巧、自包含且无依赖性
- 总是为你的 views 使用 namespace：`my-package::widget.name`

## 总结

| 功能                   | 说明                                   |
| ---------------------- | -------------------------------------- |
| `registerViewWidget()` | 将 widget 注册到特定的 injection key。 |
| `@widget()`            | 渲染为该 key 注册的所有 widgets。      |
| Extensible             | 支援无限的第三方 widgets。             |
| Non-intrusive          | 无需编辑核心 WNCMS 或 theme views。    |

Widget Injection 现在是在 WNCMS 中扩充 backend 与 frontend UI 的建议方法。
