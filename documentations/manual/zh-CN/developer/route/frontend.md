# 前台路由

## 概述

`frontend.php` 档案定义了 WNCMS 的所有公开路由。这些路由处理使用者可存取的内容，包括页面、文章、使用者个人资料、网站地图和其他前台功能。

## 档案位置

```
wncms-core/routes/frontend.php
```

## 路由结构

### 主路由群组

所有前台路由都包装在一个命名群组中：

```php
Route::name('frontend.')
    ->middleware(['is_installed', 'has_website', 'full_page_cache'])
    ->group(function () {
        // Frontend routes
    });
```

**中介层说明：**

- **is_installed**: 确保 WNCMS 已安装
- **has_website**: 验证网站是否存在
- **full_page_cache**: 启用全页快取以提高效能

**路由命名：**

所有路由都以 `frontend.` 为前缀：

```
frontend.pages.home
frontend.posts.show
frontend.users.login
```

## 核心路由

### 首页

```php
Route::get('/', [PageController::class, 'home'])->name('pages.home');
```

首页路由，通常渲染主题的 home.blade.php 模板。

### 部落格列表

```php
Route::get('blog', [PageController::class, 'blog'])->name('pages.blog');
```

显示包含分页文章的部落格索引页面。

## 页面

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

`show` 方法处理：

- 模板页面
- 纯页面
- 自订 slug 视图
- 后备重定向

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

**文章路由范例：**

```
GET  /post                          → 所有文章
GET  /post/search/laravel           → 搜寻结果
POST /post/search                   → 搜寻表单
GET  /post/rank                     → 文章排名
GET  /post/rank/week                → 每周排名
GET  /post/category/laravel         → 依分类的文章
GET  /post/tag/php                  → 依标签的文章
GET  /post/list/hot/week            → 本周热门文章
GET  /post/list/new                 → 最新文章
GET  /post/my-article               → 单一文章
```

### 标签路由

动态标签类型路由：

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model))
    ->name('posts.tag');
```

**支援的标签类型：**

- `category`
- `tag`
- `keyword`
- 设定档中定义的自订类型

**范例：**

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

**列表类型：**

- `hot`: 热门文章
- `new`: 最新文章
- `like`: 最多赞
- `fav`: 最多收藏

**时间期间：**

- `today`: 今天的文章
- `yesterday`: 昨天的文章
- `week`: 本周
- `month`: 本月

## 连结

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
GET /link                    → 所有连结
GET /link/123                → 单一连结
GET /link/category/tools     → 依分类的连结
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
GET  /user/login                    → 登入页面
POST /user/login/submit             → 登入表单
GET  /user/register                 → 注册页面
POST /user/register/submit          → 注册表单
GET  /user/profile                  → 使用者个人资料（需验证）
POST /user/profile/update           → 更新个人资料（需验证）
```

## 留言

```php
Route::prefix('comments')->name('comments.')->controller(CommentController::class)->group(function () {
    Route::post('store', 'store')->name('store');
    Route::post('{id}/like', 'like')->name('like');
    Route::post('{id}/report', 'report')->name('report');
});
```

## 点击（分析）

```php
Route::prefix('clicks')->name('clicks.')->controller(ClickController::class)->group(function () {
    Route::post('record', 'record')->name('record');
});
```

用于追踪文章、连结和其他内容的点击。

## 网站地图

```php
Route::get('sitemap/posts', [SitemapController::class, 'posts'])->name('sitemaps.posts');
Route::get('sitemap/pages', [SitemapController::class, 'pages'])->name('sitemaps.pages');
Route::get('sitemap/tags/{model}/{type}', [SitemapController::class, 'tags'])->name('sitemaps.tags');
```

**网站地图路由：**

```
GET /sitemap/posts                      → 文章网站地图
GET /sitemap/pages                      → 页面网站地图
GET /sitemap/tags/post/category         → 分类标签网站地图
```

## 全页快取

前台路由使用全页快取中介层以提高效能：

```php
Route::middleware('full_page_cache')->group(function () {
    // 快取的路由
});
```

### 快取行为

- **快取**: 静态页面、文章列表、单一文章
- **绕过**: 使用者特定页面（个人资料、设定）
- **TTL**: 可在设定中配置

### 清除快取

```php
// 清除特定页面
wncms()->cache()->forget('page:about-us');

// 清除所有前台快取
wncms()->cache()->flushTag('frontend');
```

## 路由约束

### Slug 验证

```php
Route::get('{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');
```

### 期间验证

```php
Route::get('rank/{period}', [PostController::class, 'rank'])
    ->where('period', 'today|yesterday|week|month');
```

### 标签类型验证

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model));
```

这会从配置的标签类型动态产生正则表达式。

## SEO 友善 URL

### 文章 URL

```
/post/my-article-title
/post/category/technology
/post/tag/laravel
```

### 页面 URL

```
/page/about-us
/page/contact
/page/privacy-policy
```

### 使用者 URL

```
/user/john/posts
```

## 产生 URL

### 在控制器中

```php
// 重定向到首页
return redirect()->route('frontend.pages.home');

// 重定向到文章
return redirect()->route('frontend.posts.show', ['slug' => $post->slug]);

// 重定向到标签归档
return redirect()->route('frontend.posts.tag', [
    'type' => 'category',
    'slug' => $tag->slug,
]);
```

### 在视图中

```blade
{{-- 首页连结 --}}
<a href="{{ route('frontend.pages.home') }}">Home</a>

{{-- 文章连结 --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">
    {{ $post->title }}
</a>

{{-- 标签连结 --}}
<a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $tag->slug]) }}">
    {{ $tag->name }}
</a>

{{-- 搜寻表单 --}}
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword">
    <button type="submit">Search</button>
</form>
```

## 身份验证检查

某些路由需要身份验证：

```php
Route::middleware('auth')->group(function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('post/create', [PostController::class, 'create']);
});
```

**未验证时重定向：**

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

## 搜寻功能

### POST 搜寻

```php
Route::post('search', [PostController::class, 'search'])->name('search');
```

**表单：**

```blade
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword" placeholder="Search...">
    <button type="submit">Search</button>
</form>
```

### GET 搜寻结果

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
    // 处理成功
  },
})
```

## 最佳实践

### 1. 使用命名路由

```blade
{{-- 好的 --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">{{ $post->title }}</a>

{{-- 避免 --}}
<a href="/post/{{ $post->slug }}">{{ $post->title }}</a>
```

### 2. 快取静态内容

为静态页面启用全页快取：

```php
Route::middleware('full_page_cache')->get('about', [PageController::class, 'about']);
```

### 3. 验证使用者输入

```php
public function search(Request $request)
{
    $request->validate([
        'keyword' => 'required|string|min:2|max:100',
    ]);

    // 搜寻逻辑
}
```

### 4. 使用路由模型绑定

```php
Route::get('post/{post}', [PostController::class, 'show']);

// 在控制器中
public function show(Post $post)
{
    // $post 已自动载入
}
```

### 5. 优雅地处理 404

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

## 另见

- [Backend Routes](./backend.md) - 管理路由
- [API Routes](./api.md) - API 端点
- [Frontend Controller](../controller/frontend-controller.md)
- [Add Routes](./add-routes.md) - 建立自订路由
