# 新增自訂路由

## 概述

本指南說明如何在不修改核心套件檔案的情況下,向 WNCMS 新增自訂路由。WNCMS 提供了多個擴充點用於新增您自己的路由。

## 自訂路由檔案

WNCMS 會自動載入自訂路由檔案(如果它們存在):

### 前台路由

**檔案:** `routes/custom_frontend.php`

自動包含在主要前台路由群組中,並支援本地化。

**範例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomController;

// 這些路由繼承前台中介層和命名
Route::get('custom', [CustomController::class, 'index'])->name('custom.index');
Route::get('custom/{id}', [CustomController::class, 'show'])->name('custom.show');
```

**URL:**

```
https://example.com/en/custom
https://example.com/zh_TW/custom/123
```

### 後台路由

**檔案:** `routes/custom_backend.php`

自動包含在後台路由群組中,具有身份驗證和 panel 前綴。

**範例:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\CustomAdminController;

// 這些路由繼承 /panel 前綴和 auth 中介層
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

**檔案:** `routes/custom_api.php`

自動包含在 API 路由群組中,並具有版本控制。

**範例:**

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

## 建立自訂路由檔案

### 步驟 1: 建立 Routes 目錄

如果不存在的話:

```bash
mkdir -p routes
```

### 步驟 2: 建立自訂路由檔案

```bash
touch routes/custom_frontend.php
```

### 步驟 3: 定義路由

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### 步驟 4: 建立控制器

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

### 步驟 5: 建立視圖

```bash
mkdir -p resources/views/products
touch resources/views/products/index.blade.php
touch resources/views/products/show.blade.php
```

### 步驟 6: 清除路由快取

```bash
php artisan route:clear
```

## 路由命名慣例

### 前台路由

```php
// 良好的命名方式
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/{slug}', [ServiceController::class, 'show'])->name('services.show');

// 避免與 WNCMS 核心路由衝突
// 不要使用: pages.*, posts.*, users.* 等
```

### 後台路由

```php
// 良好的命名方式,注意 panel 前綴
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

## 新增中介層

### 前台中介層

```php
// 需要身份驗證
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// 自訂中介層
Route::middleware(['custom_middleware'])->group(function () {
    Route::get('special', [SpecialController::class, 'index']);
});
```

### 後台中介層與權限

```php
// 需要特定權限
Route::middleware('can:product_index')->get('products', [ProductController::class, 'index']);

// 多個權限
Route::middleware(['can:product_edit', 'can:product_delete'])->group(function () {
    Route::get('products/{id}/edit', [ProductController::class, 'edit']);
    Route::post('products/{id}/delete', [ProductController::class, 'destroy']);
});
```

### API 中介層

```php
// 需要身份驗證
Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/store', [ProductController::class, 'store']);
});

// 速率限制
Route::middleware('throttle:60,1')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
});
```

## 權限管理

### 建立權限

對於具有自訂權限的後台路由:

```php
// 在資料庫填充器或遷移中
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'product_index']);
Permission::create(['name' => 'product_create']);
Permission::create(['name' => 'product_edit']);
Permission::create(['name' => 'product_delete']);
```

### 分配權限

```php
$role = Role::findByName('admin');
$role->givePermissionTo(['product_index', 'product_create', 'product_edit', 'product_delete']);
```

### 在路由中使用權限

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

## 路由參數

### 必要參數

```php
Route::get('products/{id}', [ProductController::class, 'show']);
```

### 可選參數

```php
Route::get('products/{category?}', [ProductController::class, 'index']);
```

### 參數約束

```php
// 數字 ID
Route::get('products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');

// Slug
Route::get('products/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+');

// 多個約束
Route::get('archive/{year}/{month}', [ArchiveController::class, 'show'])
    ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
```

### 路由模型綁定

```php
// 自動綁定
Route::get('products/{product}', [ProductController::class, 'show']);

// 控制器接收模型實例
public function show(Product $product)
{
    return view('products.show', compact('product'));
}

// 自訂綁定鍵
Route::get('products/{product:slug}', [ProductController::class, 'show']);
```

## 資源路由

### 標準資源

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

### 部分資源

```php
// 只使用特定動作
Route::resource('products', ProductController::class)->only(['index', 'show']);

// 排除特定動作
Route::resource('products', ProductController::class)->except(['destroy']);
```

### API 資源

```php
// 排除 create 和 edit 表單
Route::apiResource('products', ProductController::class);
```

## 巢狀路由

```php
// 產品與評論
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

`custom_frontend.php` 中的自訂路由會自動支援本地化:

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
{{-- 當前語言 --}}
<a href="{{ route('services.index') }}">Services</a>

{{-- 特定語言 --}}
<a href="{{ LaravelLocalization::getLocalizedURL('zh_TW', route('services.index')) }}">
    服務
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
    // 處理回應
  },
})
```

### 後台 AJAX

```php
Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])
    ->name('products.bulk_delete')
    ->middleware('can:product_delete');
```

## 路由群組

### 組織相關路由

```php
// 產品模組
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('{slug}', [ProductController::class, 'show'])->name('show');

    // 分類
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('{slug}', [CategoryController::class, 'show'])->name('show');
    });
});
```

## 測試自訂路由

### 路由列表

```bash
php artisan route:list --name=products
```

### 使用 cURL 測試

```bash
# 測試 GET 路由
curl https://example.com/products

# 測試 POST 路由
curl -X POST https://example.com/products/store \
  -H "Content-Type: application/json" \
  -d '{"name":"Product Name"}'
```

### PHPUnit 測試

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

### 變更後清除快取

```bash
php artisan route:clear
```

### 為正式環境快取路由

```bash
php artisan route:cache
```

**注意:** 路由快取不支援基於閉包的路由。請始終使用控制器方法。

## 最佳實踐

### 1. 使用命名路由

```php
// 良好
Route::get('products', [ProductController::class, 'index'])->name('products.index');

// 避免
Route::get('products', [ProductController::class, 'index']);
```

### 2. 群組相關路由

```php
Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### 3. 使用資源控制器

```php
// 不需要手動定義所有 CRUD 路由
Route::resource('products', ProductController::class);
```

### 4. 適當地套用中介層

```php
// 套用到群組
Route::middleware('auth')->group(function () {
    // 受保護的路由
});

// 套用到特定路由
Route::get('admin', [AdminController::class, 'index'])->middleware('auth');
```

### 5. 驗證參數

```php
Route::get('products/{id}', [ProductController::class, 'show'])
    ->where('id', '[0-9]+');
```

### 6. 使用路由模型綁定

```php
Route::get('products/{product}', [ProductController::class, 'show']);

public function show(Product $product)
{
    // $product 會自動載入
}
```

### 7. 記錄您的路由

```php
/**
 * 產品路由
 *
 * 這些路由處理產品列表、查看和管理。
 * 所有路由都需要 'has_website' 中介層。
 */
Route::prefix('products')->group(function () {
    // 路由...
});
```

## 常見問題

### 找不到路由

**問題:** 自訂路由回傳 404

**解決方案:**

```bash
# 清除路由快取
php artisan route:clear

# 檢查路由是否存在
php artisan route:list --name=your_route
```

### 中介層衝突

**問題:** 路由中介層與 WNCMS 中介層衝突

**解決方案:**

```php
// 使用 except() 排除特定中介層
Route::middleware(['web'])->withoutMiddleware(['full_page_cache'])->group(function () {
    // 沒有快取的路由
});
```

### 本地化問題

**問題:** 路由無法與語言前綴一起使用

**解決方案:**

確保路由在 `custom_frontend.php` 中以繼承本地化群組。

### 權限錯誤

**問題:** 權限中介層無法運作

**解決方案:**

```php
// 確保權限存在於資料庫中
Permission::create(['name' => 'product_index']);

// 分配給角色
$role->givePermissionTo('product_index');
```

## 參閱

- [Web 路由](./web.md) - 主要路由進入點
- [後台路由](./backend.md) - 管理面板路由
- [前台路由](./frontend.md) - 公開路由
- [API 路由](./api.md) - RESTful API
- [後台控制器](../controller/backend-controller.md) - 建立後台控制器
- [前台控制器](../controller/frontend-controller.md) - 建立前台控制器
