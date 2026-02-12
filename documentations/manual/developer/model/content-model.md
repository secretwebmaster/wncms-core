# Content Model

A **content model** in WNCMS represents a data entity that stores user-generated or website-managed content — such as **Posts**, **Pages**, **Links**, or custom content types.

Each content model extends the [`BaseModel`](./base-model) class to inherit WNCMS-wide features like multi-site support, tag handling, translatable attributes, and media management.

:::tip WNCMS v6.x.x
Content models in v6.x.x require `$modelKey` and use `$tagMetas` instead of the old `TAG_TYPES` constant.
:::

## Typical Structure

A standard content model in WNCMS v6.x.x typically includes:

### Core Traits

- `HasFactory` — Laravel factory support for testing/seeding
- `HasTranslations` — Multilingual field support via `wncms-translatable`
- `InteractsWithMedia` — Image/file uploads via Spatie Media Library
- `SoftDeletes` — Soft delete functionality (optional)

### Required Properties

- `public static $modelKey` — Unique model identifier (**required**)
- `protected static array $tagMetas` — Tag type definitions

### Optional Features

- Constants: `ICONS`, `STATUSES`, `SORTS`, `VISIBILITIES`
- Media collections registration
- Custom accessors/mutators
- Relationships to other models

## Real-World Example: Link Model

Here's the actual Link model from WNCMS Core v6.x.x:

```php
<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;

class Link extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Properties
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'link';

    protected $guarded = [];

    protected $translatable = ['name', 'description', 'slogan'];

    protected $casts = [
        'expired_at' => 'datetime',
        'hit_at' => 'datetime',
    ];

    protected static array $tagMetas = [
        [
            'key'   => 'link_category',
            'short' => 'category',
            'route' => '',
        ],
        [
            'key'   => 'link_tag',
            'short' => 'tag',
            'route' => '',
        ],
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-link'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Media Collections
     * ----------------------------------------------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('link_thumbnail')->singleFile();
        $this->addMediaCollection('link_icon')->singleFile();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('link_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }

    public function getIconAttribute()
    {
        $media = $this->getMedia('link_icon')->first();
        if ($media) return $media->getUrl();
    }

    public function getImageAttribute()
    {
        return $this->icon ?? $this->thumbnail;
    }
}
```

## Advanced Example: Post Model

The Post model demonstrates more advanced features:

```php
<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;
use Wncms\Traits\HasComments;
use Wncms\Interfaces\ApiModelInterface;
use Wncms\Traits\HasApi;

class Post extends BaseModel implements HasMedia, ApiModelInterface
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use HasTranslations;
    use HasComments;
    use HasApi;

    public static $modelKey = 'post';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime'
    ];

    protected static array $tagMetas = [
        [
            'key'   => 'post_category',
            'short' => 'category',
            'route' => 'frontend.posts.tag',
        ],
        [
            'key'   => 'post_tag',
            'short' => 'tag',
            'route' => 'frontend.posts.tag',
        ],
    ];

    protected $translatable = ['title', 'excerpt', 'keywords', 'content', 'label'];

protected static bool $hasApi = true;

protected static array $apiRoutes = [
    [
        'name' => 'api.v1.posts.index',
        'key' => 'wncms_api_post_index',
        'action' => 'index',
    ],
    [
        'name' => 'api.v1.posts.show',
        'key' => 'wncms_api_post_show',
        'action' => 'show',
    ],
    [
        'name' => 'api.v1.posts.store',
        'key' => 'wncms_api_post_store',
        'action' => 'store',
    ],
    [
        'name' => 'api.v1.posts.update',
        'key' => 'wncms_api_post_update',
        'action' => 'update',
    ],
    [
        'name' => 'api.v1.posts.delete',
        'key' => 'wncms_api_post_delete',
        'action' => 'delete',
    ],
];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-pencil'
    ];

    public const SORTS = [
        'sort',
        'view_today',
        'view_yesterday',
        'view_week',
        'view_month',
        'view_total',
        'published_at',
        'expired_at',
        'created_at',
        'updated_at',
    ];

    public const STATUSES = [
        'published',
        'drafted',
        'trashed',
    ];

    public const VISIBILITIES = [
        'public',
        'member',
        'admin',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_thumbnail')->singleFile();
        $this->addMediaCollection('post_content');
    }

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('post_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }
}
```

## Key Components Explained

### 1. Model Key (Required)

```php
public static $modelKey = 'post';
```

Every content model **must** define a unique model key. This is used by WNCMS for:

- Backend route generation
- Permission system
- Model registry
- Translation lookups

### 2. Tag Metadata

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // Unique tag type identifier
        'short' => 'category',       // Short name for UI
        'route' => 'frontend.posts.tag', // Frontend route name
    ],
];
```

Tag metadata defines what types of tags this model supports. The system automatically registers these tag types during boot.

### 3. API Route Metadata (Required for API Settings Integration)

If a model should expose configurable API actions in **System Settings -> API**, define all of the following together:

- Implement `Wncms\Interfaces\ApiModelInterface`
- Use `Wncms\Traits\HasApi`
- Set `protected static bool $hasApi = true;`
- Define `protected static array $apiRoutes = [...]`

Each route item must include:

- `name`: Laravel route name in `routes/api.php`
- `key`: system setting key (for example `wncms_api_post_index`)
- `action`: action name used by settings table grouping (`index`, `show`, `store`, `update`, `delete`, or custom)

Optional but recommended:

- `package_id`: package namespace used by API settings label translation (for example `wncms`, `my-package`)

Example:

```php
protected static array $apiRoutes = [
    [
        'name' => 'api.v1.posts.index',
        'key' => 'wncms_api_post_index',
        'action' => 'index',
        'package_id' => 'wncms',
    ],
];
```

`HasApi::getApiRoutes()` now auto-fills `package_id` from the model package when missing, so labels in **System Settings -> API** resolve against the correct translation namespace.

Without `$apiRoutes`, the API auth/enable toggles for that model will not appear in backend settings.

### 3. Translatable Fields

```php
protected $translatable = ['title', 'excerpt', 'content'];
```

Fields listed here can be translated into multiple languages using the `wncms-translatable` package.

### 4. Media Collections

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('post_thumbnail')->singleFile();
    $this->addMediaCollection('post_content'); // Multiple files allowed
}
```

Define media collections for file uploads. Use `singleFile()` for collections that should only have one file.

### 5. Custom Accessors

```php
public function getThumbnailAttribute()
{
    $media = $this->getMedia('post_thumbnail')->first();
    if ($media) return $media->getUrl();
    return $this->external_thumbnail;
}
```

Laravel accessors provide computed attributes. This example returns the uploaded thumbnail URL or falls back to an external URL.

### 6. Constants for Backend UI

```php
public const STATUSES = ['published', 'drafted', 'trashed'];
public const VISIBILITIES = ['public', 'member', 'admin'];
public const ICONS = ['fontawesome' => 'fa-solid fa-pencil'];
```

These constants are used by backend controllers and views:

- `STATUSES` — Available status options
- `VISIBILITIES` — Who can view the content
- `ICONS` — Icon displayed in admin panel
- `SORTS` — Available sorting options

## Creating Your Own Content Model

### Step 1: Generate the Model

```bash
php artisan make:model Article -m
```

### Step 2: Extend BaseModel

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

    public static $modelKey = 'article';

    protected $guarded = [];

    protected $translatable = ['title', 'content', 'excerpt'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static array $tagMetas = [
        [
            'key'   => 'article_category',
            'short' => 'category',
            'route' => 'frontend.articles.tag',
        ],
    ];

    public const STATUSES = ['published', 'drafted'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### Step 3: Create Migration

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('website_id')->nullable()->index();
    $table->string('title');
    $table->text('excerpt')->nullable();
    $table->longText('content')->nullable();
    $table->string('status')->default('drafted');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

### Step 4: Run Migration

```bash
php artisan migrate
```

## Best Practices

### ✅ Do:

- Always define `$modelKey`
- Use `$tagMetas` for tag types (not old `TAG_TYPES`)
- Implement `HasMedia` interface if using media
- Use `$translatable` for multilingual fields
- Define meaningful `STATUSES` and `VISIBILITIES`
- Add `website_id` column for multi-site support
- Use `$casts` for datetime fields

### ❌ Don't:

- Don't use old `TAG_TYPES` constant (deprecated in v6.x.x)
- Don't forget to implement `HasMedia` when using `InteractsWithMedia`
- Don't skip `registerMediaCollections()` if using media
- Don't use `$fillable` (use `$guarded = []` instead)

## Common Traits for Content Models

| Trait                | Purpose             | When to Use                           |
| -------------------- | ------------------- | ------------------------------------- |
| `HasFactory`         | Factory support     | Always (for seeding/testing)          |
| `HasTranslations`    | Multilingual fields | When you need translations            |
| `InteractsWithMedia` | File uploads        | When handling images/files            |
| `SoftDeletes`        | Soft delete         | When you want to keep deleted records |
| `HasComments`        | Comment system      | For user-generated comments           |
| `HasApi`             | API endpoints       | When exposing via API                 |
| `HasOptions`         | Dynamic options     | For flexible key-value settings       |

## Summary

Content models in WNCMS v6.x.x:

- ✅ Extend `BaseModel`
- ✅ Require `$modelKey` definition
- ✅ Use `$tagMetas` for tag types
- ✅ Support translations, media, and multi-site
- ✅ Provide consistent backend UI via constants
- ✅ Can be easily created and customized

For step-by-step model creation, see [Create a Model](./create-a-model).
