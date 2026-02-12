# API 路由

## 概述

`api.php` 档案定义了 WNCMS 的所有 RESTful API 端点。这些路由提供对选单、页面、文章、标签和系统更新等资源的程式化存取。

## 档案位置

```
wncms-core/routes/api.php
```

## 路由结构

### API 版本群组

所有 API 路由都使用 `v1` 前缀进行版本控制:

```php
Route::prefix('v1')->name('api.v1.')->group(function () {
    // API 路由
});
```

**URL 结构:**

```
https://example.com/api/v1/posts
https://example.com/api/v1/menus
https://example.com/api/v1/tags
```

**路由命名:**

所有路由都使用 `api.v1.` 前缀:

```
api.v1.posts.index
api.v1.menus.show
api.v1.tags.store
```

## API 控制器

所有 API 控制器都位于 `Api\V1` 命名空间:

```php
use Wncms\Http\Controllers\Api\V1\MenuController;
use Wncms\Http\Controllers\Api\V1\PageController;
use Wncms\Http\Controllers\Api\V1\PostController;
use Wncms\Http\Controllers\Api\V1\TagController;
use Wncms\Http\Controllers\Api\V1\UpdateController;
```

## 选单 API

```php
Route::prefix('menus')->name('menus.')->controller(MenuController::class)->group(function () {
    Route::match(['GET', 'POST'], '/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('sync', 'sync')->name('sync');
    Route::match(['GET', 'POST'], '{id}', 'show')->name('show');
});
```

### 端点

| Method   | Endpoint              | Action | Description  |
| -------- | --------------------- | ------ | ------------ |
| GET/POST | `/api/v1/menus`       | index  | 列出所有选单 |
| POST     | `/api/v1/menus/store` | store  | 建立新选单   |
| POST     | `/api/v1/menus/sync`  | sync   | 同步选单项目 |
| GET/POST | `/api/v1/menus/{id}`  | show   | 取得特定选单 |

### 请求范例

**列出选单:**

```bash
curl -X GET https://example.com/api/v1/menus
```

**取得特定选单:**

```bash
curl -X GET https://example.com/api/v1/menus/1
```

**建立选单:**

```bash
curl -X POST https://example.com/api/v1/menus/store \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Menu",
    "location": "header"
  }'
```

### 回应格式

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

## 页面 API

```php
Route::prefix('pages')->name('pages.')->controller(PageController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('{id}', 'show')->name('show');
});
```

### 端点

| Method | Endpoint              | Action | Description  |
| ------ | --------------------- | ------ | ------------ |
| POST   | `/api/v1/pages`       | index  | 列出所有页面 |
| POST   | `/api/v1/pages/store` | store  | 建立新页面   |
| POST   | `/api/v1/pages/{id}`  | show   | 取得特定页面 |

### 请求范例

**列出页面:**

```bash
curl -X POST https://example.com/api/v1/pages \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1
  }'
```

**建立页面:**

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

### 端点

| Method   | Endpoint                      | Action | Description  |
| -------- | ----------------------------- | ------ | ------------ |
| GET/POST | `/api/v1/posts`               | index  | 列出所有文章 |
| POST     | `/api/v1/posts/store`         | store  | 建立新文章   |
| POST     | `/api/v1/posts/update/{slug}` | update | 更新文章     |
| POST     | `/api/v1/posts/delete/{slug}` | delete | 删除文章     |
| GET/POST | `/api/v1/posts/{slug}`        | show   | 取得特定文章 |

### 请求范例

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

**删除文章:**

```bash
curl -X POST https://example.com/api/v1/posts/delete/my-article
```

### 回应格式

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

## 标签 API

```php
Route::prefix('tags')->name('tags.')->controller(TagController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('exist', 'exist')->name('exist');
    Route::post('store', 'store')->name('store');
});
```

### 端点

| Method | Endpoint             | Action | Description      |
| ------ | -------------------- | ------ | ---------------- |
| POST   | `/api/v1/tags`       | index  | 列出所有标签     |
| POST   | `/api/v1/tags/exist` | exist  | 检查标签是否存在 |
| POST   | `/api/v1/tags/store` | store  | 建立新标签       |

### 请求范例

**列出标签:**

```bash
curl -X POST https://example.com/api/v1/tags \
  -H "Content-Type: application/json" \
  -d '{
    "type": "category"
  }'
```

**检查标签存在:**

```bash
curl -X POST https://example.com/api/v1/tags/exist \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "laravel",
    "type": "tag"
  }'
```

**建立标签:**

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

### 端点

| Method | Endpoint                  | Action   | Description  |
| ------ | ------------------------- | -------- | ------------ |
| POST   | `/api/v1/update`          | update   | 执行系统更新 |
| POST   | `/api/v1/update/progress` | progress | 取得更新进度 |

### 请求范例

**执行更新:**

```bash
curl -X POST https://example.com/api/v1/update \
  -H "Content-Type: application/json" \
  -d '{
    "version": "6.0.0"
  }'
```

**检查进度:**

```bash
curl -X POST https://example.com/api/v1/update/progress
```

## 混合 GET/POST 方法

许多端点支援 GET 和 POST 两种方法:

```php
Route::match(['GET', 'POST'], '/', 'index');
```

**为什么使用混合方法?**

- **GET**: 简单查询、快取、可加入书签的 URL
- **POST**: 复杂筛选、大型承载、敏感资料

**范例:**

```bash
# 简单的 GET 请求
curl -X GET https://example.com/api/v1/posts

# 带筛选的复杂 POST 请求
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published",
    "tag": "laravel",
    "limit": 10
  }'
```

## 自订 API 路由

可透过 `custom_api.php` 新增自订 API 路由:

```php
// 使用者自订的 API 路由
if (file_exists(base_path('routes/custom_api.php'))) {
    include base_path('routes/custom_api.php');
}
```

### 建立 custom_api.php

在专案根目录建立档案:

```bash
touch routes/custom_api.php
```

**custom_api.php 范例:**

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

## 身份验证

### API Token

API 路由通常使用 token 身份验证:

```php
// 在 API 控制器中
public function index(Request $request)
{
    $user = $request->user();

    // 使用者透过 token 验证
}
```

### 带 Token 的请求

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

API 路由预设有速率限制 (在 `RouteServiceProvider` 中设定):

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
});
```

**预设限制:**

- 每分钟每使用者/IP 60 个请求

### 处理速率限制

**回应:**

```json
{
  "message": "Too Many Requests",
  "retry_after": 30
}
```

**HTTP 状态:**

- `429 Too Many Requests`

## 错误处理

### 标准错误回应

```json
{
  "status": "error",
  "message": "Resource not found",
  "errors": {
    "slug": ["The post could not be found"]
  }
}
```

### HTTP 状态码

| Code | Description |
| ---- | ----------- |
| 200  | 成功        |
| 201  | 已建立      |
| 400  | 错误请求    |
| 401  | 未授权      |
| 403  | 禁止        |
| 404  | 找不到      |
| 422  | 验证错误    |
| 429  | 请求过多    |
| 500  | 伺服器错误  |

### 控制器范例

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

## 分页

API 端点支援分页:

```bash
curl -X GET "https://example.com/api/v1/posts?page=2&per_page=20"
```

**回应:**

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

## 筛选与排序

### 筛选

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
// 路由到 API 端点
return redirect()->route('api.v1.posts.index');

// 生成 API URL
$url = route('api.v1.posts.show', ['slug' => 'my-article']);
```

### 在程式码中

```php
// API 客户端
$response = Http::get(route('api.v1.posts.index'));

// 带参数
$response = Http::post(route('api.v1.posts.store'), [
    'title' => 'New Post',
    'content' => 'Content...'
]);
```

## CORS (跨来源资源共用)

为 API 路由启用 CORS:

```php
// 在 config/cors.php 中
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_headers' => ['*'],
];
```

## 最佳实践

### 1. 使用版本控制

始终为您的 API 使用版本控制:

```php
Route::prefix('v1')->group(function () {
    // v1 路由
});

Route::prefix('v2')->group(function () {
    // v2 路由
});
```

### 2. 回传一致的回应

```php
public function index()
{
    return response()->json([
        'status' => 'success',
        'data' => $posts
    ]);
}
```

### 3. 验证输入

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    // 建立资源
}
```

### 4. 使用资源类别

```php
use Wncms\Http\Resources\PostResource;

public function show($slug)
{
    $post = Post::where('slug', $slug)->firstOrFail();
    return new PostResource($post);
}
```

### 5. 记录您的 API

提供清晰的文件，包括:

- 端点描述
- 请求/回应范例
- 身份验证需求
- 速率限制
- 错误代码

## 测试 API 路由

### 使用 Postman

1. 为 WNCMS API 建立新集合
2. 为每个端点新增请求
3. 设定身份验证 token
4. 使用各种参数测试

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

## 参阅

- [后台路由](./backend.md) - 管理面板路由
- [前台路由](./frontend.md) - 公开路由
- [API 资源](../resource/api-resource.md) - 资源转换
- [新增路由](./add-routes.md) - 建立自订路由
