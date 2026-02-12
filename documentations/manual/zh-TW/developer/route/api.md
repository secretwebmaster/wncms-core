# API 路由

## 概述

`api.php` 檔案定義了 WNCMS 的所有 RESTful API 端點。這些路由提供對選單、頁面、文章、標籤和系統更新等資源的程式化存取。

## 檔案位置

```
wncms-core/routes/api.php
```

## 路由結構

### API 版本群組

所有 API 路由都使用 `v1` 前綴進行版本控制:

```php
Route::prefix('v1')->name('api.v1.')->group(function () {
    // API 路由
});
```

**URL 結構:**

```
https://example.com/api/v1/posts
https://example.com/api/v1/menus
https://example.com/api/v1/tags
```

**路由命名:**

所有路由都使用 `api.v1.` 前綴:

```
api.v1.posts.index
api.v1.menus.show
api.v1.tags.store
```

## API 控制器

所有 API 控制器都位於 `Api\V1` 命名空間:

```php
use Wncms\Http\Controllers\Api\V1\MenuController;
use Wncms\Http\Controllers\Api\V1\PageController;
use Wncms\Http\Controllers\Api\V1\PostController;
use Wncms\Http\Controllers\Api\V1\TagController;
use Wncms\Http\Controllers\Api\V1\UpdateController;
```

## 選單 API

```php
Route::prefix('menus')->name('menus.')->controller(MenuController::class)->group(function () {
    Route::match(['GET', 'POST'], '/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('sync', 'sync')->name('sync');
    Route::match(['GET', 'POST'], '{id}', 'show')->name('show');
});
```

### 端點

| Method   | Endpoint              | Action | Description  |
| -------- | --------------------- | ------ | ------------ |
| GET/POST | `/api/v1/menus`       | index  | 列出所有選單 |
| POST     | `/api/v1/menus/store` | store  | 建立新選單   |
| POST     | `/api/v1/menus/sync`  | sync   | 同步選單項目 |
| GET/POST | `/api/v1/menus/{id}`  | show   | 取得特定選單 |

### 請求範例

**列出選單:**

```bash
curl -X GET https://example.com/api/v1/menus
```

**取得特定選單:**

```bash
curl -X GET https://example.com/api/v1/menus/1
```

**建立選單:**

```bash
curl -X POST https://example.com/api/v1/menus/store \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Menu",
    "location": "header"
  }'
```

### 回應格式

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Main Menu",
    "location": "header",
    "items": [...]
  }
}
```

## 頁面 API

```php
Route::prefix('pages')->name('pages.')->controller(PageController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('{id}', 'show')->name('show');
});
```

### 端點

| Method | Endpoint              | Action | Description  |
| ------ | --------------------- | ------ | ------------ |
| POST   | `/api/v1/pages`       | index  | 列出所有頁面 |
| POST   | `/api/v1/pages/store` | store  | 建立新頁面   |
| POST   | `/api/v1/pages/{id}`  | show   | 取得特定頁面 |

### 請求範例

**列出頁面:**

```bash
curl -X POST https://example.com/api/v1/pages \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1
  }'
```

**建立頁面:**

```bash
curl -X POST https://example.com/api/v1/pages/store \
  -H "Content-Type: application/json" \
  -d '{
    "title": "About Us",
    "slug": "about-us",
    "content": "Page content...",
    "status": "published"
  }'
```

## 文章 API

```php
Route::prefix('posts')->name('posts.')->controller(PostController::class)->group(function () {
    Route::match(['GET', 'POST'], '/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('update/{slug}', 'update')->name('update');
    Route::post('delete/{slug}', 'delete')->name('delete');
    Route::match(['GET', 'POST'], '{slug}', 'show')->name('show');
});
```

### 端點

| Method   | Endpoint                      | Action | Description  |
| -------- | ----------------------------- | ------ | ------------ |
| GET/POST | `/api/v1/posts`               | index  | 列出所有文章 |
| POST     | `/api/v1/posts/store`         | store  | 建立新文章   |
| POST     | `/api/v1/posts/update/{slug}` | update | 更新文章     |
| POST     | `/api/v1/posts/delete/{slug}` | delete | 刪除文章     |
| GET/POST | `/api/v1/posts/{slug}`        | show   | 取得特定文章 |

### 請求範例

**列出文章:**

```bash
curl -X GET https://example.com/api/v1/posts
```

**取得特定文章:**

```bash
curl -X GET https://example.com/api/v1/posts/my-article
```

**建立文章:**

```bash
curl -X POST https://example.com/api/v1/posts/store \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My New Post",
    "slug": "my-new-post",
    "content": "Post content...",
    "status": "published",
    "website_id": 1
  }'
```

**更新文章:**

```bash
curl -X POST https://example.com/api/v1/posts/update/my-article \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "content": "Updated content..."
  }'
```

**刪除文章:**

```bash
curl -X POST https://example.com/api/v1/posts/delete/my-article
```

### 回應格式

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "My New Post",
    "slug": "my-new-post",
    "content": "Post content...",
    "status": "published",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

## 標籤 API

```php
Route::prefix('tags')->name('tags.')->controller(TagController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('exist', 'exist')->name('exist');
    Route::post('store', 'store')->name('store');
});
```

### 端點

| Method | Endpoint             | Action | Description      |
| ------ | -------------------- | ------ | ---------------- |
| POST   | `/api/v1/tags`       | index  | 列出所有標籤     |
| POST   | `/api/v1/tags/exist` | exist  | 檢查標籤是否存在 |
| POST   | `/api/v1/tags/store` | store  | 建立新標籤       |

### 請求範例

**列出標籤:**

```bash
curl -X POST https://example.com/api/v1/tags \
  -H "Content-Type: application/json" \
  -d '{
    "type": "category"
  }'
```

**檢查標籤存在:**

```bash
curl -X POST https://example.com/api/v1/tags/exist \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "laravel",
    "type": "tag"
  }'
```

**建立標籤:**

```bash
curl -X POST https://example.com/api/v1/tags/store \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laravel",
    "slug": "laravel",
    "type": "tag"
  }'
```

## 更新 API

```php
Route::prefix('update')->name('update.')->controller(UpdateController::class)->group(function () {
    Route::post('/', 'update')->name('run');
    Route::post('progress', 'progress')->name('progress');
});
```

### 端點

| Method | Endpoint                  | Action   | Description  |
| ------ | ------------------------- | -------- | ------------ |
| POST   | `/api/v1/update`          | update   | 執行系統更新 |
| POST   | `/api/v1/update/progress` | progress | 取得更新進度 |

### 請求範例

**執行更新:**

```bash
curl -X POST https://example.com/api/v1/update \
  -H "Content-Type: application/json" \
  -d '{
    "version": "6.0.0"
  }'
```

**檢查進度:**

```bash
curl -X POST https://example.com/api/v1/update/progress
```

## 混合 GET/POST 方法

許多端點支援 GET 和 POST 兩種方法:

```php
Route::match(['GET', 'POST'], '/', 'index');
```

**為什麼使用混合方法?**

- **GET**: 簡單查詢、快取、可加入書籤的 URL
- **POST**: 複雜篩選、大型承載、敏感資料

**範例:**

```bash
# 簡單的 GET 請求
curl -X GET https://example.com/api/v1/posts

# 帶篩選的複雜 POST 請求
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published",
    "tag": "laravel",
    "limit": 10
  }'
```

## 自訂 API 路由

可透過 `custom_api.php` 新增自訂 API 路由:

```php
// 使用者自訂的 API 路由
if (file_exists(base_path('routes/custom_api.php'))) {
    include base_path('routes/custom_api.php');
}
```

### 建立 custom_api.php

在專案根目錄建立檔案:

```bash
touch routes/custom_api.php
```

**custom_api.php 範例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CustomController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('custom')->name('custom.')->controller(CustomController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
    });
});
```

## 身份驗證

### API Token

API 路由通常使用 token 身份驗證:

```php
// 在 API 控制器中
public function index(Request $request)
{
    $user = $request->user();

    // 使用者透過 token 驗證
}
```

### 帶 Token 的請求

```bash
curl -X GET https://example.com/api/v1/posts \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 生成 Token

```php
// 在控制器或控制台中
$token = $user->createToken('api-token')->plainTextToken;
```

## 速率限制

API 路由預設有速率限制 (在 `RouteServiceProvider` 中設定):

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
});
```

**預設限制:**

- 每分鐘每使用者/IP 60 個請求

### 處理速率限制

**回應:**

```json
{
  "message": "Too Many Requests",
  "retry_after": 30
}
```

**HTTP 狀態:**

- `429 Too Many Requests`

## 錯誤處理

### 標準錯誤回應

```json
{
  "status": "error",
  "message": "Resource not found",
  "errors": {
    "slug": ["The post could not be found"]
  }
}
```

### HTTP 狀態碼

| Code | Description |
| ---- | ----------- |
| 200  | 成功        |
| 201  | 已建立      |
| 400  | 錯誤請求    |
| 401  | 未授權      |
| 403  | 禁止        |
| 404  | 找不到      |
| 422  | 驗證錯誤    |
| 429  | 請求過多    |
| 500  | 伺服器錯誤  |

### 控制器範例

```php
public function show($slug)
{
    $post = Post::where('slug', $slug)->first();

    if (!$post) {
        return response()->json([
            'status' => 'error',
            'message' => 'Post not found'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $post
    ]);
}
```

## 分頁

API 端點支援分頁:

```bash
curl -X GET "https://example.com/api/v1/posts?page=2&per_page=20"
```

**回應:**

```json
{
  "status": "success",
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  },
  "links": {
    "first": "https://example.com/api/v1/posts?page=1",
    "last": "https://example.com/api/v1/posts?page=5",
    "prev": "https://example.com/api/v1/posts?page=1",
    "next": "https://example.com/api/v1/posts?page=3"
  }
}
```

## 篩選與排序

### 篩選

```bash
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published",
    "tag": "laravel",
    "date_from": "2024-01-01"
  }'
```

### 排序

```bash
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "sort_by": "created_at",
    "sort_order": "desc"
  }'
```

## 生成 URL

### 在控制器中

```php
// 路由到 API 端點
return redirect()->route('api.v1.posts.index');

// 生成 API URL
$url = route('api.v1.posts.show', ['slug' => 'my-article']);
```

### 在程式碼中

```php
// API 客戶端
$response = Http::get(route('api.v1.posts.index'));

// 帶參數
$response = Http::post(route('api.v1.posts.store'), [
    'title' => 'New Post',
    'content' => 'Content...'
]);
```

## CORS (跨來源資源共用)

為 API 路由啟用 CORS:

```php
// 在 config/cors.php 中
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_headers' => ['*'],
];
```

## 最佳實踐

### 1. 使用版本控制

始終為您的 API 使用版本控制:

```php
Route::prefix('v1')->group(function () {
    // v1 路由
});

Route::prefix('v2')->group(function () {
    // v2 路由
});
```

### 2. 回傳一致的回應

```php
public function index()
{
    return response()->json([
        'status' => 'success',
        'data' => $posts
    ]);
}
```

### 3. 驗證輸入

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    // 建立資源
}
```

### 4. 使用資源類別

```php
use Wncms\Http\Resources\PostResource;

public function show($slug)
{
    $post = Post::where('slug', $slug)->firstOrFail();
    return new PostResource($post);
}
```

### 5. 記錄您的 API

提供清晰的文件，包括:

- 端點描述
- 請求/回應範例
- 身份驗證需求
- 速率限制
- 錯誤代碼

## 測試 API 路由

### 使用 Postman

1. 為 WNCMS API 建立新集合
2. 為每個端點新增請求
3. 設定身份驗證 token
4. 使用各種參數測試

### 使用 PHPUnit

```php
public function test_api_returns_posts()
{
    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'status',
                 'data' => [
                     '*' => ['id', 'title', 'slug']
                 ]
             ]);
}
```

## 參閱

- [後台路由](./backend.md) - 管理面板路由
- [前台路由](./frontend.md) - 公開路由
- [API 資源](../resource/api-resource.md) - 資源轉換
- [新增路由](./add-routes.md) - 建立自訂路由
