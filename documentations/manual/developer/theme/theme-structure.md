# Theme Structure

## Overview

WNCMS themes follow a standardized directory structure that separates views, assets, configuration, and functionality. Understanding this structure is essential for developing custom themes or modifying existing ones.

## Directory Structure

A complete WNCMS theme has the following structure:

```
themes/
└── your-theme/
    ├── assets/               # Theme static assets
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   │   └── app.js
    │   └── images/
    │       └── logo.png
    ├── views/                # Blade templates
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
    ├── lang/                 # Theme translations
    │   ├── en/
    │   │   └── word.php
    │   ├── zh_TW/
    │   │   └── word.php
    │   └── zh_CN/
    │       └── word.php
    ├── config.php            # Theme configuration
    ├── functions.php         # Theme helper functions
    └── screenshot.png        # Theme preview (800×600px recommended)
```

## Core Files

### config.php

The theme configuration file defines metadata, theme options, and default values.

**Required Structure:**

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
            'zh_TW' => '你的主題',
        ],
        'description' => [
            'en' => 'A beautiful theme for WNCMS',
            'zh_TW' => '一個美麗的 WNCMS 主題',
        ],
        'author' => 'Your Name',
        'version' => '1.0.0',
        'created_at' => '2025-01-01',
        'updated_at' => '2025-01-01',
        'demo_url' => 'https://example.com',
    ],

    'option_tabs' => [
        // Theme options configuration
    ],

    'default' => [
        // Default theme option values
    ],
];
```

**Key Sections:**

- **info**: Theme metadata displayed in backend
- **option_tabs**: Customizable theme options (see [Config](./config.md))
- **default**: Default values for theme options

### functions.php

Contains custom helper functions and theme-specific logic.

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/**
 * Custom theme helper functions
 */

// Example: Get featured posts
if (!function_exists('get_featured_posts')) {
    function get_featured_posts($limit = 5) {
        return wncms()->post()->getList([
            'tag_ids' => [1], // Featured tag ID
            'count' => $limit,
        ]);
    }
}

// Example: Format post date
if (!function_exists('format_post_date')) {
    function format_post_date($date) {
        return $date->format('F j, Y');
    }
}
```

**Best Practices:**

- Always check if function exists before defining
- Use `WNCMS_THEME_START` guard to prevent direct access
- Keep functions focused and reusable
- Document complex functions

### screenshot.png

Theme preview image displayed in the backend theme selector.

**Specifications:**

- **Recommended Size**: 800×600px
- **Format**: PNG or JPG
- **Location**: Theme root directory
- **Purpose**: Visual preview in admin panel

## Views Directory

### Layouts

Master templates that define the overall page structure.

**layouts/app.blade.php:**

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

    {{-- Theme CSS --}}
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

Page templates for different content types.

**Required Pages:**

- **home.blade.php**: Homepage template
- **blog.blade.php**: Blog listing page
- **show.blade.php**: Single page template

**pages/home.blade.php:**

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
            <a href="{{ route('frontend.posts.show', $post->slug) }}">Read More</a>
        </article>
    @endforeach
</div>
@endsection
```

**pages/show.blade.php:**

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

Templates for blog posts.

**posts/index.blade.php:**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <h1>{{ gto('blog_title', 'Blog') }}</h1>

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

**posts/show.blade.php:**

```blade
@extends("$themeId::layouts.app")

@section('content')
<div class="container">
    <article class="post-single">
        <h1>{{ $post->title }}</h1>

        <div class="post-meta">
            <span>{{ $post->created_at->format('M d, Y') }}</span>
            <span>By {{ $post->author->name }}</span>
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

Reusable template fragments.

**parts/header.blade.php:**

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

**parts/footer.blade.php:**

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

Reusable UI components.

**components/card.blade.php:**

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
                {{ $linkText ?? __('Read More') }}
            </a>
        @endif
    </div>
</div>
```

Usage:

```blade
@include("$themeId::components.card", [
    'image' => $post->thumbnail,
    'title' => $post->title,
    'content' => $post->excerpt,
    'link' => route('frontend.posts.show', $post->slug),
])
```

## Assets Directory

### CSS

**assets/css/style.css:**

```css
/* Theme Variables */
:root {
  --primary-color: #007bff;
  --text-color: #333;
  --background-color: #fff;
}

/* Layout */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

/* Typography */
body {
  font-family: 'Arial', sans-serif;
  color: var(--text-color);
  background-color: var(--background-color);
}

/* Components */
.card {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 20px;
}
```

### JavaScript

**assets/js/app.js:**

```javascript
// Theme JavaScript
;(function ($) {
  'use strict'

  // Mobile menu toggle
  $('.menu-toggle').on('click', function () {
    $('.main-menu').toggleClass('active')
  })

  // Smooth scroll
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

## Language Files

**lang/en/word.php:**

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

**lang/zh_TW/word.php:**

```php
<?php

return [
    'home' => '首頁',
    'blog' => '部落格',
    'about' => '關於',
    'contact' => '聯絡',
    'read_more' => '閱讀更多',
    'latest_posts' => '最新文章',
];
```

**Usage in Blade:**

```blade
{{ __("$themeId::word.read_more") }}
```

## Theme Loading Process

### 1. Detection

ThemeServiceProvider detects the active theme from the website settings:

```php
$themeId = $website->theme ?: 'default';
```

### 2. Configuration Loading

```php
// Load config.php
$config = include public_path("themes/{$themeId}/config.php");
config(["theme.{$themeId}" => $config]);
```

### Core Theme Fallback When `/public/themes` Is Missing

If `public/themes/{themeId}` does not exist and `{themeId}` is a core theme, WNCMS now falls back to packaged theme files under:

```text
{WNCMS_RESOURCES_PATH}/themes/{themeId}
```

This fallback applies to config, views, translations, and `functions.php`.
If the active theme is not a core theme and its public folder is missing, WNCMS keeps the inactive-theme behavior.

### 3. View Registration

```php
// Register theme views with namespace
$this->loadViewsFrom(
    public_path("themes/{$themeId}/views"),
    $themeId
);
```

### 4. Translation Loading

```php
// Load theme translations
$this->loadTranslationsFrom(
    public_path("themes/{$themeId}/lang"),
    $themeId
);
```

### 5. Functions Loading

```php
// Execute functions.php
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}
```

### 6. Theme Lifecycle Hooks

ThemeServiceProvider now dispatches lifecycle hooks so theme/plugin developers can extend loading behavior without overriding the provider:

- `wncms.frontend.themes.boot.before`
- `wncms.frontend.themes.load.before`
- `wncms.frontend.themes.load.after`
- `wncms.frontend.themes.boot.after`

`load.before` uses references for `$themeId` and `$themePath`, so listeners can adjust the resolved theme at runtime.
See full payload details in [Themes Events](../event/themes.md).

## Helper Functions

### Theme Asset Loading

```blade
{{-- CSS --}}
<link rel="stylesheet" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}">

{{-- JavaScript --}}
<script src="{{ wncms()->theme()->asset($themeId, 'js/app.js') }}"></script>

{{-- Images --}}
<img src="{{ wncms()->theme()->asset($themeId, 'images/logo.png') }}">
```

### Theme View Loading

```blade
{{-- Include theme view --}}
@include(wncms()->theme()->view($themeId, 'parts.header'))

{{-- Extend theme layout --}}
@extends("$themeId::layouts.app")
```

### Theme Options

```blade
{{-- Get theme option --}}
{{ gto('site_logo') }}

{{-- Get theme option with default --}}
{{ gto('site_slogan', 'Welcome to WNCMS') }}

{{-- Get theme translation --}}
{{ __("$themeId::word.read_more") }}
```

## Best Practices

### 1. Security

Always include the security guard in PHP files:

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}
```

### 2. Namespace Usage

Use theme namespace for views and translations:

```blade
{{-- Correct --}}
@extends("$themeId::layouts.app")
{{ __("$themeId::word.home") }}

{{-- Avoid hardcoding --}}
@extends("starter::layouts.app")
```

### 3. Asset Optimization

- Minify CSS and JavaScript for production
- Optimize images (WebP format recommended)
- Use lazy loading for images
- Combine CSS/JS files when possible

### 4. Responsive Design

Ensure mobile-first approach:

```css
/* Mobile first */
.container {
  width: 100%;
}

/* Tablet */
@media (min-width: 768px) {
  .container {
    width: 750px;
  }
}

/* Desktop */
@media (min-width: 1200px) {
  .container {
    width: 1170px;
  }
}
```

### 5. Theme Options

Use theme options for customizable elements:

```blade
{{-- Good: Customizable via backend --}}
<div style="background-color: {{ gto('primary_color', '#007bff') }}">

{{-- Avoid: Hardcoded values --}}
<div style="background-color: #007bff">
```

## Template Hierarchy

WNCMS follows this template resolution order:

1. **Custom Slug View**: `{themeId}::pages.{slug}`
2. **Template Page**: `{themeId}::pages.templates.{templateId}`
3. **Plain Page**: `{themeId}::pages.show`
4. **Fallback**: Redirect to home

Example:

```php
// 1. Check for custom slug view
if (view()->exists("{$themeId}::pages.about-us")) {
    return view("{$themeId}::pages.about-us");
}

// 2. Check for template
if ($page->type === 'template') {
    return view("{$themeId}::pages.templates.{$templateId}", compact('page'));
}

// 3. Use default show page
return view("{$themeId}::pages.show", compact('page'));
```

## Troubleshooting

### Theme Not Loading

1. Check theme folder exists in `/public/themes/{themeId}`
2. Verify `config.php` exists and is valid
3. Check file permissions (755 for directories, 644 for files)
4. Clear cache: `php artisan cache:clear`

### Views Not Found

1. Ensure views are in `/views/` subdirectory
2. Check view namespace matches theme ID
3. Use correct view path syntax: `{themeId}::path.to.view`

### Assets Not Loading

1. Verify assets are in `/assets/` subdirectory
2. Check asset path: `wncms()->theme()->asset($themeId, 'path/to/asset')`
3. Ensure public disk is accessible
4. Clear browser cache

### Translation Not Working

1. Check language files exist in `/lang/{locale}/`
2. Use correct translation syntax: `__("{themeId}::word.key")`
3. Clear translation cache: `php artisan config:clear`

## See Also

- [Theme Configuration](./config.md)
- [Theme Pagination](./pagination.md)
- [Theme Menus](./menu.md)
- [Frontend Controller](../controller/frontend-controller.md)
