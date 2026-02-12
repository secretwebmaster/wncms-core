# Web 路由

## 概述

`web.php` 路由檔案作為 WNCMS 中所有 web 路由的主要入口點。它充當一個中央樞紐，包含其他路由檔案（身份驗證、安裝、後台和前台），並配置全域中介層，如本地化。

## 檔案位置

```
wncms-core/routes/web.php
```

## 路由結構

### 主路由群組

所有路由都包裝在處理本地化的主群組中：

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

- **條件前綴**: 如果啟用翻譯，則新增語言環境前綴（例如 `/en`、`/zh_TW`）
- **本地化中介層**:
  - `localeSessionRedirect`: 根據 session 語言環境重定向
  - `localizationRedirect`: 處理語言環境 URL 重定向
  - `localeViewPath`: 根據語言環境設定視圖路徑
- **全域設定**: 使用 `gss('enable_translation')` 檢查是否啟用多語言

### 包含的路由檔案

web.php 檔案包含四個獨立的路由檔案：

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

### 後備路由

後備路由捕獲所有未定義的 URL：

```php
Route::fallback([PageController::class, 'fallback']);
```

這允許：

- 自訂 404 頁面
- 動態頁面路由
- 主題特定的後備視圖

## 本地化系統

### URL 結構

當 `enable_translation` 啟用時，所有路由都以語言環境為前綴：

```
# 英文
https://example.com/en/
https://example.com/en/blog
https://example.com/en/post/my-article

# 繁體中文
https://example.com/zh_TW/
https://example.com/zh_TW/blog
https://example.com/zh_TW/post/my-article
```

### 語言環境檢測

WNCMS 使用 `secretwebmaster/laravel-localization` 套件進行語言環境處理：

1. **URL 語言環境**: 首先檢查 URL 前綴
2. **Session 語言環境**: 後退到 session 儲存的語言環境
3. **瀏覽器語言環境**: 使用 Accept-Language 標頭
4. **預設語言環境**: 使用應用程式的預設語言環境

### 中介層說明

**localeSessionRedirect:**

- 將使用者選擇的語言環境儲存在 session 中
- 如果 URL 語言環境不同，則重定向到 session 語言環境

**localizationRedirect:**

- 如果缺少或無效，則重定向到正確的語言環境 URL
- 處理語言環境切換

**localeViewPath:**

- 根據語言環境設定視圖命名空間
- 允許語言環境特定的視圖

## RouteServiceProvider 整合

RouteServiceProvider 引導 web 路由：

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

**要點：**

- **web 中介層群組**: 應用 session、CSRF 保護、cookie 加密
- **命名空間**: 自動為控制器命名空間新增前綴
- **群組**: 載入 web.php 路由

## 全域設定

### enable_translation

控制是否啟用多語言支援：

```php
// 檢查翻譯是否啟用
if (gss('enable_translation')) {
    // 翻譯邏輯
}
```

在後台管理：**設定 → 一般 → 多語言**

### 支援的語言環境

在 `config/wncms.php` 中配置：

```php
'locales' => [
    'en' => 'English',
    'zh_TW' => '繁體中文',
    'zh_CN' => '简体中文',
    'ja' => '日本語',
],
```

## 使用本地化路由

### 產生 URL

使用 Laravel 的 `route()` 輔助函式並注意語言環境：

```php
// 當前語言環境 URL
route('frontend.posts.show', ['slug' => 'my-post']);
// 輸出：/en/post/my-post（如果當前語言環境是 'en'）

// 特定語言環境 URL
route('frontend.posts.show', ['slug' => 'my-post', 'locale' => 'zh_TW']);
// 輸出：/zh_TW/post/my-post
```

### 切換語言環境

產生語言環境切換器連結：

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
// 在本地化群組之外
Route::post('webhook/payment', [WebhookController::class, 'payment']);
```

## 路由快取

為了生產環境效能，快取路由：

```bash
# 產生路由快取
php artisan route:cache

# 清除路由快取
php artisan route:clear
```

**重要提示：** 路由快取不適用於閉包。始終使用控制器參考。

## 最佳實踐

### 1. 使用命名路由

始終為路由命名以便於參考：

```php
// 好的
Route::get('about', [PageController::class, 'about'])->name('pages.about');

// 在視圖中
<a href="{{ route('pages.about') }}">About</a>
```

### 2. 分組相關路由

使用路由群組進行組織：

```php
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{slug}', [BlogController::class, 'show'])->name('show');
});
```

### 3. 適當應用中介層

```php
// 需要身份驗證
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
});

// 僅訪客
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin']);
});
```

### 4. 分離關注點

保持路由檔案專注：

- `web.php`: 主入口點，僅包含
- `auth.php`: 身份驗證路由
- `backend.php`: 管理面板路由
- `frontend.php`: 公開路由
- `api.php`: API 路由

### 5. 使用路由模型綁定

讓 Laravel 自動解析模型：

```php
Route::get('posts/{post}', [PostController::class, 'show']);

// 在控制器中
public function show(Post $post)
{
    // $post 已載入
}
```

## 除錯路由

### 列出所有路由

```bash
# 顯示所有註冊的路由
php artisan route:list

# 按名稱過濾
php artisan route:list --name=frontend

# 按方法過濾
php artisan route:list --method=GET
```

### 檢查路由註冊

驗證路由是否存在：

```php
// 在控制器或視圖中
$route = Route::getRoutes()->getByName('frontend.posts.show');
if ($route) {
    // 路由存在
}
```

### 除錯語言環境問題

檢查當前語言環境：

```php
app()->getLocale(); // 當前語言環境
LaravelLocalization::getCurrentLocale(); // URL 語言環境
session('locale'); // Session 語言環境
```

## 常見問題

### 本地化路由上出現 404

**問題：** 沒有語言環境的路由可以運作，但帶有語言環境前綴的返回 404。

**解決方案：**

1. 檢查 `enable_translation` 設定是否為 true
2. 驗證語言環境在 `config/wncms.php` 支援的語言環境中
3. 清除路由快取：`php artisan route:clear`

### 中介層衝突

**問題：** 自訂中介層與本地化衝突。

**解決方案：** 正確排序中介層：

```php
Route::middleware(['localeSessionRedirect', 'custom'])->group(function () {
    // Routes
});
```

### 找不到路由

**問題：** 命名路由不存在。

**解決方案：**

1. 檢查路由是否已註冊：`php artisan route:list`
2. 驗證路由檔案是否包含在 web.php 中
3. 清除快取：`php artisan route:clear`

## 安全考量

### CSRF 保護

web.php 中的所有 POST/PUT/PATCH/DELETE 路由自動具有 CSRF 保護：

```blade
<form method="POST" action="{{ route('posts.store') }}">
    @csrf
    <!-- Form fields -->
</form>
```

### 速率限制

應用速率限制以防止濫用：

```php
Route::middleware('throttle:60,1')->group(function () {
    // 每分鐘 60 個請求
});
```

### 路由授權

使用中介層進行存取控制：

```php
Route::middleware(['auth', 'can:view-admin'])->group(function () {
    // 管理路由
});
```

## 測試路由

### 功能測試

測試路由回應：

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

### 測試路由註冊

```php
public function test_route_exists()
{
    $this->assertTrue(Route::has('frontend.posts.show'));
}
```

## 另見

- [Backend Routes](./backend.md) - 管理面板路由
- [Frontend Routes](./frontend.md) - 公開路由
- [API Routes](./api.md) - API 端點
- [Add Routes](./add-routes.md) - 建立自訂路由
