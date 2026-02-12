# 內容模型

在 WNCMS 中，**內容模型**代表儲存使用者產生或網站管理內容的資料實體，例如**文章**、**頁面**、**連結**或自訂內容類型。

每個內容模型都擴展 [`BaseModel`](./base-model) 類別，以繼承 WNCMS 全域功能，如多站點支援、標籤處理、可翻譯屬性和媒體管理。

:::tip WNCMS v6.x.x
v6.x.x 的內容模型需要 `$modelKey`，並使用 `$tagMetas` 取代舊的 `TAG_TYPES` 常數。
:::

## 典型結構

WNCMS v6.x.x 中的標準內容模型通常包括：

### 核心 Traits

- `HasFactory` — Laravel 工廠支援，用於測試/填充資料
- `HasTranslations` — 透過 `wncms-translatable` 支援多語言欄位
- `InteractsWithMedia` — 透過 Spatie Media Library 支援圖片/檔案上傳
- `SoftDeletes` — 軟刪除功能（選用）

### 必要屬性

- `public static $modelKey` — 唯一模型識別碼（**必要**）
- `protected static array $tagMetas` — 標籤類型定義

### 選用功能

- 常數：`ICONS`、`STATUSES`、`SORTS`、`VISIBILITIES`
- 媒體集合註冊
- 自訂存取器/修改器
- 與其他模型的關聯

## 真實範例：Link 模型

以下是來自 WNCMS Core v6.x.x 的實際 Link 模型：

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

## 進階範例：Post 模型

Post 模型展示了更進階的功能：

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

## 核心元件說明

### 1. 模型鍵值（必要）

```php
public static $modelKey = 'post';
```

每個內容模型**必須**定義唯一的模型鍵值。WNCMS 使用它來：

- 產生後台路由
- 權限系統
- 模型註冊
- 翻譯查詢

### 2. 標籤中繼資料

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // 唯一標籤類型識別碼
        'short' => 'category',       // UI 短名稱
        'route' => 'frontend.posts.tag', // 前台路由名稱
    ],
];
```

標籤中繼資料定義此模型支援的標籤類型。系統會在啟動時自動註冊這些標籤類型。

### 3. 可翻譯欄位

```php
protected $translatable = ['title', 'excerpt', 'content'];
```

列在此處的欄位可使用 `wncms-translatable` 套件翻譯成多種語言。

### 4. 媒體集合

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('post_thumbnail')->singleFile();
    $this->addMediaCollection('post_content'); // 允許多個檔案
}
```

定義檔案上傳的媒體集合。對於只應有一個檔案的集合使用 `singleFile()`。

### 5. 自訂存取器

```php
public function getThumbnailAttribute()
{
    $media = $this->getMedia('post_thumbnail')->first();
    if ($media) return $media->getUrl();
    return $this->external_thumbnail;
}
```

Laravel 存取器提供計算屬性。此範例返回上傳的縮圖 URL，或回傳到外部 URL。

### 6. 後台 UI 常數

```php
public const STATUSES = ['published', 'drafted', 'trashed'];
public const VISIBILITIES = ['public', 'member', 'admin'];
public const ICONS = ['fontawesome' => 'fa-solid fa-pencil'];
```

這些常數由後台控制器和視圖使用：

- `STATUSES` — 可用狀態選項
- `VISIBILITIES` — 誰可以查看內容
- `ICONS` — 管理面板中顯示的圖示
- `SORTS` — 可用排序選項

## 建立自己的內容模型

### 步驟 1：產生模型

```bash
php artisan make:model Article -m
```

### 步驟 2：擴展 BaseModel

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

### 步驟 3：建立遷移

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

### 步驟 4：執行遷移

```bash
php artisan migrate
```

## 最佳實踐

### ✅ 應該做：

- 總是定義 `$modelKey`
- 使用 `$tagMetas` 來定義標籤類型（而非舊的 `TAG_TYPES`）
- 如果使用媒體，實作 `HasMedia` 介面
- 對多語言欄位使用 `$translatable`
- 定義有意義的 `STATUSES` 和 `VISIBILITIES`
- 為多站點支援新增 `website_id` 欄位
- 對日期時間欄位使用 `$casts`

### ❌ 不應該做：

- 不要使用舊的 `TAG_TYPES` 常數（在 v6.x.x 已棄用）
- 使用 `InteractsWithMedia` 時不要忘記實作 `HasMedia`
- 如果使用媒體，不要跳過 `registerMediaCollections()`
- 不要使用 `$fillable`（改用 `$guarded = []`）

## 內容模型常用 Traits

| Trait                | 用途       | 何時使用                  |
| -------------------- | ---------- | ------------------------- |
| `HasFactory`         | 工廠支援   | 總是使用（用於填充/測試） |
| `HasTranslations`    | 多語言欄位 | 需要翻譯時                |
| `InteractsWithMedia` | 檔案上傳   | 處理圖片/檔案時           |
| `SoftDeletes`        | 軟刪除     | 想保留已刪除記錄時        |
| `HasComments`        | 評論系統   | 用於使用者產生的評論      |
| `HasApi`             | API 端點   | 透過 API 公開時           |
| `HasOptions`         | 動態選項   | 用於彈性鍵值設定          |

## 總結

WNCMS v6.x.x 的內容模型：

- ✅ 擴展 `BaseModel`
- ✅ 需要 `$modelKey` 定義
- ✅ 使用 `$tagMetas` 定義標籤類型
- ✅ 支援翻譯、媒體和多站點
- ✅ 透過常數提供一致的後台 UI
- ✅ 可輕鬆建立和自訂

有關逐步建立模型的說明，請參閱[建立模型](./create-a-model)。
