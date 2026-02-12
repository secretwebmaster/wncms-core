# 新增自订路由

## 概述

本指南说明如何在不修改核心套件档案的情况下,向 WNCMS 新增自订路由。WNCMS 提供了多个扩充点用于新增您自己的路由。

## 自订路由档案

WNCMS 会自动载入自订路由档案(如果它们存在):

### 前台路由

**档案:** `routes/custom_frontend.php`

自动包含在主要前台路由群组中,并支援本地化。

**范例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomController;

// 这些路由继承前台中介层和命名
Route::get('custom', [CustomController::class, 'index'])->name('custom.index');
Route::get('custom/{id}', [CustomController::class, 'show'])->name('custom.show');
```

**URL:**

```
https://example.com/en/custom
https://example.com/zh_TW/custom/123
```

### 后台路由

**档案:** `routes/custom_backend.php`

自动包含在后台路由群组中,具有身份验证和 panel 前缀。

**范例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\CustomAdminController;

// 这些路由继承 /panel 前缀和 auth 中介层
Route::prefix('custom')->name('custom.')->controller(CustomAdminController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('create', 'create')->name('create');
    Route::post('store', 'store')->name('store');
    Route::get('{id}/edit', 'edit')->name('edit');
    Route::post('{id}/update', 'update')->name('update');
    Route::post('{id}/delete', 'destroy')->name('destroy');
});
```

**URL:**

```
https://example.com/panel/custom
https://example.com/panel/custom/create
https://example.com/panel/custom/1/edit
```

### API 路由

**档案:** `routes/custom_api.php`

自动包含在 API 路由群组中,并具有版本控制。

**范例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CustomApiController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('custom')->name('custom.')->controller(CustomApiController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
    });
});
```

**URL:**

```
https://example.com/api/v1/custom
https://example.com/api/v1/custom/123
```

## 建立自订路由档案

### 步骤 1: 建立 Routes 目录

如果不存在的话:

```bash
mkdir -p routes
```

### 步骤 2: 建立自订路由档案

```bash
touch routes/custom_frontend.php
```

### 步骤 3: 定义路由

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### 步骤 4: 建立控制器

```bash
php artisan make:controller ProductController
```

**app/Http/Controllers/ProductController.php:**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index');
    }

    public function show($slug)
    {
        return view('products.show', compact('slug'));
    }
}
```

### 步骤 5: 建立视图

```bash
mkdir -p resources/views/products
touch resources/views/products/index.blade.php
touch resources/views/products/show.blade.php
```

### 步骤 6: 清除路由快取

```bash
php artisan route:clear
```

## 路由命名惯例

### 前台路由

```php
// 良好的命名方式
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/{slug}', [ServiceController::class, 'show'])->name('services.show');

// 避免与 WNCMS 核心路由冲突
// 不要使用: pages.*, posts.*, users.* 等
```

### 后台路由

```php
// 良好的命名方式,注意 panel 前缀
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', 'index')->name('index'); // panel.products.index
    Route::post('store', 'store')->name('store'); // panel.products.store
});
```

### API 路由

```php
// 良好的版本化 API 命名
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index'); // api.v1.products.index
    });
});
```

## 新增中介层

### 前台中介层

```php
// 需要身份验证
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// 自订中介层
Route::middleware(['custom_middleware'])->group(function () {
    Route::get('special', [SpecialController::class, 'index']);
});
```

### 后台中介层与权限

```php
// 需要特定权限
Route::middleware('can:product_index')->get('products', [ProductController::class, 'index']);

// 多个权限
Route::middleware(['can:product_edit', 'can:product_delete'])->group(function () {
    Route::get('products/{id}/edit', [ProductController::class, 'edit']);
    Route::post('products/{id}/delete', [ProductController::class, 'destroy']);
});
```

### API 中介层

```php
// 需要身份验证
Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/store', [ProductController::class, 'store']);
});

// 速率限制
Route::middleware('throttle:60,1')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
});
```

## 权限管理

### 建立权限

对于具有自订权限的后台路由:

```php
// 在资料库填充器或迁移中
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'product_index']);
Permission::create(['name' => 'product_create']);
Permission::create(['name' => 'product_edit']);
Permission::create(['name' => 'product_delete']);
```

### 分配权限

```php
$role = Role::findByName('admin');
$role->givePermissionTo(['product_index', 'product_create', 'product_edit', 'product_delete']);
```

### 在路由中使用权限

```php
Route::prefix('products')->name('products.')->group(function () {
    Route::middleware('can:product_index')->get('/', 'index')->name('index');
    Route::middleware('can:product_create')->get('create', 'create')->name('create');
    Route::middleware('can:product_create')->post('store', 'store')->name('store');
    Route::middleware('can:product_edit')->get('{id}/edit', 'edit')->name('edit');
    Route::middleware('can:product_edit')->post('{id}/update', 'update')->name('update');
    Route::middleware('can:product_delete')->post('{id}/delete', 'destroy')->name('destroy');
});
```

## 路由参数

### 必要参数

```php
Route::get('products/{id}', [ProductController::class, 'show']);
```

### 可选参数

```php
Route::get('products/{category?}', [ProductController::class, 'index']);
```

### 参数约束

```php
// 数字 ID
Route::get('products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');

// Slug
Route::get('products/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+');

// 多个约束
Route::get('archive/{year}/{month}', [ArchiveController::class, 'show'])
    ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
```

### 路由模型绑定

```php
// 自动绑定
Route::get('products/{product}', [ProductController::class, 'show']);

// 控制器接收模型实例
public function show(Product $product)
{
    return view('products.show', compact('product'));
}

// 自订绑定键
Route::get('products/{product:slug}', [ProductController::class, 'show']);
```

## 资源路由

### 标准资源

```php
use App\Http\Controllers\ProductController;

Route::resource('products', ProductController::class);
```

**生成的路由:**

| Method    | URI                 | Action  | Route Name       |
| --------- | ------------------- | ------- | ---------------- |
| GET       | /products           | index   | products.index   |
| GET       | /products/create    | create  | products.create  |
| POST      | /products           | store   | products.store   |
| GET       | /products/{id}      | show    | products.show    |
| GET       | /products/{id}/edit | edit    | products.edit    |
| PUT/PATCH | /products/{id}      | update  | products.update  |
| DELETE    | /products/{id}      | destroy | products.destroy |

### 部分资源

```php
// 只使用特定动作
Route::resource('products', ProductController::class)->only(['index', 'show']);

// 排除特定动作
Route::resource('products', ProductController::class)->except(['destroy']);
```

### API 资源

```php
// 排除 create 和 edit 表单
Route::apiResource('products', ProductController::class);
```

## 巢状路由

```php
// 产品与评论
Route::prefix('products/{product}')->group(function () {
    Route::get('reviews', [ReviewController::class, 'index'])->name('products.reviews.index');
    Route::post('reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
});
```

**URL:**

```
/products/123/reviews
```

## 本地化路由

`custom_frontend.php` 中的自订路由会自动支援本地化:

```php
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
```

**URLs:**

```
https://example.com/en/services
https://example.com/zh_TW/services
```

### 生成本地化 URL

```blade
{{-- 当前语言 --}}
<a href="{{ route('services.index') }}">Services</a>

{{-- 特定语言 --}}
<a href="{{ LaravelLocalization::getLocalizedURL('zh_TW', route('services.index')) }}">
    服务
</a>
```

## AJAX 路由

### 前台 AJAX

```php
Route::post('products/filter', [ProductController::class, 'filter'])->name('products.filter');
```

**控制器:**

```php
public function filter(Request $request)
{
    $products = Product::query();

    if ($request->has('category')) {
        $products->where('category_id', $request->category);
    }

    return response()->json([
        'status' => 'success',
        'data' => $products->get()
    ]);
}
```

**JavaScript:**

```javascript
$.ajax({
  url: '{{ route("products.filter") }}',
  method: 'POST',
  data: {
    category: categoryId,
    _token: '{{ csrf_token() }}',
  },
  success: function (response) {
    // 处理回应
  },
})
```

### 后台 AJAX

```php
Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])
    ->name('products.bulk_delete')
    ->middleware('can:product_delete');
```

## 路由群组

### 组织相关路由

```php
// 产品模组
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('{slug}', [ProductController::class, 'show'])->name('show');

    // 分类
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('{slug}', [CategoryController::class, 'show'])->name('show');
    });
});
```

## 测试自订路由

### 路由列表

```bash
php artisan route:list --name=products
```

### 使用 cURL 测试

```bash
# 测试 GET 路由
curl https://example.com/products

# 测试 POST 路由
curl -X POST https://example.com/products/store \
  -H "Content-Type: application/json" \
  -d '{"name":"Product Name"}'
```

### PHPUnit 测试

```php
public function test_products_index_page()
{
    $response = $this->get(route('products.index'));
    $response->assertStatus(200);
}

public function test_product_store_requires_authentication()
{
    $response = $this->post(route('products.store'), [
        'name' => 'Test Product'
    ]);
    $response->assertRedirect(route('frontend.users.login'));
}
```

## 路由快取

### 变更后清除快取

```bash
php artisan route:clear
```

### 为正式环境快取路由

```bash
php artisan route:cache
```

**注意:** 路由快取不支援基于闭包的路由。请始终使用控制器方法。

## 最佳实践

### 1. 使用命名路由

```php
// 良好
Route::get('products', [ProductController::class, 'index'])->name('products.index');

// 避免
Route::get('products', [ProductController::class, 'index']);
```

### 2. 群组相关路由

```php
Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### 3. 使用资源控制器

```php
// 不需要手动定义所有 CRUD 路由
Route::resource('products', ProductController::class);
```

### 4. 适当地套用中介层

```php
// 套用到群组
Route::middleware('auth')->group(function () {
    // 受保护的路由
});

// 套用到特定路由
Route::get('admin', [AdminController::class, 'index'])->middleware('auth');
```

### 5. 验证参数

```php
Route::get('products/{id}', [ProductController::class, 'show'])
    ->where('id', '[0-9]+');
```

### 6. 使用路由模型绑定

```php
Route::get('products/{product}', [ProductController::class, 'show']);

public function show(Product $product)
{
    // $product 会自动载入
}
```

### 7. 记录您的路由

```php
/**
 * 产品路由
 *
 * 这些路由处理产品列表、查看和管理。
 * 所有路由都需要 'has_website' 中介层。
 */
Route::prefix('products')->group(function () {
    // 路由...
});
```

## 常见问题

### 找不到路由

**问题:** 自订路由回传 404

**解决方案:**

```bash
# 清除路由快取
php artisan route:clear

# 检查路由是否存在
php artisan route:list --name=your_route
```

### 中介层冲突

**问题:** 路由中介层与 WNCMS 中介层冲突

**解决方案:**

```php
// 使用 except() 排除特定中介层
Route::middleware(['web'])->withoutMiddleware(['full_page_cache'])->group(function () {
    // 没有快取的路由
});
```

### 本地化问题

**问题:** 路由无法与语言前缀一起使用

**解决方案:**

确保路由在 `custom_frontend.php` 中以继承本地化群组。

### 权限错误

**问题:** 权限中介层无法运作

**解决方案:**

```php
// 确保权限存在于资料库中
Permission::create(['name' => 'product_index']);

// 分配给角色
$role->givePermissionTo('product_index');
```

## 参阅

- [Web 路由](./web.md) - 主要路由进入点
- [后台路由](./backend.md) - 管理面板路由
- [前台路由](./frontend.md) - 公开路由
- [API 路由](./api.md) - RESTful API
- [后台控制器](../controller/backend-controller.md) - 建立后台控制器
- [前台控制器](../controller/frontend-controller.md) - 建立前台控制器
