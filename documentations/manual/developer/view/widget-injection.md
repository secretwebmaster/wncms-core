Below is **the Markdown file ONLY** and **the correct directory**.

I chose this location:

```
docs/developer/view/widget-injection.md
```

Reason:

- It belongs to **developer** (plugin/theme developers)
- It affects **view layer**
- Other folders (controller / theme / helper) do not match the purpose
- You will later create more view-related docs (Blade directives, layout injection)
  → `developer/view/` becomes a reusable category.

If you want the zh-TW version afterward, tell me.

# `docs/developer/view/widget-injection.md`

````md
# View Widget Injection

WNCMS provides a powerful **View Widget Injection** system that allows packages, plugins, and themes to inject Blade views into any WNCMS template without modifying core files.

This system enables developers to extend backend and frontend views dynamically by registering widgets from a Service Provider.

## Overview

You can inject custom Blade views into predefined “widget positions” using:

```php
wncms()->registerViewWidget($key, $view, $data = []);
```
````

Then, inside any Blade template, you render the registered widgets using:

```blade
@widget('your.widget.key')
```

This architecture lets multiple packages add UI components to the same location at runtime.

## How It Works

### 1. Register a widget in your package or plugin

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

- **$key** = where the widget should appear
- **$view** = Blade file to render
- **$data** = variables passed to that Blade view

### 2. Render widgets inside Blade using `@widget`

In any WNCMS blade file:

```blade
@widget('wncms.backend.admin.dashboard.above_update_log')
```

WNCMS will automatically find all widgets registered for this position and render them in order.

## Common Use Cases

### Dashboard extensions

Packages can add:

- API usage stats
- Cache info
- System health
- Custom tools
- Traffic logs
- Marketplace banners

Example:

```php
wncms()->registerViewWidget(
    'wncms.backend.admin.dashboard.above_update_log',
    'my-plugin::widgets.server-status'
);
```

### Custom Backend Panels

Example keys you might define:

- `wncms.backend.users.index.before_table`
- `wncms.backend.posts.form.after_content`
- `wncms.backend.settings.email.after_form`

### Theme & Frontend extensions

Example:

```blade
@widget('theme.home.after_slider')
```

Then a theme provider can register:

```php
wncms()->registerViewWidget(
    'theme.home.after_slider',
    'my-theme::blocks.featured-links'
);
```

## Creating Your Own Widget Positions

You can create custom widget positions by simply adding `@widget('your.custom.key')` anywhere inside a Blade template.

Example in a theme:

```blade
<div class="hero">
    @include('theme::parts.hero')

    @widget('theme.hero.after')
</div>
```

Now any plugin/theme can inject content into that location.

## Registering Widgets from a Package

Inside your package’s Service Provider:

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

Make sure your views are registered:

```php
$this->loadViewsFrom(__DIR__ . '/../resources/views', 'my-package');
```

## Blade Directive Implementation (Core)

WNCMS provides the `@widget` directive automatically via:

```
Wncms\Providers\ViewServiceProvider
```

The directive loops through all registered widgets for the given key and renders them.

You do **not** need to do anything extra to enable this.

## Best Practices

- Use unique, descriptive keys such as:

  - `wncms.backend.admin.dashboard.above_update_log`
  - `theme.default.home.after_banner`
  - `wncms.backend.posts.edit.bottom`

- Avoid modifying core WNCMS views—inject instead
- Keep widget views small, self-contained, and dependency-free
- Always namespace your views: `my-package::widget.name`

## Summary

| Feature                | Description                                    |
| ---------------------- | ---------------------------------------------- |
| `registerViewWidget()` | Register a widget to a specific injection key. |
| `@widget()`            | Render all widgets registered to that key.     |
| Extensible             | Supports unlimited third-party widgets.        |
| Non-intrusive          | No need to edit core WNCMS or theme views.     |

Widget Injection is now the recommended method for extending backend and frontend UI in WNCMS.
If you want the **zh-TW translation**, reply:
**"generate zh-TW version"**

```
# DONE
- Selected correct directory: `docs/developer/view/widget-injection.md`
- Provided full production-ready markdown

# PENDING
- zh-TW version (optional)
- Add link to nav sidebar (optional)

Tell me if you want these.
```
