# Create a Model

This guide shows two ways to scaffold a new model for WNCMS:

- Use Laravel’s built-in Artisan generators
- Use the WNCMS helper command `wncms:create-model` (recommended for backend CRUD)

The result is a local model that extends WNCMS’s [`BaseModel`](./base-model.md), plus an admin controller, migration, views, permissions, and routes.

## Before you start

- Ensure WNCMS is installed and autoloaded.
- Confirm `routes/custom_backend.php` is included by your `routes/web.php` (WNCMS does this by default).
- Decide your model’s singular and plural names. Example in this doc: `Article` / `articles`.

## Option A — Laravel built-in generators

### 1) Generate files

```bash
php artisan make:model Article -m
php artisan make:controller Backend/ArticleController --resource --model=Article
```

### 2) Update the model to extend `BaseModel`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;

class Article extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    /**
     * Model key (required in v6.x.x)
     */
    public static $modelKey = 'article';

    protected $guarded = [];

    /**
     * Translatable fields
     */
    protected $translatable = ['title', 'excerpt', 'content'];

    /**
     * Date casts
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Tag metadata (replaces old TAG_TYPES constant)
     */
    protected static array $tagMetas = [
        [
            'key'   => 'article_category',
            'short' => 'category',
            'route' => 'frontend.articles.tag',
        ],
        [
            'key'   => 'article_tag',
            'short' => 'tag',
            'route' => 'frontend.articles.tag',
        ],
    ];

    /**
     * Status options for backend UI
     */
    public const STATUSES = ['published', 'drafted'];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### 3) Make the backend controller extend WNCMS `BackendController`

```php
<?php

namespace App\Http\Controllers\Backend;

use Wncms\Http\Controllers\Backend\BackendController;

class ArticleController extends BackendController
{
    // Usually no need to override anything for basic CRUD.
    // You can customize policies, validation, columns, etc.
}
```

### 4) Create a migration

Edit the generated migration (example columns):

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('website_id')->nullable()->index(); // multisite support
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('content')->nullable();
    $table->string('status')->default('drafted');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

Run it:

```bash
php artisan migrate
```

### 5) Register routes (if not using the WNCMS command below)

Append to `routes/custom_backend.php`:

```php
<?php

use App\Http\Controllers\Backend\ArticleController;

Route::get('articles', [ArticleController::class, 'index'])->middleware('can:article_index')->name('articles.index');
Route::get('articles/create', [ArticleController::class, 'create'])->middleware('can:article_create')->name('articles.create');
Route::get('articles/create/{id}', [ArticleController::class, 'create'])->middleware('can:article_clone')->name('articles.clone');
Route::get('articles/{id}/edit', [ArticleController::class, 'edit'])->middleware('can:article_edit')->name('articles.edit');
Route::post('articles/store', [ArticleController::class, 'store'])->middleware('can:article_create')->name('articles.store');
Route::patch('articles/{id}', [ArticleController::class, 'update'])->middleware('can:article_edit')->name('articles.update');
Route::delete('articles/{id}', [ArticleController::class, 'destroy'])->middleware('can:article_delete')->name('articles.destroy');
Route::post('articles/bulk_delete', [ArticleController::class, 'bulk_delete'])->middleware('can:article_bulk_delete')->name('articles.bulk_delete');
```

### 6) Create backend views

WNCMS expects `resources/views/backend/{plural_snake}/`:

```
resources/views/backend/articles/
  ├─ index.blade.php
  ├─ create.blade.php
  └─ edit.blade.php
```

Name your routes and views using the plural snake case (e.g., `backend.articles.index`) to match WNCMS conventions.

### 7) Add permissions and menu (optional but recommended)

- Create permissions like `article_index`, `article_create`, etc. using your preferred method or a small seeder.
- Add a backend menu item pointing to `route('articles.index')`.

### 8) Model `ROUTES` supports 2 formats

WNCMS supports both a simple list and a detailed route definition for sidebar/menu permissions.

Simple format (permission is inferred as `{model}_{suffix}`):

```php
public const ROUTES = [
    'index',
    'summary',
];
```

Detailed format (you can override permission per route):

```php
public const ROUTES = [
    ['name' => 'index'],
    ['name' => 'summary', 'permission' => 'article_index'],
];
```

You can also use full route names in `name` (for example `articles.index`); otherwise WNCMS will prefix it automatically using the model table route prefix.

## Option B — WNCMS quick command (recommended)

Use the all-in-one WNCMS command to generate model, migration, controller, views, permissions, and routes. It also normalizes snake/camel names inside the generated controller.

```bash
php artisan wncms:create-model Article
```

What it does:

- `make:model Article`
  - Generated model now extends `Wncms\Models\BaseModel` and includes a `modelKey` fallback (auto-derived from class name when left empty)
- `make:migration create_articles_table`
- `make:controller Backend/ArticleController --resource --model=Article`

  - Rewrites view names (`backend.articles.*`), route names (`articles.*`), cache tags, and model word helpers to snake case

- `wncms:create-model-view article` to create `index/create/edit` views
- `wncms:create-model-permission article` to seed basic permissions
- Optionally appends a full routes block to `routes/custom_backend.php` and prepends the `use` statement for the controller

You will see a confirmation prompt before routes are appended.

Run migration:

```bash
php artisan migrate
```

## After scaffolding

### Customize your model (v6.x.x)

After using `wncms:create-model`, the generated model already uses `BaseModel` and has a safe `modelKey` fallback. Update it as needed for your feature requirements:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;

class Article extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    // Required: Model key
    public static $modelKey = 'article';

    protected $guarded = [];

    // Optional: Translatable fields
    protected $translatable = ['title', 'excerpt', 'content'];

    // Optional: Date casts
    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Optional: Tag metadata (v6.x.x style)
    protected static array $tagMetas = [
        [
            'key'   => 'article_category',
            'short' => 'category',
            'route' => 'frontend.articles.tag',
        ],
    ];

    // Optional: Status constants
    public const STATUSES = ['published', 'drafted'];

    // Optional: Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### Translation keys

If you use `BaseModel::getModelName()` or `wncms()->getModelWord('article')`, add translations:

```
resources/lang/en/wncms.php
  'word' => [
    'article' => 'Article',
    'articles' => 'Articles',
  ],
```

Or package-scoped keys if you later move this into a package.

### Tag types (optional)

If your model needs tags, declare them in `$tagMetas`:

```php
protected static array $tagMetas = [
    [
        'key'   => 'article_category',
        'short' => 'category',
        'route' => 'frontend.articles.tag',
    ],
    [
        'key'   => 'article_tag',
        'short' => 'tag',
        'route' => 'frontend.articles.tag',
    ],
];
```

WNCMS will auto-register them at model boot.

### Media & translations (optional)

To use media library or translations:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Translatable\Traits\HasTranslations;

class Article extends BaseModel implements HasMedia
{
    use InteractsWithMedia;
    use HasTranslations;

    protected $translatable = ['title', 'excerpt', 'content'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

## Common tips

- Keep controller names pluralized and placed under `App\Http\Controllers\Backend\`.
- Use plural snake for route and view names (e.g., `articles.index`) to match WNCMS helpers and stubs.
- Include `website_id` in migrations if your site uses multisite.
- Clear caches when adding menus or permissions if they’re cached.
- Follow WNCMS’s naming: columns like `status`, timestamps, and slugs make integration smoother.

## Quick checklist

- ✅ Model extends `Wncms\Models\BaseModel`
- ✅ Model defines `public static $modelKey`
- ✅ Model uses `$tagMetas` array (not old `TAG_TYPES`)
- ✅ Backend controller extends `Wncms\Http\Controllers\Backend\BackendController`
- ✅ Migration created and migrated with `website_id` column
- ✅ Views under `resources/views/backend/{plural}/`
- ✅ Routes registered to `routes/custom_backend.php`
- ✅ Permissions created and assigned
- ✅ Optional: translations, media collections, and translatable fields added
