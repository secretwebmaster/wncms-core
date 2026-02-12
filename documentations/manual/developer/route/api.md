# API Routes

## Overview

The `api.php` file defines all RESTful API endpoints for WNCMS. These routes provide programmatic access to resources like menus, pages, posts, tags, and system updates.

## File Location

```
wncms-core/routes/api.php
```

## Route Structure

### API Version Group

All API routes are versioned with `v1` prefix:

```php
Route::prefix('v1')->name('api.v1.')->group(function () {
    // API routes
});
```

**URL Structure:**

```
https://example.com/api/v1/posts
https://example.com/api/v1/menus
https://example.com/api/v1/tags
```

**Route Naming:**

All routes are prefixed with `api.v1.`:

```
api.v1.posts.index
api.v1.menus.show
api.v1.tags.store
```

## API Controllers

All API controllers are located in the `Api\V1` namespace:

```php
use Wncms\Http\Controllers\Api\V1\MenuController;
use Wncms\Http\Controllers\Api\V1\PageController;
use Wncms\Http\Controllers\Api\V1\PostController;
use Wncms\Http\Controllers\Api\V1\TagController;
use Wncms\Http\Controllers\Api\V1\UpdateController;
```

## Menus API

```php
Route::prefix('menus')->name('menus.')->controller(MenuController::class)->group(function () {
    Route::match(['GET', 'POST'], '/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('sync', 'sync')->name('sync');
    Route::match(['GET', 'POST'], '{id}', 'show')->name('show');
});
```

### Endpoints

| Method   | Endpoint              | Action | Description       |
| -------- | --------------------- | ------ | ----------------- |
| GET/POST | `/api/v1/menus`       | index  | List all menus    |
| POST     | `/api/v1/menus/store` | store  | Create a new menu |
| POST     | `/api/v1/menus/sync`  | sync   | Sync menu items   |
| GET/POST | `/api/v1/menus/{id}`  | show   | Get specific menu |

### Request Examples

**List Menus:**

```bash
curl -X GET https://example.com/api/v1/menus
```

**Get Specific Menu:**

```bash
curl -X GET https://example.com/api/v1/menus/1
```

**Create Menu:**

```bash
curl -X POST https://example.com/api/v1/menus/store \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Menu",
    "location": "header"
  }'
```

### Response Format

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

## Pages API

```php
Route::prefix('pages')->name('pages.')->controller(PageController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('{id}', 'show')->name('show');
});
```

### Endpoints

| Method | Endpoint              | Action | Description       |
| ------ | --------------------- | ------ | ----------------- |
| POST   | `/api/v1/pages`       | index  | List all pages    |
| POST   | `/api/v1/pages/store` | store  | Create a new page |
| POST   | `/api/v1/pages/{id}`  | show   | Get specific page |

### Request Examples

**List Pages:**

```bash
curl -X POST https://example.com/api/v1/pages \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1
  }'
```

**Create Page:**

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

## Posts API

```php
Route::prefix('posts')->name('posts.')->controller(PostController::class)->group(function () {
    Route::match(['GET', 'POST'], '/', 'index')->name('index');
    Route::post('store', 'store')->name('store');
    Route::post('update/{slug}', 'update')->name('update');
    Route::post('delete/{slug}', 'delete')->name('delete');
    Route::match(['GET', 'POST'], '{slug}', 'show')->name('show');
});
```

### Endpoints

| Method   | Endpoint                      | Action | Description       |
| -------- | ----------------------------- | ------ | ----------------- |
| GET/POST | `/api/v1/posts`               | index  | List all posts    |
| POST     | `/api/v1/posts/store`         | store  | Create a new post |
| POST     | `/api/v1/posts/update/{slug}` | update | Update a post     |
| POST     | `/api/v1/posts/delete/{slug}` | delete | Delete a post     |
| GET/POST | `/api/v1/posts/{slug}`        | show   | Get specific post |

### Request Examples

**List Posts:**

```bash
curl -X GET https://example.com/api/v1/posts
```

**Get Specific Post:**

```bash
curl -X GET https://example.com/api/v1/posts/my-article
```

**Create Post:**

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

**Update Post:**

```bash
curl -X POST https://example.com/api/v1/posts/update/my-article \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "content": "Updated content..."
  }'
```

**Delete Post:**

```bash
curl -X POST https://example.com/api/v1/posts/delete/my-article
```

### Response Format

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

## Tags API

```php
Route::prefix('tags')->name('tags.')->controller(TagController::class)->group(function () {
    Route::post('/', 'index')->name('index');
    Route::post('exist', 'exist')->name('exist');
    Route::post('store', 'store')->name('store');
});
```

### Endpoints

| Method | Endpoint             | Action | Description         |
| ------ | -------------------- | ------ | ------------------- |
| POST   | `/api/v1/tags`       | index  | List all tags       |
| POST   | `/api/v1/tags/exist` | exist  | Check if tag exists |
| POST   | `/api/v1/tags/store` | store  | Create a new tag    |

### Request Examples

**List Tags:**

```bash
curl -X POST https://example.com/api/v1/tags \
  -H "Content-Type: application/json" \
  -d '{
    "type": "category"
  }'
```

**Check Tag Existence:**

```bash
curl -X POST https://example.com/api/v1/tags/exist \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "laravel",
    "type": "tag"
  }'
```

**Create Tag:**

```bash
curl -X POST https://example.com/api/v1/tags/store \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laravel",
    "slug": "laravel",
    "type": "tag"
  }'
```

## Update API

```php
Route::prefix('update')->name('update.')->controller(UpdateController::class)->group(function () {
    Route::post('/', 'update')->name('run');
    Route::post('progress', 'progress')->name('progress');
});
```

### Endpoints

| Method | Endpoint                  | Action   | Description         |
| ------ | ------------------------- | -------- | ------------------- |
| POST   | `/api/v1/update`          | update   | Run system update   |
| POST   | `/api/v1/update/progress` | progress | Get update progress |

### Request Examples

**Run Update:**

```bash
curl -X POST https://example.com/api/v1/update \
  -H "Content-Type: application/json" \
  -d '{
    "version": "6.0.0"
  }'
```

**Check Progress:**

```bash
curl -X POST https://example.com/api/v1/update/progress
```

## Mixed GET/POST Methods

Many endpoints support both GET and POST methods:

```php
Route::match(['GET', 'POST'], '/', 'index');
```

**Why Mixed Methods?**

- **GET**: Simple queries, caching, bookmarkable URLs
- **POST**: Complex filters, large payloads, sensitive data

**Example:**

```bash
# Simple GET request
curl -X GET https://example.com/api/v1/posts

# Complex POST request with filters
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published",
    "tag": "laravel",
    "limit": 10
  }'
```

## Custom API Routes

Custom API routes can be added via `custom_api.php`:

```php
// Custom user-defined API routes
if (file_exists(base_path('routes/custom_api.php'))) {
    include base_path('routes/custom_api.php');
}
```

### Creating custom_api.php

Create the file in your project root:

```bash
touch routes/custom_api.php
```

**Example custom_api.php:**

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

## Authentication

### API Token

API routes typically use token authentication:

```php
// In API controller
public function index(Request $request)
{
    $user = $request->user();

    // User is authenticated via token
}
```

### Request with Token

```bash
curl -X GET https://example.com/api/v1/posts \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Generating Tokens

```php
// In controller or console
$token = $user->createToken('api-token')->plainTextToken;
```

## Rate Limiting

API routes are rate-limited by default (configured in `RouteServiceProvider`):

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
});
```

**Default Limit:**

- 60 requests per minute per user/IP

### Handling Rate Limit

**Response:**

```json
{
  "message": "Too Many Requests",
  "retry_after": 30
}
```

**HTTP Status:**

- `429 Too Many Requests`

## Error Handling

### Standard Error Response

```json
{
  "status": "error",
  "message": "Resource not found",
  "errors": {
    "slug": ["The post could not be found"]
  }
}
```

### HTTP Status Codes

| Code | Description       |
| ---- | ----------------- |
| 200  | Success           |
| 201  | Created           |
| 400  | Bad Request       |
| 401  | Unauthorized      |
| 403  | Forbidden         |
| 404  | Not Found         |
| 422  | Validation Error  |
| 429  | Too Many Requests |
| 500  | Server Error      |

### Controller Example

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

## Pagination

API endpoints support pagination:

```bash
curl -X GET "https://example.com/api/v1/posts?page=2&per_page=20"
```

**Response:**

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

## Filtering & Sorting

### Filtering

```bash
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published",
    "tag": "laravel",
    "date_from": "2024-01-01"
  }'
```

### Sorting

```bash
curl -X POST https://example.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{
    "sort_by": "created_at",
    "sort_order": "desc"
  }'
```

## Generating URLs

### In Controllers

```php
// Route to API endpoint
return redirect()->route('api.v1.posts.index');

// Generate API URL
$url = route('api.v1.posts.show', ['slug' => 'my-article']);
```

### In Code

```php
// API client
$response = Http::get(route('api.v1.posts.index'));

// With parameters
$response = Http::post(route('api.v1.posts.store'), [
    'title' => 'New Post',
    'content' => 'Content...'
]);
```

## CORS (Cross-Origin Resource Sharing)

Enable CORS for API routes:

```php
// In config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_headers' => ['*'],
];
```

## Best Practices

### 1. Use Versioning

Always version your API:

```php
Route::prefix('v1')->group(function () {
    // v1 routes
});

Route::prefix('v2')->group(function () {
    // v2 routes
});
```

### 2. Return Consistent Responses

```php
public function index()
{
    return response()->json([
        'status' => 'success',
        'data' => $posts
    ]);
}
```

### 3. Validate Input

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    // Create resource
}
```

### 4. Use Resource Classes

```php
use Wncms\Http\Resources\PostResource;

public function show($slug)
{
    $post = Post::where('slug', $slug)->firstOrFail();
    return new PostResource($post);
}
```

### 5. Document Your API

Provide clear documentation with:

- Endpoint descriptions
- Request/response examples
- Authentication requirements
- Rate limits
- Error codes

## Testing API Routes

### Using Postman

1. Create a new collection for WNCMS API
2. Add requests for each endpoint
3. Set up authentication tokens
4. Test with various parameters

### Using PHPUnit

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

## See Also

- [Backend Routes](./backend.md) - Admin panel routes
- [Frontend Routes](./frontend.md) - Public routes
- [API Resources](../resource/api-resource.md) - Resource transformations
- [Add Routes](./add-routes.md) - Creating custom routes
