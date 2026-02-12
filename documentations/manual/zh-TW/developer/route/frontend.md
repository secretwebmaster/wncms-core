# 前台路由

## 概述

`frontend.php` 檔案定義了 WNCMS 的所有公開路由。這些路由處理使用者可存取的內容，包括頁面、文章、使用者個人資料、網站地圖和其他前台功能。

## 檔案位置

```
wncms-core/routes/frontend.php
```

## 路由結構

### 主路由群組

所有前台路由都包裝在一個命名群組中：

```php
Route::name('frontend.')
    ->middleware(['is_installed', 'has_website', 'full_page_cache'])
    ->group(function () {
        // Frontend routes
    });
```

**中介層說明：**

- **is_installed**: 確保 WNCMS 已安裝
- **has_website**: 驗證網站是否存在
- **full_page_cache**: 啟用全頁快取以提高效能

**路由命名：**

所有路由都以 `frontend.` 為前綴：

```
frontend.pages.home
frontend.posts.show
frontend.users.login
```

## 核心路由

### 首頁

```php
Route::get('/', [PageController::class, 'home'])->name('pages.home');
```

首頁路由，通常渲染主題的 home.blade.php 模板。

### 部落格列表

```php
Route::get('blog', [PageController::class, 'blog'])->name('pages.blog');
```

顯示包含分頁文章的部落格索引頁面。

## 頁面

```php
Route::prefix('page')->name('pages.')->controller(PageController::class)->group(function () {
    Route::get('{slug}', 'show')->name('show');
});
```

**使用方式：**

```
https://example.com/page/about-us
https://example.com/page/contact
https://example.com/page/privacy-policy
```

`show` 方法處理：

- 模板頁面
- 純頁面
- 自訂 slug 視圖
- 後備重定向

## 文章

```php
Route::prefix('post')->name('posts.')->controller(PostController::class)->group(function () {
    // Post listing
    Route::get('/', 'index')->name('index');

    // Search
    Route::post('search', 'search')->name('search');
    Route::get('search/{keyword}', 'result')->name('search.result');

    // Rankings
    Route::get('rank', 'rank')->name('rank');
    Route::get('rank/{period}', 'rank')->name('rank.period');

    // Tag archives
    Route::get('{type}/{slug}', 'tag')
        ->where('type', wncms()->tag()->getTagTypesForRoute(wncms()->getModelClass('post')))
        ->name('tag');

    // Post lists (hot, new, liked, favorites)
    Route::get('list/{name?}/{period?}', 'post_list')
        ->where('name', 'hot|new|like|fav')
        ->where('period', 'today|yesterday|week|month')
        ->name('list');

    // Auth-required post routes
    Route::middleware('auth')->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('edit/{post}', 'edit')->name('edit');
        Route::post('update/{post}', 'update')->name('update');
    });

    // Single post (must be last)
    Route::get('{slug}', 'show')->name('show');
});
```

**文章路由範例：**

```
GET  /post                          → 所有文章
GET  /post/search/laravel           → 搜尋結果
POST /post/search                   → 搜尋表單
GET  /post/rank                     → 文章排名
GET  /post/rank/week                → 每週排名
GET  /post/category/laravel         → 依分類的文章
GET  /post/tag/php                  → 依標籤的文章
GET  /post/list/hot/week            → 本週熱門文章
GET  /post/list/new                 → 最新文章
GET  /post/my-article               → 單一文章
```

### 標籤路由

動態標籤類型路由：

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model))
    ->name('posts.tag');
```

**支援的標籤類型：**

- `category`
- `tag`
- `keyword`
- 設定檔中定義的自訂類型

**範例：**

```
/post/category/technology
/post/tag/laravel
/post/keyword/tutorial
```

### 文章列表

```php
Route::get('list/{name?}/{period?}', 'post_list')
    ->where('name', 'hot|new|like|fav')
    ->where('period', 'today|yesterday|week|month')
    ->name('list');
```

**列表類型：**

- `hot`: 熱門文章
- `new`: 最新文章
- `like`: 最多讚
- `fav`: 最多收藏

**時間期間：**

- `today`: 今天的文章
- `yesterday`: 昨天的文章
- `week`: 本週
- `month`: 本月

## 連結

```php
Route::prefix('link')->name('links.')->controller(LinkController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{id}', 'show')->name('show');
    Route::get('{type}/{slug}', 'tag')
        ->where('type', wncms()->tag()->getTagTypesForRoute(wncms()->getModelClass('link')))
        ->name('tag');
});
```

**使用方式：**

```
GET /link                    → 所有連結
GET /link/123                → 單一連結
GET /link/category/tools     → 依分類的連結
```

## 使用者

```php
Route::prefix('user')->name('users.')->controller(UserController::class)->group(function () {
    // Public profile
    Route::get('{username}/posts', 'posts')->name('posts');

    // Authentication pages
    Route::get('/login', 'show_login')->name('login');
    Route::post('/login/submit', 'login')->name('login.submit');
    Route::post('/login/ajax', 'login_ajax')->name('login.ajax');

    Route::get('/register', 'show_register')->name('register');
    Route::post('/register/submit', 'register')->name('register.submit');

    Route::get('/password/forgot', 'show_password_forgot')->name('password.forgot');
    Route::post('/password/forgot/submit', 'password_forgot')->name('password.forgot.submit');

    // Profile management (auth required)
    Route::middleware('auth')->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile/update', 'profile_update')->name('profile.update');
        Route::get('/settings', 'settings')->name('settings');
        Route::post('/settings/update', 'settings_update')->name('settings.update');
    });
});
```

**使用者路由：**

```
GET  /user/john/posts               → 使用者的文章
GET  /user/login                    → 登入頁面
POST /user/login/submit             → 登入表單
GET  /user/register                 → 註冊頁面
POST /user/register/submit          → 註冊表單
GET  /user/profile                  → 使用者個人資料（需驗證）
POST /user/profile/update           → 更新個人資料（需驗證）
```

## 留言

```php
Route::prefix('comments')->name('comments.')->controller(CommentController::class)->group(function () {
    Route::post('store', 'store')->name('store');
    Route::post('{id}/like', 'like')->name('like');
    Route::post('{id}/report', 'report')->name('report');
});
```

## 點擊（分析）

```php
Route::prefix('clicks')->name('clicks.')->controller(ClickController::class)->group(function () {
    Route::post('record', 'record')->name('record');
});
```

用於追蹤文章、連結和其他內容的點擊。

## 網站地圖

```php
Route::get('sitemap/posts', [SitemapController::class, 'posts'])->name('sitemaps.posts');
Route::get('sitemap/pages', [SitemapController::class, 'pages'])->name('sitemaps.pages');
Route::get('sitemap/tags/{model}/{type}', [SitemapController::class, 'tags'])->name('sitemaps.tags');
```

**網站地圖路由：**

```
GET /sitemap/posts                      → 文章網站地圖
GET /sitemap/pages                      → 頁面網站地圖
GET /sitemap/tags/post/category         → 分類標籤網站地圖
```

## 全頁快取

前台路由使用全頁快取中介層以提高效能：

```php
Route::middleware('full_page_cache')->group(function () {
    // 快取的路由
});
```

### 快取行為

- **快取**: 靜態頁面、文章列表、單一文章
- **繞過**: 使用者特定頁面（個人資料、設定）
- **TTL**: 可在設定中配置

### 清除快取

```php
// 清除特定頁面
wncms()->cache()->forget('page:about-us');

// 清除所有前台快取
wncms()->cache()->flushTag('frontend');
```

## 路由約束

### Slug 驗證

```php
Route::get('{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');
```

### 期間驗證

```php
Route::get('rank/{period}', [PostController::class, 'rank'])
    ->where('period', 'today|yesterday|week|month');
```

### 標籤類型驗證

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model));
```

這會從配置的標籤類型動態產生正則表達式。

## SEO 友善 URL

### 文章 URL

```
/post/my-article-title
/post/category/technology
/post/tag/laravel
```

### 頁面 URL

```
/page/about-us
/page/contact
/page/privacy-policy
```

### 使用者 URL

```
/user/john/posts
```

## 產生 URL

### 在控制器中

```php
// 重定向到首頁
return redirect()->route('frontend.pages.home');

// 重定向到文章
return redirect()->route('frontend.posts.show', ['slug' => $post->slug]);

// 重定向到標籤歸檔
return redirect()->route('frontend.posts.tag', [
    'type' => 'category',
    'slug' => $tag->slug,
]);
```

### 在視圖中

```blade
{{-- 首頁連結 --}}
<a href="{{ route('frontend.pages.home') }}">Home</a>

{{-- 文章連結 --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">
    {{ $post->title }}
</a>

{{-- 標籤連結 --}}
<a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $tag->slug]) }}">
    {{ $tag->name }}
</a>

{{-- 搜尋表單 --}}
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword">
    <button type="submit">Search</button>
</form>
```

## 身份驗證檢查

某些路由需要身份驗證：

```php
Route::middleware('auth')->group(function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('post/create', [PostController::class, 'create']);
});
```

**未驗證時重定向：**

```php
// 在控制器中
public function create()
{
    if (!auth()->check()) {
        return redirect()->route('frontend.users.login');
    }

    return view('frontend.posts.create');
}
```

## 搜尋功能

### POST 搜尋

```php
Route::post('search', [PostController::class, 'search'])->name('search');
```

**表單：**

```blade
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword" placeholder="Search...">
    <button type="submit">Search</button>
</form>
```

### GET 搜尋結果

```php
Route::get('search/{keyword}', [PostController::class, 'result'])->name('search.result');
```

**URL：**

```
/post/search/laravel
/post/search/php+tutorial
```

## AJAX 路由

### AJAX 登入

```php
Route::post('/login/ajax', [UserController::class, 'login_ajax'])->name('login.ajax');
```

**使用方式：**

```javascript
$.ajax({
  url: '{{ route("frontend.users.login.ajax") }}',
  method: 'POST',
  data: {
    email: email,
    password: password,
    _token: '{{ csrf_token() }}',
  },
  success: function (response) {
    // 處理成功
  },
})
```

## 最佳實踐

### 1. 使用命名路由

```blade
{{-- 好的 --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">{{ $post->title }}</a>

{{-- 避免 --}}
<a href="/post/{{ $post->slug }}">{{ $post->title }}</a>
```

### 2. 快取靜態內容

為靜態頁面啟用全頁快取：

```php
Route::middleware('full_page_cache')->get('about', [PageController::class, 'about']);
```

### 3. 驗證使用者輸入

```php
public function search(Request $request)
{
    $request->validate([
        'keyword' => 'required|string|min:2|max:100',
    ]);

    // 搜尋邏輯
}
```

### 4. 使用路由模型綁定

```php
Route::get('post/{post}', [PostController::class, 'show']);

// 在控制器中
public function show(Post $post)
{
    // $post 已自動載入
}
```

### 5. 優雅地處理 404

```php
public function show($slug)
{
    $post = Post::where('slug', $slug)->first();

    if (!$post) {
        abort(404, 'Post not found');
    }

    return view('frontend.posts.show', compact('post'));
}
```

## 另見

- [Backend Routes](./backend.md) - 管理路由
- [API Routes](./api.md) - API 端點
- [Frontend Controller](../controller/frontend-controller.md)
- [Add Routes](./add-routes.md) - 建立自訂路由
