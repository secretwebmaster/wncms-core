# Adding Custom Routes

## Overview

This guide explains how to add custom routes to WNCMS without modifying the core package files. WNCMS provides several extension points for adding your own routes.

## Custom Route Files

WNCMS automatically includes custom route files if they exist:

### Frontend Routes

**File:** `routes/custom_frontend.php`

Automatically included in the main frontend route group with localization support.

**Example:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomController;

// These routes inherit the frontend middleware and naming
Route::get('custom', [CustomController::class, 'index'])->name('custom.index');
Route::get('custom/{id}', [CustomController::class, 'show'])->name('custom.show');
```

**URL:**

```
https://example.com/en/custom
https://example.com/zh_TW/custom/123
```

### Backend Routes

**File:** `routes/custom_backend.php`

Automatically included in the backend route group with authentication and panel prefix.

**Example:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\CustomAdminController;

// These routes inherit /panel prefix and auth middleware
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

### API Routes

**File:** `routes/custom_api.php`

Automatically included in the API route group with versioning.

**Example:**

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

## Creating Custom Route Files

### Step 1: Create Routes Directory

If it doesn't exist:

```bash
mkdir -p routes
```

### Step 2: Create Custom Route File

```bash
touch routes/custom_frontend.php
```

### Step 3: Define Routes

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### Step 4: Create Controller

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

### Step 5: Create Views

```bash
mkdir -p resources/views/products
touch resources/views/products/index.blade.php
touch resources/views/products/show.blade.php
```

### Step 6: Clear Route Cache

```bash
php artisan route:clear
```

## Route Naming Conventions

### Frontend Routes

```php
// Good naming
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/{slug}', [ServiceController::class, 'show'])->name('services.show');

// Avoid conflicts with WNCMS core routes
// Don't use: pages.*, posts.*, users.*, etc.
```

### Backend Routes

```php
// Good naming with panel prefix awareness
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', 'index')->name('index'); // panel.products.index
    Route::post('store', 'store')->name('store'); // panel.products.store
});
```

### API Routes

```php
// Good versioned API naming
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index'); // api.v1.products.index
    });
});
```

## Adding Middleware

### Frontend Middleware

```php
// Require authentication
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Custom middleware
Route::middleware(['custom_middleware'])->group(function () {
    Route::get('special', [SpecialController::class, 'index']);
});
```

### Backend Middleware with Permissions

```php
// Require specific permission
Route::middleware('can:product_index')->get('products', [ProductController::class, 'index']);

// Multiple permissions
Route::middleware(['can:product_edit', 'can:product_delete'])->group(function () {
    Route::get('products/{id}/edit', [ProductController::class, 'edit']);
    Route::post('products/{id}/delete', [ProductController::class, 'destroy']);
});
```

### API Middleware

```php
// Require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/store', [ProductController::class, 'store']);
});

// Rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
});
```

## Permission Management

### Creating Permissions

For backend routes with custom permissions:

```php
// In database seeder or migration
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'product_index']);
Permission::create(['name' => 'product_create']);
Permission::create(['name' => 'product_edit']);
Permission::create(['name' => 'product_delete']);
```

### Assigning Permissions

```php
$role = Role::findByName('admin');
$role->givePermissionTo(['product_index', 'product_create', 'product_edit', 'product_delete']);
```

### Using Permissions in Routes

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

## Route Parameters

### Required Parameters

```php
Route::get('products/{id}', [ProductController::class, 'show']);
```

### Optional Parameters

```php
Route::get('products/{category?}', [ProductController::class, 'index']);
```

### Parameter Constraints

```php
// Numeric ID
Route::get('products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');

// Slug
Route::get('products/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+');

// Multiple constraints
Route::get('archive/{year}/{month}', [ArchiveController::class, 'show'])
    ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
```

### Route Model Binding

```php
// Automatic binding
Route::get('products/{product}', [ProductController::class, 'show']);

// Controller receives model instance
public function show(Product $product)
{
    return view('products.show', compact('product'));
}

// Custom binding key
Route::get('products/{product:slug}', [ProductController::class, 'show']);
```

## Resource Routes

### Standard Resource

```php
use App\Http\Controllers\ProductController;

Route::resource('products', ProductController::class);
```

**Generated Routes:**

| Method    | URI                 | Action  | Route Name       |
| --------- | ------------------- | ------- | ---------------- |
| GET       | /products           | index   | products.index   |
| GET       | /products/create    | create  | products.create  |
| POST      | /products           | store   | products.store   |
| GET       | /products/{id}      | show    | products.show    |
| GET       | /products/{id}/edit | edit    | products.edit    |
| PUT/PATCH | /products/{id}      | update  | products.update  |
| DELETE    | /products/{id}      | destroy | products.destroy |

### Partial Resource

```php
// Only specific actions
Route::resource('products', ProductController::class)->only(['index', 'show']);

// Exclude specific actions
Route::resource('products', ProductController::class)->except(['destroy']);
```

### API Resource

```php
// Excludes create and edit forms
Route::apiResource('products', ProductController::class);
```

## Nested Routes

```php
// Products with reviews
Route::prefix('products/{product}')->group(function () {
    Route::get('reviews', [ReviewController::class, 'index'])->name('products.reviews.index');
    Route::post('reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
});
```

**URL:**

```
/products/123/reviews
```

## Localized Routes

Custom routes in `custom_frontend.php` automatically support localization:

```php
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
```

**URLs:**

```
https://example.com/en/services
https://example.com/zh_TW/services
```

### Generating Localized URLs

```blade
{{-- Current locale --}}
<a href="{{ route('services.index') }}">Services</a>

{{-- Specific locale --}}
<a href="{{ LaravelLocalization::getLocalizedURL('zh_TW', route('services.index')) }}">
    服務
</a>
```

## AJAX Routes

### Frontend AJAX

```php
Route::post('products/filter', [ProductController::class, 'filter'])->name('products.filter');
```

**Controller:**

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
    // Handle response
  },
})
```

### Backend AJAX

```php
Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])
    ->name('products.bulk_delete')
    ->middleware('can:product_delete');
```

## Route Groups

### Organizing Related Routes

```php
// Products module
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('{slug}', [ProductController::class, 'show'])->name('show');

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('{slug}', [CategoryController::class, 'show'])->name('show');
    });
});
```

## Testing Custom Routes

### Route List

```bash
php artisan route:list --name=products
```

### Testing with cURL

```bash
# Test GET route
curl https://example.com/products

# Test POST route
curl -X POST https://example.com/products/store \
  -H "Content-Type: application/json" \
  -d '{"name":"Product Name"}'
```

### PHPUnit Tests

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

## Route Caching

### Clear Cache After Changes

```bash
php artisan route:clear
```

### Cache Routes for Production

```bash
php artisan route:cache
```

**Note:** Route caching doesn't work with closure-based routes. Always use controller methods.

## Best Practices

### 1. Use Named Routes

```php
// Good
Route::get('products', [ProductController::class, 'index'])->name('products.index');

// Avoid
Route::get('products', [ProductController::class, 'index']);
```

### 2. Group Related Routes

```php
Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});
```

### 3. Use Resource Controllers

```php
// Instead of manually defining all CRUD routes
Route::resource('products', ProductController::class);
```

### 4. Apply Middleware Appropriately

```php
// Apply to group
Route::middleware('auth')->group(function () {
    // Protected routes
});

// Apply to specific route
Route::get('admin', [AdminController::class, 'index'])->middleware('auth');
```

### 5. Validate Parameters

```php
Route::get('products/{id}', [ProductController::class, 'show'])
    ->where('id', '[0-9]+');
```

### 6. Use Route Model Binding

```php
Route::get('products/{product}', [ProductController::class, 'show']);

public function show(Product $product)
{
    // $product is auto-loaded
}
```

### 7. Document Your Routes

```php
/**
 * Product Routes
 *
 * These routes handle product listing, viewing, and management.
 * All routes require 'has_website' middleware.
 */
Route::prefix('products')->group(function () {
    // Routes...
});
```

## Common Issues

### Route Not Found

**Problem:** Custom routes return 404

**Solution:**

```bash
# Clear route cache
php artisan route:clear

# Check if route exists
php artisan route:list --name=your_route
```

### Middleware Conflicts

**Problem:** Route middleware conflicts with WNCMS middleware

**Solution:**

```php
// Use except() to exclude specific middleware
Route::middleware(['web'])->withoutMiddleware(['full_page_cache'])->group(function () {
    // Routes without cache
});
```

### Localization Issues

**Problem:** Routes not working with locale prefix

**Solution:**

Ensure routes are in `custom_frontend.php` to inherit localization group.

### Permission Errors

**Problem:** Permission middleware not working

**Solution:**

```php
// Ensure permission exists in database
Permission::create(['name' => 'product_index']);

// Assign to role
$role->givePermissionTo('product_index');
```

## See Also

- [Web Routes](./web.md) - Main route entry point
- [Backend Routes](./backend.md) - Admin panel routes
- [Frontend Routes](./frontend.md) - Public routes
- [API Routes](./api.md) - RESTful API
- [Backend Controller](../controller/backend-controller.md) - Creating backend controllers
- [Frontend Controller](../controller/frontend-controller.md) - Creating frontend controllers
