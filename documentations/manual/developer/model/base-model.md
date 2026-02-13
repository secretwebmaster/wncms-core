# Base Model

`BaseModel` is the foundation class for all models in WNCMS Core v6.x.x.  
It extends Laravel's native `Illuminate\Database\Eloquent\Model` and implements `BaseModelInterface`, providing essential features like **multi-site support**, **tag handling**, **dynamic model naming**, and **attribute event hooks**.

:::tip Requirements

- PHP 8.2+
- Laravel 12.0+
- WNCMS Core 6.x.x
  :::

## Location

```php
Wncms\Models\BaseModel
```

## Purpose

All WNCMS models (and your custom models) should extend this class to inherit:

- Multi-site awareness through `HasMultisite` trait
- Tag relationship and filtering via `HasTags` trait
- Required model key registration (`$modelKey`)
- Package identification (`$packageId`)
- Event dispatching when accessing attributes
- Translatable model name resolution
- Tag metadata definition support

## Key Properties

### Required Properties

```php
abstract class BaseModel extends Model implements BaseModelInterface
{
    use HasMultisite;
    use HasTags;

    // Package identifier (default: 'wncms')
    public static $packageId = 'wncms';

    // Required: unique model identifier
    public static $modelKey = '';

    // Tag metadata definitions (optional)
    protected static array $tagMetas = [];
}
```

:::warning Important
Every model extending `BaseModel` **must** define `public static $modelKey`. The `booted()` method will throw an exception if this is not set.
:::

### Example Model Setup

```php
namespace App\Models;

use Wncms\Models\BaseModel;

class Product extends BaseModel
{
    public static $modelKey = 'product';

    protected static array $tagMetas = [
        [
            'key'   => 'product_category',
            'short' => 'category',
            'route' => 'frontend.products.tag',
        ],
        [
            'key'   => 'product_tag',
            'short' => 'tag',
            'route' => 'frontend.products.tag',
        ],
    ];
}
```

## Traits Used

### HasMultisite

Adds support for multi-website data isolation.  
Each record can belong to a specific website when multi-site mode is enabled via the `website_id` column.

### HasTags

Provides a unified interface for tag assignment and filtering using the `wncms-tags` package.  
Supports multiple tag types through the `$tagMetas` property.

## Tag Metadata System

### Defining Tag Metas

Instead of the old `TAG_TYPES` constant, WNCMS v6.x.x uses `$tagMetas` array:

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // Tag type key
        'short' => 'category',       // Short name for UI
        'route' => 'frontend.posts.tag', // Frontend route
    ],
    [
        'key'   => 'post_tag',
        'short' => 'tag',
        'route' => 'frontend.posts.tag',
    ],
];
```

### Getting Tag Metadata

The `getTagMeta()` method processes and enriches tag metadata:

```php
public static function getTagMeta(): array
{
    $raw = static::$tagMetas ?? [];

    if (empty($raw)) {
        return [];
    }

    $package = static::getPackageId();
    $modelClass = static::class;

    $metas = [];
    foreach ($raw as $meta) {
        $metas[] = array_merge($meta, [
            'model' => $modelClass,
            'model_key' => static::getModelKey(),
            'package' => $package,
            'label' => $package . "::word." . $meta['key'],
        ]);
    }

    return $metas;
}
```

This returns enriched metadata with model class, package info, and translation keys.

## Boot Logic

```php
protected static function booted()
{
    if (empty(static::$modelKey)) {
        throw new \Exception(static::class . ' must define public static $modelKey.');
    }
}
```

The boot method ensures every model has a `$modelKey` defined. This is **required** for WNCMS's internal model registration system.

## Attribute Event Hook

`BaseModel` overrides `getAttribute()` to dispatch a custom event whenever an attribute is retrieved:

```php
public function getAttribute($key)
{
    $value = parent::getAttribute($key);

    if (array_key_exists($key, $this->attributes)) {
        $event = new \Wncms\Events\ModelGettingAttribute($this, $key, $value);
        event($event);
        $value = $event->value;
    }

    if ($this->hasCast($key)) {
        if (is_string($value) || is_null($value)) {
            return $this->castAttribute($key, $value);
        }
        return $value;
    }

    return $value;
}
```

This event-based approach allows listeners (e.g., keyword replacers, filters, plugins) to modify field values **at runtime** without altering database data.

:::tip Use Case
You can listen to `ModelGettingAttribute` to transform content, inject dynamic values, or apply global filters across all models.
:::

## Model Name Resolution

`getModelName()` returns a localized display name for the model based on priority fallbacks:

```php
public static function getModelName(?string $locale = null): string
{
    $short = strtolower(class_basename(static::class));
    $packageId = static::getPackageId();

    // 1. User override via settings
    if (function_exists('gss') && $packageId) {
        $override = gss("{$packageId}::{$short}_model_name");
        if (!empty($override)) {
            return $override;
        }
    }

    // 2. Package translation
    if ($packageId) {
        $translated = __("{$packageId}::word.{$short}", locale: $locale);
        if ($translated !== "{$packageId}::word.{$short}") {
            return $translated;
        }
    }

    // 3. Core translation
    $core = __("wncms::word.{$short}", locale: $locale);
    if ($core !== "wncms::word.{$short}") {
        return $core;
    }

    // 4. Fallback to formatted class name
    return ucfirst(str_replace('_', ' ', $short));
}
```

### Priority Order:

1. **User-defined override** from settings (`gss()` function)
2. **Package-specific translation** (e.g., `wncms-faqs::word.faq`)
3. **Core translation** (`wncms::word.post`)
4. **Fallback** to formatted class name (e.g., `Post` → `Post`)

### Example Usage:

```php
Post::getModelName('zh_TW');  // → "文章"
Post::getModelName('en');     // → "Post"

## Multisite Mode Helper

`BaseModel` provides a centralized mode resolver so model-level checks do not duplicate raw config logic:

```php
public static function getMultiWebsiteMode(): string
public static function isWebsiteScopedModel(): bool
```

`getMultiWebsiteMode()` resolves mode in this order:
1. `config('wncms.models.{model_key}.website_mode')`
2. `config('wncms.model_website_modes.{model_key}')`
3. `gss('model_website_modes')` runtime overrides (when available)

Then normalizes to one of: `global`, `single`, `multi`.
```

## Default Constants

BaseModel provides default constants that can be overridden:

```php
public const ICONS = [
    'fontawesome' => 'fa-solid fa-cube'
];

public const ROUTES = [
    'index',
    'create',
];

public const SORTS = [
    'id',
    'status',
    'created_at',
    'updated_at',
];
```

These are used by backend controllers and views for consistent UI behavior.

## Package Identification

```php
public static function getPackageId(): ?string
{
    return property_exists(static::class, 'packageId')
        ? static::$packageId
        : null;
}
```

Returns the package identifier, useful for translation lookups and plugin system integration.

## Real-World Example

Here's a complete example from WNCMS Core - the Post model:

```php
namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Translatable\Traits\HasTranslations;

class Post extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use HasTranslations;

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

    protected $translatable = ['title', 'excerpt', 'keywords', 'content'];
}
```

## Summary

`BaseModel` provides:
✅ Required `$modelKey` validation  
✅ Multi-site support via `HasMultisite`  
✅ Tag system via `HasTags` + `$tagMetas`  
✅ Attribute event hooks for runtime modifications  
✅ Localized model names with fallbacks  
✅ Package identification system  
✅ Default constants for backend UI

All custom models should extend this class to leverage WNCMS's powerful features.
