# 主题结构

## 概述

WNCMS 主题遵循标准化的目录结构，将视图、资源、配置和功能分离。了解此结构对于开发自订主题或修改现有主题至关重要。

## 目录结构

一个完整的 WNCMS 主题具有以下结构：

```
themes/
└── your-theme/
    ├── assets/               # 主题静态资源
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   │   └── app.js
    │   └── images/
    │       └── logo.png
    ├── views/                # Blade 范本
    │   ├── layouts/
    │   │   └── app.blade.php
    │   ├── pages/
    │   │   ├── home.blade.php
    │   │   ├── blog.blade.php
    │   │   └── show.blade.php
    │   ├── posts/
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── parts/
    │   │   ├── header.blade.php
    │   │   └── footer.blade.php
    │   └── components/
    │       └── card.blade.php
    ├── lang/                 # 主题翻译
    │   ├── en/
    │   │   └── word.php
    │   ├── zh_TW/
    │   │   └── word.php
    │   └── zh_CN/
    │       └── word.php
    ├── config.php            # 主题配置
    ├── functions.php         # 主题辅助函数
    └── screenshot.png        # 主题预览（建议 800×600px）
```

## 核心档案

### config.php

主题配置档案定义元数据、主题选项和预设值。

**必需结构：**

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

return [
    'info' => [
        'id' => 'your-theme',
        'type' => 'blog',
        'name' => [
            'en' => 'Your Theme',
            'zh_TW' => '你的主题',
        ],
        'description' => [
            'en' => 'A beautiful theme for WNCMS',
            'zh_TW' => '一个美丽的 WNCMS 主题',
        ],
        'author' => 'Your Name',
        'version' => '1.0.0',
        'created_at' => '2025-01-01',
        'updated_at' => '2025-01-01',
        'demo_url' => 'https://example.com',
    ],

    'option_tabs' => [
        // 主题选项配置
    ],

    'default' => [
        // 主题选项的预设值
    ],
];
```

**关键区块：**

- **info**：在后台显示的主题元数据
- **option_tabs**：可自订的主题选项（请参阅 [配置](./config.md)）
- **default**：主题选项的预设值

### functions.php

包含自订辅助函数和主题特定逻辑。

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/**
 * 自订主题辅助函数
 */

// 范例：取得精选文章
if (!function_exists('get_featured_posts')) {
    function get_featured_posts($limit = 5) {
        return wncms()->post()->getList([
            'tag_ids' => [1], // 精选标签 ID
            'count' => $limit,
        ]);
    }
}

// 范例：格式化文章日期
if (!function_exists('format_post_date')) {
    function format_post_date($date) {
        return $date->format('F j, Y');
    }
}
```

**最佳实践：**

- 定义前始终检查函数是否存在
- 使用 `WNCMS_THEME_START` 防护以防止直接存取
- 保持函数专注且可重用
- 为复杂函数提供文档

### screenshot.png

在后台主题选择器中显示的主题预览图片。

**规格：**

- **建议尺寸**：800×600px
- **格式**：PNG 或 JPG
- **位置**：主题根目录
- **用途**：在管理面板中的视觉预览

## Views 目录

### Layouts

定义整体页面结构的主范本。

**layouts/app.blade.php：**

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title ?? $website->site_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ $website->site_favicon ?: asset('wncms/images/logos/favicon.png') }}">

    {{-- 主题 CSS --}}
    <link rel="stylesheet" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}">

    @stack('head_css')
    {!! $website->head_code !!}
</head>
<body>
    @include(wncms()->theme()->view($themeId, 'parts.header'))

    @yield('content')

    @include(wncms()->theme()->view($themeId, 'parts.footer'))

    <script src="{{ wncms()->theme()->asset($themeId, 'js/app.js') }}"></script>
    @stack('foot_js')
    {!! $website->foot_code !!}
</body>
</html>
```

### Pages

不同内容类型的页面范本。

**必需页面：**

- **home.blade.php**：首页范本
- **blog.blade.php**：部落格列表页
- **show.blade.php**：单页范本

**pages/home.blade.php：**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <h1>{{ gto('site_slogan', 'Welcome to WNCMS') }}</h1>

    @php
        $posts = wncms()->post()->getList([
            'count' => gto('homepage_posts_count', 10),
        ]);
    @endphp

    @foreach($posts as $post)
        <article>
            <h2>{{ $post->title }}</h2>
            <p>{{ $post->excerpt }}</p>
            <a href="{{ route('frontend.posts.show', $post->slug) }}">阅读更多</a>
        </article>
    @endforeach
</div>
@endsection
```

**pages/show.blade.php：**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <article>
        <h1>{{ $page->title }}</h1>
        <div class="content">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection
```

### Posts

部落格文章的范本。

**posts/index.blade.php：**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <h1>{{ gto('blog_title', '部落格') }}</h1>

    @foreach($posts as $post)
        <article class="post-preview">
            @if($post->thumbnail)
                <img src="{{ $post->thumbnail }}" alt="{{ $post->title }}">
            @endif
            <h2>
                <a href="{{ route('frontend.posts.show', $post->slug) }}">
                    {{ $post->title }}
                </a>
            </h2>
            <p>{{ $post->excerpt }}</p>
        </article>
    @endforeach

    {{ $posts->links() }}
</div>
@endsection
```

**posts/show.blade.php：**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <article class="post-single">
        <h1>{{ $post->title }}</h1>

        <div class="post-meta">
            <span>{{ $post->created_at->format('M d, Y') }}</span>
            <span>作者 {{ $post->author->name }}</span>
        </div>

        @if($post->thumbnail)
            <img src="{{ $post->thumbnail }}" alt="{{ $post->title }}" class="featured-image">
        @endif

        <div class="post-content">
            {!! $post->content !!}
        </div>

        @if($post->tags->count())
            <div class="post-tags">
                @foreach($post->tags as $tag)
                    <a href="{{ route('frontend.tags.show', $tag->slug) }}">
                        {{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </article>
</div>
@endsection
```

### Parts

可重用的范本片段。

**parts/header.blade.php：**

```blade
<header>
    <div class="container">
        <div class="logo">
            <a href="{{ route('frontend.pages.home') }}">
                <img src="{{ gto('site_logo', $website->site_logo) }}" alt="{{ $website->site_name }}">
            </a>
        </div>

        <nav class="main-menu">
            @if(gto('header_menu'))
                <ul>
                    @foreach(wncms()->menu()->getMenuParentItems(gto('header_menu')) as $menuItem)
                        @php $menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem); @endphp
                        <li>
                            <a href="{{ $menuItemUrl }}"
                               @if($menuItem->is_new_window) target="_blank" @endif>
                                {{ $menuItem->name }}
                            </a>

                            @if($menuItem->children->count())
                                <ul class="submenu">
                                    @foreach($menuItem->children as $subMenuItem)
                                        @php $subMenuItemUrl = wncms()->menu()->getMenuItemUrl($subMenuItem); @endphp
                                        <li>
                                            <a href="{{ $subMenuItemUrl }}"
                                               @if($subMenuItem->is_new_window) target="_blank" @endif>
                                                {{ $subMenuItem->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </nav>
    </div>
</header>
```

**parts/footer.blade.php：**

```blade
<footer>
    <div class="container">
        <div class="footer-content">
            <p>&copy; {{ date('Y') }} {{ $website->site_name }}</p>

            @if(gto('footer_menu'))
                <nav class="footer-menu">
                    <ul>
                        @foreach(wncms()->menu()->getMenuParentItems(gto('footer_menu')) as $menuItem)
                            @php $menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem); @endphp
                            <li>
                                <a href="{{ $menuItemUrl }}">{{ $menuItem->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>
</footer>
```

### Components

可重用的 UI 组件。

**components/card.blade.php：**

```blade
<div class="card {{ $class ?? '' }}">
    @if(isset($image))
        <img src="{{ $image }}" alt="{{ $title ?? '' }}" class="card-image">
    @endif

    <div class="card-body">
        @if(isset($title))
            <h3 class="card-title">{{ $title }}</h3>
        @endif

        @if(isset($content))
            <p class="card-content">{{ $content }}</p>
        @endif

        @if(isset($link))
            <a href="{{ $link }}" class="card-link">
                {{ $linkText ?? __('阅读更多') }}
            </a>
        @endif
    </div>
</div>
```

使用方式：

```blade
@include("$themeId::components.card", [
    'image' => $post->thumbnail,
    'title' => $post->title,
    'content' => $post->excerpt,
    'link' => route('frontend.posts.show', $post->slug),
])
```

## Assets 目录

### CSS

**assets/css/style.css：**

```css
/* 主题变数 */
:root {
  --primary-color: #007bff;
  --text-color: #333;
  --background-color: #fff;
}

/* 版面配置 */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

/* 排版 */
body {
  font-family: 'Arial', sans-serif;
  color: var(--text-color);
  background-color: var(--background-color);
}

/* 组件 */
.card {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 20px;
}
```

### JavaScript

**assets/js/app.js：**

```javascript
// 主题 JavaScript
;(function ($) {
  'use strict'

  // 行动选单切换
  $('.menu-toggle').on('click', function () {
    $('.main-menu').toggleClass('active')
  })

  // 平滑卷动
  $('a[href^="#"]').on('click', function (e) {
    e.preventDefault()
    var target = $(this.hash)
    if (target.length) {
      $('html, body').animate(
        {
          scrollTop: target.offset().top - 80,
        },
        500,
      )
    }
  })
})(jQuery)
```

## 语言档案

**lang/en/word.php：**

```php
<?php

return [
    'home' => 'Home',
    'blog' => 'Blog',
    'about' => 'About',
    'contact' => 'Contact',
    'read_more' => 'Read More',
    'latest_posts' => 'Latest Posts',
];
```

**lang/zh_TW/word.php：**

```php
<?php

return [
    'home' => '首页',
    'blog' => '部落格',
    'about' => '关于',
    'contact' => '联络',
    'read_more' => '阅读更多',
    'latest_posts' => '最新文章',
];
```

**在 Blade 中使用：**

```blade
{{ __("$themeId::word.read_more") }}
```

## 主题载入过程

### 1. 侦测

ThemeServiceProvider 从网站设定中侦测启用的主题：

```php
$themeId = $website->theme ?: 'default';
```

### 2. 载入配置

```php
// 载入 config.php
$config = include public_path("themes/{$themeId}/config.php");
config(["theme.{$themeId}" => $config]);
```

### 当 `/public/themes` 缺失时的核心主题回退

如果 `public/themes/{themeId}` 不存在，且 `{themeId}` 属于核心主题，WNCMS 会回退到以下内建主题目录：

```text
{WNCMS_RESOURCES_PATH}/themes/{themeId}
```

这个回退会同时套用于配置、视图、翻译与 `functions.php`。
如果当前启用的主题不是核心主题且公开目录缺失，系统仍会保持“主题未启用”行为。

### 3. 注册视图

```php
// 使用命名空间注册主题视图
$this->loadViewsFrom(
    public_path("themes/{$themeId}/views"),
    $themeId
);
```

### 4. 载入翻译

```php
// 载入主题翻译
$this->loadTranslationsFrom(
    public_path("themes/{$themeId}/lang"),
    $themeId
);
```

### 5. 载入函数

```php
// 执行 functions.php
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}
```

### 6. 主题生命周期 Hooks

ThemeServiceProvider 现在会派发生命周期 hooks，让主题/插件开发者无需覆写 provider 即可扩充载入行为：

- `wncms.frontend.themes.boot.before`
- `wncms.frontend.themes.load.before`
- `wncms.frontend.themes.load.after`
- `wncms.frontend.themes.boot.after`

`load.before` 对 `$themeId` 与 `$themePath` 使用引用参数，监听器可在运行时调整解析结果。
完整参数说明请见 [Themes 事件](../event/themes.md)。

## 辅助函数

### 主题资源载入

```blade
{{-- CSS --}}
<link rel="stylesheet" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}">

{{-- JavaScript --}}
<script src="{{ wncms()->theme()->asset($themeId, 'js/app.js') }}"></script>

{{-- Images --}}
<img src="{{ wncms()->theme()->asset($themeId, 'images/logo.png') }}">
```

### 主题视图载入

```blade
{{-- 包含主题视图 --}}
@include(wncms()->theme()->view($themeId, 'parts.header'))

{{-- 扩充主题版面 --}}
@extends("$themeId::layouts.app")
```

### 主题选项

```blade
{{-- 取得主题选项 --}}
{{ gto('site_logo') }}

{{-- 取得主题选项（含预设值）--}}
{{ gto('site_slogan', 'Welcome to WNCMS') }}

{{-- 取得主题翻译 --}}
{{ __("$themeId::word.read_more") }}
```

## 最佳实践

### 1. 安全性

PHP 档案中始终包含安全防护：

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}
```

### 2. 命名空间使用

为视图和翻译使用主题命名空间：

```blade
{{-- 正确 --}}
@extends("$themeId::layouts.app")
{{ __("$themeId::word.home") }}

{{-- 避免硬编码 --}}
@extends("starter::layouts.app")
```

### 3. 资源优化

- 为正式环境压缩 CSS 和 JavaScript
- 优化图片（建议使用 WebP 格式）
- 为图片使用延迟载入
- 尽可能合并 CSS/JS 档案

### 4. 响应式设计

确保行动优先方法：

```css
/* 行动优先 */
.container {
  width: 100%;
}

/* 平板 */
@media (min-width: 768px) {
  .container {
    width: 750px;
  }
}

/* 桌面 */
@media (min-width: 1200px) {
  .container {
    width: 1170px;
  }
}
```

### 5. 主题选项

为可自订元素使用主题选项：

```blade
{{-- 好：可透过后台自订 --}}
<div style="background-color: {{ gto('primary_color', '#007bff') }}">

{{-- 避免：硬编码值 --}}
<div style="background-color: #007bff">
```

## 范本阶层

WNCMS 遵循以下范本解析顺序：

1. **自订别名视图**：`{themeId}::pages.{slug}`
2. **范本页面**：`{themeId}::pages.templates.{templateId}`
3. **纯页面**：`{themeId}::pages.show`
4. **回退**：重导向至首页

范例：

```php
// 1. 检查自订别名视图
if (view()->exists("{$themeId}::pages.about-us")) {
    return view("{$themeId}::pages.about-us");
}

// 2. 检查范本
if ($page->type === 'template') {
    return view("{$themeId}::pages.templates.{$templateId}", compact('page'));
}

// 3. 使用预设显示页面
return view("{$themeId}::pages.show", compact('page'));
```

## 疑难排解

### 主题未载入

1. 检查主题资料夹是否存在于 `/public/themes/{themeId}`
2. 验证 `config.php` 存在且有效
3. 检查档案权限（目录 755，档案 644）
4. 清除快取：`php artisan cache:clear`

### 找不到视图

1. 确保视图在 `/views/` 子目录中
2. 检查视图命名空间与主题 ID 匹配
3. 使用正确的视图路径语法：`{themeId}::path.to.view`

### 资源未载入

1. 验证资源在 `/assets/` 子目录中
2. 检查资源路径：`wncms()->theme()->asset($themeId, 'path/to/asset')`
3. 确保公用磁碟可存取
4. 清除浏览器快取

### 翻译无效

1. 检查语言档案是否存在于 `/lang/{locale}/`
2. 使用正确的翻译语法：`__("{themeId}::word.key")`
3. 清除翻译快取：`php artisan config:clear`

## 另请参阅

- [主题配置](./config.md)
- [主题分页](./pagination.md)
- [主题选单](./menu.md)
- [Frontend Controller](../controller/frontend-controller.md)
