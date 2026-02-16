# Backend Routes

## Overview

The `backend.php` file defines all admin panel routes for WNCMS. These routes are protected by authentication middleware and permission checks, providing a secure administration interface for managing content, users, settings, and system configurations.

## File Location

```
wncms-core/routes/backend.php
```

## Route Structure

### Main Route Group

All backend routes are wrapped in a group with common middleware:

```php
Route::prefix('panel')
    ->middleware(['auth', 'is_installed', 'has_website'])
    ->group(function () {
        // Backend routes
    });
```

**Middleware Breakdown:**

- **auth**: Requires user authentication
- **is_installed**: Ensures WNCMS is installed
- **has_website**: Verifies website exists (multi-site check)

**URL Structure:**

All backend routes are prefixed with `/panel`:

```
https://example.com/panel/dashboard
https://example.com/panel/posts
https://example.com/panel/settings
```

## Permission System

### Permission Middleware

Most routes use `can:permission_name` middleware for authorization:

```php
Route::get('posts', [PostController::class, 'index'])
    ->middleware('can:post_index')
    ->name('posts.index');
```

**Common Permissions:**

- `{model}_index`: View list
- `{model}_show`: View single record
- `{model}_create`: Create new record
- `{model}_edit`: Edit existing record
- `{model}_delete`: Delete record
- `{model}_bulk_delete`: Bulk delete
- `{model}_clone`: Clone/duplicate record

### Permission Naming Convention

```
{model}_{action}

Examples:
- post_index
- user_create
- page_edit
- menu_delete
- setting_update
```

## Route Groups

### Dashboard

```php
Route::controller(DashboardController::class)->group(function () {
    Route::get('dashboard', 'show_dashboard')->name('dashboard');
    Route::post('switch_website', 'switch_website')->name('dashboard.switch_website');
});
```

**Routes:**

- `GET /panel/dashboard` - Main dashboard
- `POST /panel/dashboard/switch_website` - Switch active website (multi-site)

### Posts

```php
Route::prefix('posts')->controller(PostController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:post_index')->name('posts.index');
    Route::get('/create', 'create')->middleware('can:post_create')->name('posts.create');
    Route::get('/clone/{id}', 'create')->middleware('can:post_clone')->name('posts.clone');
    Route::get('/{id}/edit', 'edit')->middleware('can:post_edit')->name('posts.edit');
    Route::get('/{id}', 'show')->middleware('can:post_show')->name('posts.show');
    Route::post('/store', 'store')->middleware('can:post_create')->name('posts.store');
    Route::patch('/{id}', 'update')->middleware('can:post_edit')->name('posts.update');
    Route::delete('/{id}', 'destroy')->middleware('can:post_delete')->name('posts.destroy');
    Route::post('/bulk_delete', 'bulk_delete')->middleware('can:post_bulk_delete')->name('posts.bulk_delete');
});
```

**Standard CRUD Pattern:**

| Method | URL                  | Action           | Permission         |
| ------ | -------------------- | ---------------- | ------------------ |
| GET    | `/posts`             | List all         | `post_index`       |
| GET    | `/posts/create`      | Show create form | `post_create`      |
| POST   | `/posts/store`       | Save new post    | `post_create`      |
| GET    | `/posts/{id}`        | View single      | `post_show`        |
| GET    | `/posts/{id}/edit`   | Show edit form   | `post_edit`        |
| PATCH  | `/posts/{id}`        | Update post      | `post_edit`        |
| DELETE | `/posts/{id}`        | Delete post      | `post_delete`      |
| POST   | `/posts/bulk_delete` | Bulk delete      | `post_bulk_delete` |
| GET    | `/posts/clone/{id}`  | Clone post       | `post_clone`       |

### Pages

```php
Route::prefix('pages')->group(function () {

    // Page Builder
    Route::prefix('{page}/builder')->controller(PageBuilderController::class)->group(function () {
        Route::get('/editor', 'editor')->middleware('can:page_edit')->name('pages.builder.editor');
        Route::get('/load', 'load')->middleware('can:page_edit')->name('pages.builder.load');
        Route::post('/save', 'save')->middleware('can:page_edit')->name('pages.builder.save');
    });

    // Page Management
    Route::controller(PageController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:page_index')->name('pages.index');
        Route::get('/create', 'create')->middleware('can:page_create')->name('pages.create');
        Route::post('/store', 'store')->middleware('can:page_create')->name('pages.store');
        Route::get('/{id}/edit', 'edit')->middleware('can:page_edit')->name('pages.edit');
        Route::patch('/{id}', 'update')->middleware('can:page_edit')->name('pages.update');
        Route::delete('/{id}', 'destroy')->middleware('can:page_delete')->name('pages.destroy');
    });
});
```

### Menus

```php
Route::prefix('menus')->controller(MenuController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:menu_index')->name('menus.index');
    Route::get('/create', 'create')->middleware('can:menu_create')->name('menus.create');
    Route::get('/{id}/edit', 'edit')->middleware('can:menu_edit')->name('menus.edit');
    Route::post('/store', 'store')->middleware('can:menu_create')->name('menus.store');
    Route::patch('/{id}', 'update')->middleware('can:menu_edit')->name('menus.update');
    Route::delete('/{id}', 'destroy')->middleware('can:menu_delete')->name('menus.destroy');
    Route::post('/clone', 'clone')->middleware('can:menu_create')->name('menus.clone');

    // AJAX endpoints
    Route::post('/get_menu_item', 'get_menu_item')->middleware('can:menu_edit')->name('menus.get_menu_item');
    Route::post('/edit_menu_item', 'edit_menu_item')->middleware('can:menu_edit')->name('menus.edit_menu_item');
});
```

### Users

```php
Route::prefix('users')->controller(UserController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:user_index')->name('users.index');
    Route::get('/create', 'create')->middleware('can:user_create')->name('users.create');
    Route::post('/store', 'store')->middleware('can:user_create')->name('users.store');
    Route::get('/{id}/edit', 'edit')->middleware('can:user_edit')->name('users.edit');
    Route::patch('/{id}', 'update')->middleware('can:user_edit')->name('users.update');
    Route::delete('/{id}', 'destroy')->middleware('can:user_delete')->name('users.destroy');
    Route::post('/bulk_delete', 'bulk_delete')->middleware('can:user_bulk_delete')->name('users.bulk_delete');
});
```

### Roles & Permissions

```php
// Roles
Route::prefix('roles')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:role_index')->name('roles.index');
    Route::get('/create', 'create')->middleware('can:role_create')->name('roles.create');
    Route::post('/store', 'store')->middleware('can:role_create')->name('roles.store');
    Route::get('/{id}/edit', 'edit')->middleware('can:role_edit')->name('roles.edit');
    Route::patch('/{id}', 'update')->middleware('can:role_edit')->name('roles.update');
    Route::delete('/{id}', 'destroy')->middleware('can:role_delete')->name('roles.destroy');
});

// Permissions
Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:permission_index')->name('permissions.index');
    Route::post('/store', 'store')->middleware('can:permission_create')->name('permissions.store');
    Route::patch('/{id}', 'update')->middleware('can:permission_edit')->name('permissions.update');
    Route::delete('/{id}', 'destroy')->middleware('can:permission_delete')->name('permissions.destroy');
});
```

### Settings

```php
Route::prefix('settings')->controller(SettingController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:setting_index')->name('settings.index');
    Route::get('/{tab?}', 'show')->middleware('can:setting_show')->name('settings.show');
    Route::post('/store', 'store')->middleware('can:setting_edit')->name('settings.store');
    Route::post('/update', 'update')->middleware('can:setting_edit')->name('settings.update');
});
```

### Websites

```php
Route::prefix('websites')->controller(WebsiteController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:website_index')->name('websites.index');
    Route::get('/create', 'create')->middleware('can:website_create')->name('websites.create');
    Route::post('/store', 'store')->middleware('can:website_create')->name('websites.store');
    Route::get('/{id}/edit', 'edit')->middleware('can:website_edit')->name('websites.edit');
    Route::patch('/{id}', 'update')->middleware('can:website_edit')->name('websites.update');
    Route::delete('/{id}', 'destroy')->middleware('can:website_delete')->name('websites.destroy');
});
```

### Tags

```php
Route::prefix('tags')->controller(TagController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:tag_index')->name('tags.index');
    Route::get('/create', 'create')->middleware('can:tag_create')->name('tags.create');
    Route::post('/store', 'store')->middleware('can:tag_create')->name('tags.store');
    Route::get('/{id}/edit', 'edit')->middleware('can:tag_edit')->name('tags.edit');
    Route::patch('/{id}', 'update')->middleware('can:tag_edit')->name('tags.update');
    Route::delete('/{id}', 'destroy')->middleware('can:tag_delete')->name('tags.destroy');
    Route::post('/bulk_delete', 'bulk_delete')->middleware('can:tag_bulk_delete')->name('tags.bulk_delete');
});
```

Tag list filter behavior:

- `GET /panel/tags?type=post_category`: shows only that tag type.
- `GET /panel/tags?type=all`: shows all tag types (does not apply `where type = all`).
- If `type` is missing, backend redirects with default `type=post_category`.
- In tag create/edit, `parent_id` uses Tagify (single select, `maxTags=1`) and loads **all tags** of the selected type as parent candidates.

### Themes

```php
Route::prefix('themes')->controller(ThemeController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:theme_index')->name('themes.index');
    Route::get('/{theme}/options', 'options')->middleware('can:theme_edit')->name('themes.options');
    Route::post('/{theme}/update_options', 'update_options')->middleware('can:theme_edit')->name('themes.update_options');
    Route::post('/{theme}/activate', 'activate')->middleware('can:theme_edit')->name('themes.activate');
    Route::post('/{theme}/reset', 'reset')->middleware('can:theme_edit')->name('themes.reset');
});
```

### Cache

```php
Route::prefix('cache')->controller(CacheController::class)->group(function () {
    Route::post('/flush', 'flush')->middleware('can:cache_flush')->name('cache.flush');
    Route::post('/flush/{tag}', 'flush')->middleware('can:cache_flush')->name('cache.flush.tag');
    Route::post('/clear/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear');
    Route::post('/clear/{tag}/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear.tag');
});
```

### Uploads

```php
Route::prefix('uploads')->controller(UploadController::class)->group(function () {
    Route::post('/image', 'image')->name('uploads.image');
    Route::post('/file', 'file')->name('uploads.file');
    Route::post('/media', 'media')->name('uploads.media');
    Route::delete('/{id}', 'destroy')->name('uploads.destroy');
});
```

## Common Route Patterns

### Resource Routes

WNCMS follows Laravel's resourceful routing pattern:

```php
// List all
GET     /panel/posts                → PostController@index

// Create form
GET     /panel/posts/create         → PostController@create

// Store new
POST    /panel/posts/store          → PostController@store

// View single
GET     /panel/posts/{id}           → PostController@show

// Edit form
GET     /panel/posts/{id}/edit      → PostController@edit

// Update
PATCH   /panel/posts/{id}           → PostController@update

// Delete
DELETE  /panel/posts/{id}           → PostController@destroy
```

### Bulk Operations

```php
// Bulk delete
POST /panel/posts/bulk_delete → PostController@bulk_delete

// Bulk update
POST /panel/posts/bulk_update → PostController@bulk_update

// Bulk sync tags
POST /panel/posts/bulk_sync_tags → PostController@bulk_sync_tags
```

### Clone/Duplicate

```php
GET /panel/posts/clone/{id} → PostController@create (with ID)
```

## Route Naming Convention

All backend routes use the pattern: `{resource}.{action}`

```php
// Examples
posts.index
posts.create
posts.store
posts.edit
posts.update
posts.destroy
posts.bulk_delete
```

## Generating URLs

### In Controllers

```php
// Redirect to index
return redirect()->route('posts.index');

// Redirect to edit with ID
return redirect()->route('posts.edit', $post->id);

// Redirect with success message
return redirect()->route('posts.index')
    ->with('success', 'Post created successfully');
```

### In Views

```blade
{{-- Link to create --}}
<a href="{{ route('posts.create') }}">Create Post</a>

{{-- Link to edit --}}
<a href="{{ route('posts.edit', $post->id) }}">Edit</a>

{{-- Form action --}}
<form action="{{ route('posts.store') }}" method="POST">
    @csrf
    <!-- Form fields -->
</form>

{{-- Update form --}}
<form action="{{ route('posts.update', $post->id) }}" method="POST">
    @csrf
    @method('PATCH')
    <!-- Form fields -->
</form>
```

## Middleware Groups

### Auth Required

All backend routes require authentication:

```php
Route::middleware('auth')->group(function () {
    // Routes
});
```

### Installation Check

Ensures WNCMS is installed:

```php
Route::middleware('is_installed')->group(function () {
    // Routes
});
```

### Website Check

Verifies active website exists (multi-site):

```php
Route::middleware('has_website')->group(function () {
    // Routes
});
```

## Authorization

### Gate Checks

```php
// In controller
public function edit($id)
{
    $post = Post::findOrFail($id);

    $this->authorize('post_edit');

    return view('backend.posts.edit', compact('post'));
}
```

### Middleware Checks

```php
Route::get('posts/{id}/edit', [PostController::class, 'edit'])
    ->middleware('can:post_edit');
```

### Policy Checks

```php
// In controller
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);

    // Update logic
}
```

## AJAX Routes

### Returning JSON

```php
// In controller
public function get_menu_item(Request $request)
{
    $menuItem = MenuItem::find($request->id);

    return response()->json([
        'success' => true,
        'data' => $menuItem,
    ]);
}
```

### AJAX Form Submission

```blade
<script>
$('form#menu-item').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: '{{ route("menus.edit_menu_item") }}',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            alert('Success!');
        }
    });
});
</script>
```

## Best Practices

### 1. Use Named Routes

Always name routes for maintainability:

```php
Route::get('posts', [PostController::class, 'index'])
    ->name('posts.index');
```

### 2. Apply Permissions

Protect routes with appropriate permissions:

```php
Route::get('posts', [PostController::class, 'index'])
    ->middleware('can:post_index');
```

### 3. Group Related Routes

Use prefixes and groups for organization:

```php
Route::prefix('posts')->group(function () {
    // All post routes
});
```

### 4. Use Controller Actions

Avoid closures for cacheability:

```php
// Good
Route::get('posts', [PostController::class, 'index']);

// Avoid (can't cache)
Route::get('posts', function () {
    return view('posts.index');
});
```

### 5. Validate Route Parameters

Use route constraints:

```php
Route::get('posts/{id}/edit', [PostController::class, 'edit'])
    ->where('id', '[0-9]+');
```

## See Also

- [Frontend Routes](./frontend.md) - Public routes
- [API Routes](./api.md) - API endpoints
- [Add Routes](./add-routes.md) - Creating custom routes
- [Backend Controller](../controller/backend-controller.md)
