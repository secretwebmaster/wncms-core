# Base Model

`BaseModel` 是 WNCMS Core v6.x.x 中所有模型的基础类别。  
它扩展了 Laravel 原生的 `Illuminate\Database\Eloquent\Model` 并实作 `BaseModelInterface`，提供**多站点支援**、**标签处理**、**动态模型命名**和**属性事件钩子**等核心功能。

:::tip 需求

- PHP 8.2+
- Laravel 12.0+
- WNCMS Core 6.x.x
  :::

## 位置

```php
Wncms\Models\BaseModel
```

## 用途

所有 WNCMS 模型（以及您的自订模型）都应该扩展此类别以继承：

- 透过 `HasMultisite` trait 实现多站点感知
- 透过 `HasTags` trait 实现标签关系和筛选
- 必须的模型键注册（`$modelKey`）
- 套件识别（`$packageId`）
- 存取属性时分派事件
- 可翻译的模型名称解析
- 标签元资料定义支援

## 核心属性

### 必要属性

```php
abstract class BaseModel extends Model implements BaseModelInterface
{
    use HasMultisite;
    use HasTags;

    // 套件识别码（预设：'wncms'）
    public static $packageId = 'wncms';

    // 必要：唯一模型识别码
    public static $modelKey = '';

    // 标签元资料定义（选用）
    protected static array $tagMetas = [];
}
```

:::warning 重要
每个扩展 `BaseModel` 的模型**必须**定义 `public static $modelKey`。如果未设定，`booted()` 方法将抛出例外。
:::

### 模型设定范例

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

## 使用的 Traits

### HasMultisite

添加多网站资料隔离支援。  
启用多站点模式时，每笔记录可以透过 `website_id` 栏位属于特定网站。

### HasTags

使用 `wncms-tags` 套件提供统一的标签指派和筛选介面。  
透过 `$tagMetas` 属性支援多种标签类型。

## 标签元资料系统

### 定义标签元资料

WNCMS v6.x.x 使用 `$tagMetas` 阵列取代旧的 `TAG_TYPES` 常数：

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // 标签类型键
        'short' => 'category',       // UI 显示的短名称
        'route' => 'frontend.posts.tag', // 前台路由
    ],
    [
        'key'   => 'post_tag',
        'short' => 'tag',
        'route' => 'frontend.posts.tag',
    ],
];
```

### 取得标签元资料

`getTagMeta()` 方法处理并丰富标签元资料：

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

这将返回包含模型类别、套件资讯和翻译键的丰富元资料。

## 启动逻辑

```php
protected static function booted()
{
    if (empty(static::$modelKey)) {
        throw new \Exception(static::class . ' must define public static $modelKey.');
    }
}
```

启动方法确保每个模型都定义了 `$modelKey`。这是 WNCMS 内部模型注册系统的**必要条件**。

## 属性事件钩子

`BaseModel` 覆写 `getAttribute()` 以在每次取得属性时分派自订事件：

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

这种基于事件的方法允许监听器（例如关键字替换器、过滤器、外挂）在**执行时**修改栏位值，而无需更改资料库资料。

:::tip 使用情境
您可以监听 `ModelGettingAttribute` 来转换内容、注入动态值或在所有模型中套用全域过滤器。
:::

## 模型名称解析

`getModelName()` 根据优先顺序回传模型的本地化显示名称：

```php
public static function getModelName(?string $locale = null): string
{
    $short = strtolower(class_basename(static::class));
    $packageId = static::getPackageId();

    // 1. 使用者透过设定覆写
    if (function_exists('gss') && $packageId) {
        $override = gss("{$packageId}::{$short}_model_name");
        if (!empty($override)) {
            return $override;
        }
    }

    // 2. 套件翻译
    if ($packageId) {
        $translated = __("{$packageId}::word.{$short}", locale: $locale);
        if ($translated !== "{$packageId}::word.{$short}") {
            return $translated;
        }
    }

    // 3. 核心翻译
    $core = __("wncms::word.{$short}", locale: $locale);
    if ($core !== "wncms::word.{$short}") {
        return $core;
    }

    // 4. 降级为格式化的类别名称
    return ucfirst(str_replace('_', ' ', $short));
}
```

### 优先顺序：

1. **使用者定义覆写** 从设定（`gss()` 函数）
2. **套件专属翻译**（例如 `wncms-faqs::word.faq`）
3. **核心翻译**（`wncms::word.post`）
4. **降级** 为格式化的类别名称（例如 `Post` → `Post`）

### 使用范例：

```php
Post::getModelName('zh_TW');  // → "文章"
Post::getModelName('en');     // → "Post"
```

## 预设常数

BaseModel 提供可被覆写的预设常数：

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

这些用于后台控制器和视图以实现一致的 UI 行为。

## 套件识别

```php
public static function getPackageId(): ?string
{
    return property_exists(static::class, 'packageId')
        ? static::$packageId
        : null;
}
```

返回套件识别码，用于翻译查询和外挂系统整合。

## 实际范例

这是来自 WNCMS Core 的完整范例 - Post 模型：

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

## 总结

`BaseModel` 提供：
✅ 必要的 `$modelKey` 验证  
✅ 透过 `HasMultisite` 实现多站点支援  
✅ 透过 `HasTags` + `$tagMetas` 实现标签系统  
✅ 用于执行时修改的属性事件钩子  
✅ 具有降级机制的本地化模型名称  
✅ 套件识别系统  
✅ 后台 UI 的预设常数

所有自订模型都应该扩展此类别以利用 WNCMS 的强大功能。
