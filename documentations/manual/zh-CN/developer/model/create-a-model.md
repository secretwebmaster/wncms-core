# 建立模型

本指南展示两种为 WNCMS 建立新模型的方式：

- 使用 Laravel 内建的 Artisan 产生器
- 使用 WNCMS 辅助指令 `wncms:create-model`（建议用于后台 CRUD）

结果将会是一个扩展 WNCMS [`BaseModel`](./base-model.md) 的本地模型，以及管理控制器、迁移、视图、权限和路由。

## 开始之前

- 确保 WNCMS 已安装并自动载入。
- 确认 `routes/custom_backend.php` 已被 `routes/web.php` 引入（WNCMS 预设会执行此操作）。
- 决定模型的单数和复数名称。本文件范例：`Article` / `articles`。

## 选项 A — Laravel 内建产生器

### 1) 产生档案

```bash
php artisan make:model Article -m
php artisan make:controller Backend/ArticleController --resource --model=Article
```

### 2) 更新模型以扩展 `BaseModel`

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
     * 模型键值（v6.x.x 必要）
     */
    public static $modelKey = 'article';

    protected $guarded = [];

    /**
     * 可翻译栏位
     */
    protected $translatable = ['title', 'excerpt', 'content'];

    /**
     * 日期转型
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * 标签中继资料（取代旧的 TAG_TYPES 常数）
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
     * 后台 UI 的状态选项
     */
    public const STATUSES = ['published', 'drafted'];

    /**
     * 注册媒体集合
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### 3) 让后台控制器扩展 WNCMS `BackendController`

```php
<?php

namespace App\Http\Controllers\Backend;

use Wncms\Http\Controllers\Backend\BackendController;

class ArticleController extends BackendController
{
    // 基本 CRUD 通常不需要覆写任何内容。
    // 您可以自订策略、验证、栏位等。
}
```

### 4) 建立迁移

编辑产生的迁移（范例栏位）：

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('website_id')->nullable()->index(); // 多站点支援
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('content')->nullable();
    $table->string('status')->default('drafted');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

执行迁移：

```bash
php artisan migrate
```

### 5) 注册路由（如果未使用下面的 WNCMS 指令）

附加到 `routes/custom_backend.php`：

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

### 6) 建立后台视图

WNCMS 预期视图位于 `resources/views/backend/{plural_snake}/`：

```
resources/views/backend/articles/
  ├─ index.blade.php
  ├─ create.blade.php
  └─ edit.blade.php
```

使用复数蛇形命名法命名路由和视图（例如 `backend.articles.index`）以符合 WNCMS 惯例。

### 7) 新增权限和选单（选用但建议）

- 使用您偏好的方法或小型填充器建立权限，如 `article_index`、`article_create` 等。
- 新增指向 `route('articles.index')` 的后台选单项目。

## 选项 B — WNCMS 快速指令（建议）

使用一体化的 WNCMS 指令来产生模型、迁移、控制器、视图、权限和路由。它还会将产生的控制器内的蛇形/驼峰命名标准化。

```bash
php artisan wncms:create-model Article
```

它会执行以下操作：

- `make:model Article`
- `make:migration create_articles_table`
- `make:controller Backend/ArticleController --resource --model=Article`

  - 将视图名称（`backend.articles.*`）、路由名称（`articles.*`）、快取标签和模型字词辅助函式重写为蛇形命名法

- `wncms:create-model-view article` 建立 `index/create/edit` 视图
- `wncms:create-model-permission article` 填充基本权限
- 选择性地将完整路由区块附加到 `routes/custom_backend.php` 并为控制器预先新增 `use` 语句

在附加路由之前，您会看到确认提示。

执行迁移：

```bash
php artisan migrate
```

## 建立架构后

### 更新模型（v6.x.x 重要）

使用 `wncms:create-model` 之后，手动更新产生的模型档案以包含 v6.x.x 要求：

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

    // 必要：模型键值
    public static $modelKey = 'article';

    protected $guarded = [];

    // 选用：可翻译栏位
    protected $translatable = ['title', 'excerpt', 'content'];

    // 选用：日期转型
    protected $casts = [
        'published_at' => 'datetime',
    ];

    // 选用：标签中继资料（v6.x.x 风格）
    protected static array $tagMetas = [
        [
            'key'   => 'article_category',
            'short' => 'category',
            'route' => 'frontend.articles.tag',
        ],
    ];

    // 选用：状态常数
    public const STATUSES = ['published', 'drafted'];

    // 选用：媒体集合
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### 翻译键值

如果您使用 `BaseModel::getModelName()` 或 `wncms_model_word('article')`，请新增翻译：

```
resources/lang/en/wncms.php
  'word' => [
    'article' => 'Article',
    'articles' => 'Articles',
  ],
```

或者如果您稍后将其移至套件中，请使用套件范围的键值。

### 标签类型（选用）

如果您的模型需要标签，请在 `$tagMetas` 中宣告：

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

WNCMS 会在模型启动时自动注册它们。

### 媒体与翻译（选用）

若要使用媒体库或翻译：

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

## 常用提示

- 保持控制器名称为复数，并放置在 `App\Http\Controllers\Backend\` 下。
- 路由和视图名称使用复数蛇形命名法（例如 `articles.index`）以符合 WNCMS 辅助函式和存根。
- 如果您的站点使用多站点，请在迁移中包含 `website_id`。
- 新增选单或权限时，如果它们被快取，请清除快取。
- 遵循 WNCMS 的命名：诸如 `status`、时间戳记和 slugs 等栏位使整合更顺畅。

## 快速检查清单

- ✅ 模型扩展 `Wncms\Models\BaseModel`
- ✅ 模型定义 `public static $modelKey`
- ✅ 模型使用 `$tagMetas` 阵列（而非旧的 `TAG_TYPES`）
- ✅ 后台控制器扩展 `Wncms\Http\Controllers\Backend\BackendController`
- ✅ 迁移已建立并执行，包含 `website_id` 栏位
- ✅ 视图位于 `resources/views/backend/{plural}/` 下
- ✅ 路由注册到 `routes/custom_backend.php`
- ✅ 权限已建立并分配
- ✅ 选用：已新增翻译、媒体集合和可翻译栏位
