# 内容模型

在 WNCMS 中，**内容模型**代表储存使用者产生或网站管理内容的资料实体，例如**文章**、**页面**、**连结**或自订内容类型。

每个内容模型都扩展 [`BaseModel`](./base-model) 类别，以继承 WNCMS 全域功能，如多站点支援、标签处理、可翻译属性和媒体管理。

:::tip WNCMS v6.x.x
v6.x.x 的内容模型需要 `$modelKey`，并使用 `$tagMetas` 取代旧的 `TAG_TYPES` 常数。
:::

## 典型结构

WNCMS v6.x.x 中的标准内容模型通常包括：

### 核心 Traits

- `HasFactory` — Laravel 工厂支援，用于测试/填充资料
- `HasTranslations` — 透过 `wncms-translatable` 支援多语言栏位
- `InteractsWithMedia` — 透过 Spatie Media Library 支援图片/档案上传
- `SoftDeletes` — 软删除功能（选用）

### 必要属性

- `public static $modelKey` — 唯一模型识别码（**必要**）
- `protected static array $tagMetas` — 标签类型定义

### 选用功能

- 常数：`ICONS`、`STATUSES`、`SORTS`、`VISIBILITIES`
- 媒体集合注册
- 自订存取器/修改器
- 与其他模型的关联

## 真实范例：Link 模型

以下是来自 WNCMS Core v6.x.x 的实际 Link 模型：

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

## 进阶范例：Post 模型

Post 模型展示了更进阶的功能：

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

## 核心元件说明

### 1. 模型键值（必要）

```php
public static $modelKey = 'post';
```

每个内容模型**必须**定义唯一的模型键值。WNCMS 使用它来：

- 产生后台路由
- 权限系统
- 模型注册
- 翻译查询

### 2. 标签中继资料

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // 唯一标签类型识别码
        'short' => 'category',       // UI 短名称
        'route' => 'frontend.posts.tag', // 前台路由名称
    ],
];
```

标签中继资料定义此模型支援的标签类型。系统会在启动时自动注册这些标签类型。

### 3. API 路由中继资料（要在 API 设定显示时为必要）

如果模型要在**系统设定 -> API**中显示可配置的 API 动作，请同时定义以下项目：

- 实作 `Wncms\Interfaces\ApiModelInterface`
- 使用 `Wncms\Traits\HasApi`
- 设定 `protected static bool $hasApi = true;`
- 定义 `protected static array $apiRoutes = [...]`

每个路由项目必须包含：

- `name`：`routes/api.php` 中的 Laravel 路由名称
- `key`：系统设定键值（例如 `wncms_api_post_index`）
- `action`：设定表格中的动作名称（`index`、`show`、`store`、`update`、`delete` 或自定义）

可选但建议提供：

- `package_id`：API 设定标签翻译所用的套件命名空间（例如 `wncms`、`my-package`）

范例：

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

`HasApi::getApiRoutes()` 现在会在缺少 `package_id` 时自动以模型套件识别码补上，确保**系统设定 -> API**中的标签翻译能使用正确命名空间。

若未定义 `$apiRoutes`，该模型的 API 启用/验证切换不会出现在后台设定中。

### 3. 可翻译栏位

```php
protected $translatable = ['title', 'excerpt', 'content'];
```

列在此处的栏位可使用 `wncms-translatable` 套件翻译成多种语言。

### 4. 媒体集合

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('post_thumbnail')->singleFile();
    $this->addMediaCollection('post_content'); // 允许多个档案
}
```

定义档案上传的媒体集合。对于只应有一个档案的集合使用 `singleFile()`。

### 5. 自订存取器

```php
public function getThumbnailAttribute()
{
    $media = $this->getMedia('post_thumbnail')->first();
    if ($media) return $media->getUrl();
    return $this->external_thumbnail;
}
```

Laravel 存取器提供计算属性。此范例返回上传的缩图 URL，或回传到外部 URL。

### 6. 后台 UI 常数

```php
public const STATUSES = ['published', 'drafted', 'trashed'];
public const VISIBILITIES = ['public', 'member', 'admin'];
public const ICONS = ['fontawesome' => 'fa-solid fa-pencil'];
```

这些常数由后台控制器和视图使用：

- `STATUSES` — 可用状态选项
- `VISIBILITIES` — 谁可以查看内容
- `ICONS` — 管理面板中显示的图示
- `SORTS` — 可用排序选项

## 建立自己的内容模型

### 步骤 1：产生模型

```bash
php artisan make:model Article -m
```

### 步骤 2：扩展 BaseModel

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

### 步骤 3：建立迁移

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

### 步骤 4：执行迁移

```bash
php artisan migrate
```

## 最佳实践

### ✅ 应该做：

- 总是定义 `$modelKey`
- 使用 `$tagMetas` 来定义标签类型（而非旧的 `TAG_TYPES`）
- 如果使用媒体，实作 `HasMedia` 介面
- 对多语言栏位使用 `$translatable`
- 定义有意义的 `STATUSES` 和 `VISIBILITIES`
- 为多站点支援新增 `website_id` 栏位
- 对日期时间栏位使用 `$casts`

### ❌ 不应该做：

- 不要使用旧的 `TAG_TYPES` 常数（在 v6.x.x 已弃用）
- 使用 `InteractsWithMedia` 时不要忘记实作 `HasMedia`
- 如果使用媒体，不要跳过 `registerMediaCollections()`
- 不要使用 `$fillable`（改用 `$guarded = []`）

## 内容模型常用 Traits

| Trait                | 用途       | 何时使用                  |
| -------------------- | ---------- | ------------------------- |
| `HasFactory`         | 工厂支援   | 总是使用（用于填充/测试） |
| `HasTranslations`    | 多语言栏位 | 需要翻译时                |
| `InteractsWithMedia` | 档案上传   | 处理图片/档案时           |
| `SoftDeletes`        | 软删除     | 想保留已删除记录时        |
| `HasComments`        | 评论系统   | 用于使用者产生的评论      |
| `HasApi`             | API 端点   | 透过 API 公开时           |
| `HasOptions`         | 动态选项   | 用于弹性键值设定          |

## 总结

WNCMS v6.x.x 的内容模型：

- ✅ 扩展 `BaseModel`
- ✅ 需要 `$modelKey` 定义
- ✅ 使用 `$tagMetas` 定义标签类型
- ✅ 支援翻译、媒体和多站点
- ✅ 透过常数提供一致的后台 UI
- ✅ 可轻松建立和自订

有关逐步建立模型的说明，请参阅[建立模型](./create-a-model)。
