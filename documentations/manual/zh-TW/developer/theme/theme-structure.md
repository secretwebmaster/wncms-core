# 主題結構

## 概述

WNCMS 主題遵循標準化的目錄結構，將視圖、資源、配置和功能分離。了解此結構對於開發自訂主題或修改現有主題至關重要。

## 目錄結構

一個完整的 WNCMS 主題具有以下結構：

```
themes/
└── your-theme/
    ├── assets/               # 主題靜態資源
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   │   └── app.js
    │   └── images/
    │       └── logo.png
    ├── views/                # Blade 範本
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
    ├── lang/                 # 主題翻譯
    │   ├── en/
    │   │   └── word.php
    │   ├── zh_TW/
    │   │   └── word.php
    │   └── zh_CN/
    │       └── word.php
    ├── config.php            # 主題配置
    ├── functions.php         # 主題輔助函數
    └── screenshot.png        # 主題預覽（建議 800×600px）
```

## 核心檔案

### config.php

主題配置檔案定義元數據、主題選項和預設值。

**必需結構：**

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
        // 主題選項配置
    ],

    'default' => [
        // 主題選項的預設值
    ],
];
```

**關鍵區塊：**

- **info**：在後台顯示的主題元數據
- **option_tabs**：可自訂的主題選項（請參閱 [配置](./config.md)）
- **default**：主題選項的預設值

### functions.php

包含自訂輔助函數和主題特定邏輯。

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/**
 * 自訂主題輔助函數
 */

// 範例：取得精選文章
if (!function_exists('get_featured_posts')) {
    function get_featured_posts($limit = 5) {
        return wncms()->post()->getList([
            'tag_ids' => [1], // 精選標籤 ID
            'count' => $limit,
        ]);
    }
}

// 範例：格式化文章日期
if (!function_exists('format_post_date')) {
    function format_post_date($date) {
        return $date->format('F j, Y');
    }
}
```

**最佳實踐：**

- 定義前始終檢查函數是否存在
- 使用 `WNCMS_THEME_START` 防護以防止直接存取
- 保持函數專注且可重用
- 為複雜函數提供文檔

### screenshot.png

在後台主題選擇器中顯示的主題預覽圖片。

**規格：**

- **建議尺寸**：800×600px
- **格式**：PNG 或 JPG
- **位置**：主題根目錄
- **用途**：在管理面板中的視覺預覽

## Views 目錄

### Layouts

定義整體頁面結構的主範本。

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

    {{-- 主題 CSS --}}
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

不同內容類型的頁面範本。

**必需頁面：**

- **home.blade.php**：首頁範本
- **blog.blade.php**：部落格列表頁
- **show.blade.php**：單頁範本

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
            <a href="{{ route('frontend.posts.show', $post->slug) }}">閱讀更多</a>
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

部落格文章的範本。

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

可重用的範本片段。

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

可重用的 UI 組件。

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
                {{ $linkText ?? __('閱讀更多') }}
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

## Assets 目錄

### CSS

**assets/css/style.css：**

```css
/* 主題變數 */
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

/* 組件 */
.card {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 20px;
}
```

### JavaScript

**assets/js/app.js：**

```javascript
// 主題 JavaScript
;(function ($) {
  'use strict'

  // 行動選單切換
  $('.menu-toggle').on('click', function () {
    $('.main-menu').toggleClass('active')
  })

  // 平滑捲動
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

## 語言檔案

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
    'home' => '首頁',
    'blog' => '部落格',
    'about' => '關於',
    'contact' => '聯絡',
    'read_more' => '閱讀更多',
    'latest_posts' => '最新文章',
];
```

**在 Blade 中使用：**

```blade
{{ __("$themeId::word.read_more") }}
```

## 主題載入過程

### 1. 偵測

ThemeServiceProvider 從網站設定中偵測啟用的主題：

```php
$themeId = $website->theme ?: 'default';
```

### 2. 載入配置

```php
// 載入 config.php
$config = include public_path("themes/{$themeId}/config.php");
config(["theme.{$themeId}" => $config]);
```

### 當 `/public/themes` 遺失時的核心主題回退

如果 `public/themes/{themeId}` 不存在，且 `{themeId}` 是核心主題，WNCMS 會回退到以下內建主題目錄：

```text
{WNCMS_RESOURCES_PATH}/themes/{themeId}
```

這個回退會同時套用到設定、視圖、翻譯與 `functions.php`。
如果目前啟用的主題不是核心主題且公開目錄遺失，系統仍維持「主題未啟用」行為。

### 3. 註冊視圖

```php
// 使用命名空間註冊主題視圖
$this->loadViewsFrom(
    public_path("themes/{$themeId}/views"),
    $themeId
);
```

### 4. 載入翻譯

```php
// 載入主題翻譯
$this->loadTranslationsFrom(
    public_path("themes/{$themeId}/lang"),
    $themeId
);
```

### 5. 載入函數

```php
// 執行 functions.php
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}
```

### 6. 主題生命週期 Hooks

ThemeServiceProvider 現在會派發生命週期 hooks，讓主題/插件開發者不需覆寫 provider 即可擴充載入行為：

- `wncms.frontend.themes.boot.before`
- `wncms.frontend.themes.load.before`
- `wncms.frontend.themes.load.after`
- `wncms.frontend.themes.boot.after`

`load.before` 對 `$themeId` 與 `$themePath` 使用引用參數，監聽器可在執行期調整解析結果。
完整參數說明請見 [Themes 事件](../event/themes.md)。

## 輔助函數

### 主題資源載入

```blade
{{-- CSS --}}
<link rel="stylesheet" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}">

{{-- JavaScript --}}
<script src="{{ wncms()->theme()->asset($themeId, 'js/app.js') }}"></script>

{{-- Images --}}
<img src="{{ wncms()->theme()->asset($themeId, 'images/logo.png') }}">
```

### 主題視圖載入

```blade
{{-- 包含主題視圖 --}}
@include(wncms()->theme()->view($themeId, 'parts.header'))

{{-- 擴充主題版面 --}}
@extends("$themeId::layouts.app")
```

### 主題選項

```blade
{{-- 取得主題選項 --}}
{{ gto('site_logo') }}

{{-- 取得主題選項（含預設值）--}}
{{ gto('site_slogan', 'Welcome to WNCMS') }}

{{-- 取得主題翻譯 --}}
{{ __("$themeId::word.read_more") }}
```

## 最佳實踐

### 1. 安全性

PHP 檔案中始終包含安全防護：

```php
<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}
```

### 2. 命名空間使用

為視圖和翻譯使用主題命名空間：

```blade
{{-- 正確 --}}
@extends("$themeId::layouts.app")
{{ __("$themeId::word.home") }}

{{-- 避免硬編碼 --}}
@extends("starter::layouts.app")
```

### 3. 資源優化

- 為正式環境壓縮 CSS 和 JavaScript
- 優化圖片（建議使用 WebP 格式）
- 為圖片使用延遲載入
- 盡可能合併 CSS/JS 檔案

### 4. 響應式設計

確保行動優先方法：

```css
/* 行動優先 */
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

### 5. 主題選項

為可自訂元素使用主題選項：

```blade
{{-- 好：可透過後台自訂 --}}
<div style="background-color: {{ gto('primary_color', '#007bff') }}">

{{-- 避免：硬編碼值 --}}
<div style="background-color: #007bff">
```

## 範本階層

WNCMS 遵循以下範本解析順序：

1. **自訂別名視圖**：`{themeId}::pages.{slug}`
2. **範本頁面**：`{themeId}::pages.templates.{templateId}`
3. **純頁面**：`{themeId}::pages.show`
4. **回退**：重導向至首頁

範例：

```php
// 1. 檢查自訂別名視圖
if (view()->exists("{$themeId}::pages.about-us")) {
    return view("{$themeId}::pages.about-us");
}

// 2. 檢查範本
if ($page->type === 'template') {
    return view("{$themeId}::pages.templates.{$templateId}", compact('page'));
}

// 3. 使用預設顯示頁面
return view("{$themeId}::pages.show", compact('page'));
```

## 疑難排解

### 主題未載入

1. 檢查主題資料夾是否存在於 `/public/themes/{themeId}`
2. 驗證 `config.php` 存在且有效
3. 檢查檔案權限（目錄 755，檔案 644）
4. 清除快取：`php artisan cache:clear`

### 找不到視圖

1. 確保視圖在 `/views/` 子目錄中
2. 檢查視圖命名空間與主題 ID 匹配
3. 使用正確的視圖路徑語法：`{themeId}::path.to.view`

### 資源未載入

1. 驗證資源在 `/assets/` 子目錄中
2. 檢查資源路徑：`wncms()->theme()->asset($themeId, 'path/to/asset')`
3. 確保公用磁碟可存取
4. 清除瀏覽器快取

### 翻譯無效

1. 檢查語言檔案是否存在於 `/lang/{locale}/`
2. 使用正確的翻譯語法：`__("{themeId}::word.key")`
3. 清除翻譯快取：`php artisan config:clear`

## 另請參閱

- [主題配置](./config.md)
- [主題分頁](./pagination.md)
- [主題選單](./menu.md)
- [Frontend Controller](../controller/frontend-controller.md)
