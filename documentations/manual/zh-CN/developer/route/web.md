# Web 路由

## 概述

`web.php` 路由档案作为 WNCMS 中所有 web 路由的主要入口点。它充当一个中央枢纽，包含其他路由档案（身份验证、安装、后台和前台），并配置全域中介层，如本地化。

## 档案位置

```
wncms-core/routes/web.php
```

## 路由结构

### 主路由群组

所有路由都包装在处理本地化的主群组中：

```php
Route::group([
    'prefix' => gss('enable_translation', true) ? LaravelLocalization::setLocale() : null,
    'middleware' => gss('enable_translation', true)
        ? ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
        : [],
], function () {
    // Route includes here
});
```

**主要功能：**

- **条件前缀**: 如果启用翻译，则新增语言环境前缀（例如 `/en`、`/zh_TW`）
- **本地化中介层**:
  - `localeSessionRedirect`: 根据 session 语言环境重定向
  - `localizationRedirect`: 处理语言环境 URL 重定向
  - `localeViewPath`: 根据语言环境设定视图路径
- **全域设定**: 使用 `gss('enable_translation')` 检查是否启用多语言

### 包含的路由档案

web.php 档案包含四个独立的路由档案：

```php
// Installation routes
require __DIR__ . '/install.php';

// Authentication routes
require __DIR__ . '/auth.php';

// Backend admin panel routes
require __DIR__ . '/backend.php';

// Frontend public routes
require __DIR__ . '/frontend.php';
```

### 后备路由

后备路由捕获所有未定义的 URL：

```php
Route::fallback([PageController::class, 'fallback']);
```

这允许：

- 自订 404 页面
- 动态页面路由
- 主题特定的后备视图

## 本地化系统

### URL 结构

当 `enable_translation` 启用时，所有路由都以语言环境为前缀：

```
# 英文
https://example.com/en/
https://example.com/en/blog
https://example.com/en/post/my-article

# 繁体中文
https://example.com/zh_TW/
https://example.com/zh_TW/blog
https://example.com/zh_TW/post/my-article
```

### 语言环境检测

WNCMS 使用 `secretwebmaster/laravel-localization` 套件进行语言环境处理：

1. **URL 语言环境**: 首先检查 URL 前缀
2. **Session 语言环境**: 后退到 session 储存的语言环境
3. **浏览器语言环境**: 使用 Accept-Language 标头
4. **预设语言环境**: 使用应用程式的预设语言环境

### 中介层说明

**localeSessionRedirect:**

- 将使用者选择的语言环境储存在 session 中
- 如果 URL 语言环境不同，则重定向到 session 语言环境

**localizationRedirect:**

- 如果缺少或无效，则重定向到正确的语言环境 URL
- 处理语言环境切换

**localeViewPath:**

- 根据语言环境设定视图命名空间
- 允许语言环境特定的视图

## RouteServiceProvider 整合

RouteServiceProvider 引导 web 路由：

```php
public function boot()
{
    $this->routes(function () {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(__DIR__ . '/../../routes/web.php');
    });
}
```

**要点：**

- **web 中介层群组**: 应用 session、CSRF 保护、cookie 加密
- **命名空间**: 自动为控制器命名空间新增前缀
- **群组**: 载入 web.php 路由

## 全域设定

### enable_translation

控制是否启用多语言支援：

```php
// 检查翻译是否启用
if (gss('enable_translation')) {
    // 翻译逻辑
}
```

在后台管理：**设定 → 一般 → 多语言**

### 支援的语言环境

在 `config/wncms.php` 中配置：

```php
'locales' => [
    'en' => 'English',
    'zh_TW' => '繁体中文',
    'zh_CN' => '简体中文',
    'ja' => '日本语',
],
```

## 使用本地化路由

### 产生 URL

使用 Laravel 的 `route()` 辅助函式并注意语言环境：

```php
// 当前语言环境 URL
route('frontend.posts.show', ['slug' => 'my-post']);
// 输出：/en/post/my-post（如果当前语言环境是 'en'）

// 特定语言环境 URL
route('frontend.posts.show', ['slug' => 'my-post', 'locale' => 'zh_TW']);
// 输出：/zh_TW/post/my-post
```

### 切换语言环境

产生语言环境切换器连结：

```blade
@foreach(config('wncms.locales') as $locale => $name)
    <a href="{{ LaravelLocalization::getLocalizedURL($locale) }}">
        {{ $name }}
    </a>
@endforeach
```

### 非本地化路由

某些路由不需要本地化（例如，API、webhook）：

```php
// 在本地化群组之外
Route::post('webhook/payment', [WebhookController::class, 'payment']);
```

## 路由快取

为了生产环境效能，快取路由：

```bash
# 产生路由快取
php artisan route:cache

# 清除路由快取
php artisan route:clear
```

**重要提示：** 路由快取不适用于闭包。始终使用控制器参考。

## 最佳实践

### 1. 使用命名路由

始终为路由命名以便于参考：

```php
// 好的
Route::get('about', [PageController::class, 'about'])->name('pages.about');

// 在视图中
<a href="{{ route('pages.about') }}">About</a>
```

### 2. 分组相关路由

使用路由群组进行组织：

```php
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{slug}', [BlogController::class, 'show'])->name('show');
});
```

### 3. 适当应用中介层

```php
// 需要身份验证
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
});

// 仅访客
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin']);
});
```

### 4. 分离关注点

保持路由档案专注：

- `web.php`: 主入口点，仅包含
- `auth.php`: 身份验证路由
- `backend.php`: 管理面板路由
- `frontend.php`: 公开路由
- `api.php`: API 路由

### 5. 使用路由模型绑定

让 Laravel 自动解析模型：

```php
Route::get('posts/{post}', [PostController::class, 'show']);

// 在控制器中
public function show(Post $post)
{
    // $post 已载入
}
```

## 除错路由

### 列出所有路由

```bash
# 显示所有注册的路由
php artisan route:list

# 按名称过滤
php artisan route:list --name=frontend

# 按方法过滤
php artisan route:list --method=GET
```

### 检查路由注册

验证路由是否存在：

```php
// 在控制器或视图中
$route = Route::getRoutes()->getByName('frontend.posts.show');
if ($route) {
    // 路由存在
}
```

### 除错语言环境问题

检查当前语言环境：

```php
app()->getLocale(); // 当前语言环境
LaravelLocalization::getCurrentLocale(); // URL 语言环境
session('locale'); // Session 语言环境
```

## 常见问题

### 本地化路由上出现 404

**问题：** 没有语言环境的路由可以运作，但带有语言环境前缀的返回 404。

**解决方案：**

1. 检查 `enable_translation` 设定是否为 true
2. 验证语言环境在 `config/wncms.php` 支援的语言环境中
3. 清除路由快取：`php artisan route:clear`

### 中介层冲突

**问题：** 自订中介层与本地化冲突。

**解决方案：** 正确排序中介层：

```php
Route::middleware(['localeSessionRedirect', 'custom'])->group(function () {
    // Routes
});
```

### 找不到路由

**问题：** 命名路由不存在。

**解决方案：**

1. 检查路由是否已注册：`php artisan route:list`
2. 验证路由档案是否包含在 web.php 中
3. 清除快取：`php artisan route:clear`

## 安全考量

### CSRF 保护

web.php 中的所有 POST/PUT/PATCH/DELETE 路由自动具有 CSRF 保护：

```blade
<form method="POST" action="{{ route('posts.store') }}">
    @csrf
    <!-- Form fields -->
</form>
```

### 速率限制

应用速率限制以防止滥用：

```php
Route::middleware('throttle:60,1')->group(function () {
    // 每分钟 60 个请求
});
```

### 路由授权

使用中介层进行存取控制：

```php
Route::middleware(['auth', 'can:view-admin'])->group(function () {
    // 管理路由
});
```

## 测试路由

### 功能测试

测试路由回应：

```php
public function test_home_page_loads()
{
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('frontend.pages.home');
}

public function test_localized_route()
{
    $response = $this->get('/en/blog');

    $response->assertStatus(200);
}
```

### 测试路由注册

```php
public function test_route_exists()
{
    $this->assertTrue(Route::has('frontend.posts.show'));
}
```

## 另见

- [Backend Routes](./backend.md) - 管理面板路由
- [Frontend Routes](./frontend.md) - 公开路由
- [API Routes](./api.md) - API 端点
- [Add Routes](./add-routes.md) - 建立自订路由
