# 後台路由

## 概述

`backend.php` 檔案定義了 WNCMS 的所有管理面板路由。這些路由受到身份驗證中介層和權限檢查的保護，提供安全的管理介面來管理內容、使用者、設定和系統配置。

## 檔案位置

```
wncms-core/routes/backend.php
```

## 路由結構

### 主路由群組

所有後台路由都包裝在具有共同中介層的群組中：

```php
Route::prefix('panel')
    ->middleware(['auth', 'is_installed', 'has_website'])
    ->group(function () {
        // Backend routes
    });
```

**中介層說明：**

- **auth**: 需要使用者身份驗證
- **is_installed**: 確保 WNCMS 已安裝
- **has_website**: 驗證網站是否存在（多站點檢查）

**URL 結構：**

所有後台路由都以 `/panel` 為前綴：

```
https://example.com/panel/dashboard
https://example.com/panel/posts
https://example.com/panel/settings
```

## 權限系統

### 權限中介層

大多數路由使用 `can:permission_name` 中介層進行授權：

```php
Route::get('posts', [PostController::class, 'index'])
    ->middleware('can:post_index')
    ->name('posts.index');
```

**常見權限：**

- `{model}_index`: 查看列表
- `{model}_show`: 查看單一記錄
- `{model}_create`: 建立新記錄
- `{model}_edit`: 編輯現有記錄
- `{model}_delete`: 刪除記錄
- `{model}_bulk_delete`: 批量刪除
- `{model}_clone`: 複製/複製記錄

### 權限命名慣例

```
{model}_{action}

範例：
- post_index
- user_create
- page_edit
- menu_delete
- setting_update
```

## 路由群組

### 儀表板

```php
Route::controller(DashboardController::class)->group(function () {
    Route::get('dashboard', 'show_dashboard')->name('dashboard');
    Route::post('switch_website', 'switch_website')->name('dashboard.switch_website');
});
```

**路由：**

- `GET /panel/dashboard` - 主儀表板
- `POST /panel/dashboard/switch_website` - 切換活動網站（多站點）

### 文章

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

**標準 CRUD 模式：**

| 方法   | URL                  | 動作         | 權限               |
| ------ | -------------------- | ------------ | ------------------ |
| GET    | `/posts`             | 列出全部     | `post_index`       |
| GET    | `/posts/create`      | 顯示建立表單 | `post_create`      |
| POST   | `/posts/store`       | 儲存新文章   | `post_create`      |
| GET    | `/posts/{id}`        | 查看單一     | `post_show`        |
| GET    | `/posts/{id}/edit`   | 顯示編輯表單 | `post_edit`        |
| PATCH  | `/posts/{id}`        | 更新文章     | `post_edit`        |
| DELETE | `/posts/{id}`        | 刪除文章     | `post_delete`      |
| POST   | `/posts/bulk_delete` | 批量刪除     | `post_bulk_delete` |
| GET    | `/posts/clone/{id}`  | 複製文章     | `post_clone`       |

### 頁面

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

### 選單

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

### 使用者

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

### 角色和權限

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

### 設定

```php
Route::prefix('settings')->controller(SettingController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:setting_index')->name('settings.index');
    Route::get('/{tab?}', 'show')->middleware('can:setting_show')->name('settings.show');
    Route::post('/store', 'store')->middleware('can:setting_edit')->name('settings.store');
    Route::post('/update', 'update')->middleware('can:setting_edit')->name('settings.update');
});
```

### 網站

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

### 標籤

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

標籤列表篩選行為：

- `GET /panel/tags?type=post_category`：只顯示該標籤類型。
- `GET /panel/tags?type=all`：顯示所有標籤類型（不會套用 `where type = all`）。
- 當 `type` 為空時，後端會重新導向並預設使用 `type=post_category`。

### 主題

```php
Route::prefix('themes')->controller(ThemeController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:theme_index')->name('themes.index');
    Route::get('/{theme}/options', 'options')->middleware('can:theme_edit')->name('themes.options');
    Route::post('/{theme}/update_options', 'update_options')->middleware('can:theme_edit')->name('themes.update_options');
    Route::post('/{theme}/activate', 'activate')->middleware('can:theme_edit')->name('themes.activate');
    Route::post('/{theme}/reset', 'reset')->middleware('can:theme_edit')->name('themes.reset');
});
```

### 快取

```php
Route::prefix('cache')->controller(CacheController::class)->group(function () {
    Route::post('/flush', 'flush')->middleware('can:cache_flush')->name('cache.flush');
    Route::post('/flush/{tag}', 'flush')->middleware('can:cache_flush')->name('cache.flush.tag');
    Route::post('/clear/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear');
    Route::post('/clear/{tag}/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear.tag');
});
```

### 上傳

```php
Route::prefix('uploads')->controller(UploadController::class)->group(function () {
    Route::post('/image', 'image')->name('uploads.image');
    Route::post('/file', 'file')->name('uploads.file');
    Route::post('/media', 'media')->name('uploads.media');
    Route::delete('/{id}', 'destroy')->name('uploads.destroy');
});
```

## 常見路由模式

### 資源路由

WNCMS 遵循 Laravel 的資源路由模式：

```php
// 列出全部
GET     /panel/posts                → PostController@index

// 建立表單
GET     /panel/posts/create         → PostController@create

// 儲存新的
POST    /panel/posts/store          → PostController@store

// 查看單一
GET     /panel/posts/{id}           → PostController@show

// 編輯表單
GET     /panel/posts/{id}/edit      → PostController@edit

// 更新
PATCH   /panel/posts/{id}           → PostController@update

// 刪除
DELETE  /panel/posts/{id}           → PostController@destroy
```

### 批量操作

```php
// 批量刪除
POST /panel/posts/bulk_delete → PostController@bulk_delete

// 批量更新
POST /panel/posts/bulk_update → PostController@bulk_update

// 批量同步標籤
POST /panel/posts/bulk_sync_tags → PostController@bulk_sync_tags
```

### 複製/複製

```php
GET /panel/posts/clone/{id} → PostController@create (with ID)
```

## 路由命名慣例

所有後台路由都使用模式：`{resource}.{action}`

```php
// 範例
posts.index
posts.create
posts.store
posts.edit
posts.update
posts.destroy
posts.bulk_delete
```

## 產生 URL

### 在控制器中

```php
// 重定向到索引
return redirect()->route('posts.index');

// 重定向到編輯並帶 ID
return redirect()->route('posts.edit', $post->id);

// 重定向並帶成功訊息
return redirect()->route('posts.index')
    ->with('success', 'Post created successfully');
```

### 在視圖中

```blade
{{-- 建立連結 --}}
<a href="{{ route('posts.create') }}">Create Post</a>

{{-- 編輯連結 --}}
<a href="{{ route('posts.edit', $post->id) }}">Edit</a>

{{-- 表單動作 --}}
<form action="{{ route('posts.store') }}" method="POST">
    @csrf
    <!-- Form fields -->
</form>

{{-- 更新表單 --}}
<form action="{{ route('posts.update', $post->id) }}" method="POST">
    @csrf
    @method('PATCH')
    <!-- Form fields -->
</form>
```

## 中介層群組

### 需要身份驗證

所有後台路由都需要身份驗證：

```php
Route::middleware('auth')->group(function () {
    // Routes
});
```

### 安裝檢查

確保 WNCMS 已安裝：

```php
Route::middleware('is_installed')->group(function () {
    // Routes
});
```

### 網站檢查

驗證活動網站是否存在（多站點）：

```php
Route::middleware('has_website')->group(function () {
    // Routes
});
```

## 授權

### 閘道檢查

```php
// 在控制器中
public function edit($id)
{
    $post = Post::findOrFail($id);

    $this->authorize('post_edit');

    return view('backend.posts.edit', compact('post'));
}
```

### 中介層檢查

```php
Route::get('posts/{id}/edit', [PostController::class, 'edit'])
    ->middleware('can:post_edit');
```

### 政策檢查

```php
// 在控制器中
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);

    // 更新邏輯
}
```

## AJAX 路由

### 回傳 JSON

```php
// 在控制器中
public function get_menu_item(Request $request)
{
    $menuItem = MenuItem::find($request->id);

    return response()->json([
        'success' => true,
        'data' => $menuItem,
    ]);
}
```

### AJAX 表單提交

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

## 最佳實踐

### 1. 使用命名路由

始終為路由命名以提高可維護性：

```php
Route::get('posts', [PostController::class, 'index'])
    ->name('posts.index');
```

### 2. 應用權限

使用適當的權限保護路由：

```php
Route::get('posts', [PostController::class, 'index'])
    ->middleware('can:post_index');
```

### 3. 分組相關路由

使用前綴和群組進行組織：

```php
Route::prefix('posts')->group(function () {
    // 所有文章路由
});
```

### 4. 使用控制器動作

避免使用閉包以提高可快取性：

```php
// 好的
Route::get('posts', [PostController::class, 'index']);

// 避免（無法快取）
Route::get('posts', function () {
    return view('posts.index');
});
```

### 5. 驗證路由參數

使用路由約束：

```php
Route::get('posts/{id}/edit', [PostController::class, 'edit'])
    ->where('id', '[0-9]+');
```

## 另見

- [Frontend Routes](./frontend.md) - 公開路由
- [API Routes](./api.md) - API 端點
- [Add Routes](./add-routes.md) - 建立自訂路由
- [Backend Controller](../controller/backend-controller.md)
