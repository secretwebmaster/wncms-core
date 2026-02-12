# 建立模型

本指南展示兩種為 WNCMS 建立新模型的方式：

- 使用 Laravel 內建的 Artisan 產生器
- 使用 WNCMS 輔助指令 `wncms:create-model`（建議用於後台 CRUD）

結果將會是一個擴展 WNCMS [`BaseModel`](./base-model.md) 的本地模型，以及管理控制器、遷移、視圖、權限和路由。

## 開始之前

- 確保 WNCMS 已安裝並自動載入。
- 確認 `routes/custom_backend.php` 已被 `routes/web.php` 引入（WNCMS 預設會執行此操作）。
- 決定模型的單數和複數名稱。本文件範例：`Article` / `articles`。

## 選項 A — Laravel 內建產生器

### 1) 產生檔案

```bash
php artisan make:model Article -m
php artisan make:controller Backend/ArticleController --resource --model=Article
```

### 2) 更新模型以擴展 `BaseModel`

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
     * 模型鍵值（v6.x.x 必要）
     */
    public static $modelKey = 'article';

    protected $guarded = [];

    /**
     * 可翻譯欄位
     */
    protected $translatable = ['title', 'excerpt', 'content'];

    /**
     * 日期轉型
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * 標籤中繼資料（取代舊的 TAG_TYPES 常數）
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
     * 後台 UI 的狀態選項
     */
    public const STATUSES = ['published', 'drafted'];

    /**
     * 註冊媒體集合
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### 3) 讓後台控制器擴展 WNCMS `BackendController`

```php
<?php

namespace App\Http\Controllers\Backend;

use Wncms\Http\Controllers\Backend\BackendController;

class ArticleController extends BackendController
{
    // 基本 CRUD 通常不需要覆寫任何內容。
    // 您可以自訂策略、驗證、欄位等。
}
```

### 4) 建立遷移

編輯產生的遷移（範例欄位）：

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('website_id')->nullable()->index(); // 多站點支援
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('content')->nullable();
    $table->string('status')->default('drafted');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

執行遷移：

```bash
php artisan migrate
```

### 5) 註冊路由（如果未使用下面的 WNCMS 指令）

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

### 6) 建立後台視圖

WNCMS 預期視圖位於 `resources/views/backend/{plural_snake}/`：

```
resources/views/backend/articles/
  ├─ index.blade.php
  ├─ create.blade.php
  └─ edit.blade.php
```

使用複數蛇形命名法命名路由和視圖（例如 `backend.articles.index`）以符合 WNCMS 慣例。

### 7) 新增權限和選單（選用但建議）

- 使用您偏好的方法或小型填充器建立權限，如 `article_index`、`article_create` 等。
- 新增指向 `route('articles.index')` 的後台選單項目。

## 選項 B — WNCMS 快速指令（建議）

使用一體化的 WNCMS 指令來產生模型、遷移、控制器、視圖、權限和路由。它還會將產生的控制器內的蛇形/駝峰命名標準化。

```bash
php artisan wncms:create-model Article
```

它會執行以下操作：

- `make:model Article`
- `make:migration create_articles_table`
- `make:controller Backend/ArticleController --resource --model=Article`

  - 將視圖名稱（`backend.articles.*`）、路由名稱（`articles.*`）、快取標籤和模型字詞輔助函式重寫為蛇形命名法

- `wncms:create-model-view article` 建立 `index/create/edit` 視圖
- `wncms:create-model-permission article` 填充基本權限
- 選擇性地將完整路由區塊附加到 `routes/custom_backend.php` 並為控制器預先新增 `use` 語句

在附加路由之前，您會看到確認提示。

執行遷移：

```bash
php artisan migrate
```

## 建立架構後

### 更新模型（v6.x.x 重要）

使用 `wncms:create-model` 之後，手動更新產生的模型檔案以包含 v6.x.x 要求：

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

    // 必要：模型鍵值
    public static $modelKey = 'article';

    protected $guarded = [];

    // 選用：可翻譯欄位
    protected $translatable = ['title', 'excerpt', 'content'];

    // 選用：日期轉型
    protected $casts = [
        'published_at' => 'datetime',
    ];

    // 選用：標籤中繼資料（v6.x.x 風格）
    protected static array $tagMetas = [
        [
            'key'   => 'article_category',
            'short' => 'category',
            'route' => 'frontend.articles.tag',
        ],
    ];

    // 選用：狀態常數
    public const STATUSES = ['published', 'drafted'];

    // 選用：媒體集合
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article_thumbnail')->singleFile();
    }
}
```

### 翻譯鍵值

如果您使用 `BaseModel::getModelName()` 或 `wncms_model_word('article')`，請新增翻譯：

```
resources/lang/en/wncms.php
  'word' => [
    'article' => 'Article',
    'articles' => 'Articles',
  ],
```

或者如果您稍後將其移至套件中，請使用套件範圍的鍵值。

### 標籤類型（選用）

如果您的模型需要標籤，請在 `$tagMetas` 中宣告：

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

WNCMS 會在模型啟動時自動註冊它們。

### 媒體與翻譯（選用）

若要使用媒體庫或翻譯：

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

- 保持控制器名稱為複數，並放置在 `App\Http\Controllers\Backend\` 下。
- 路由和視圖名稱使用複數蛇形命名法（例如 `articles.index`）以符合 WNCMS 輔助函式和存根。
- 如果您的站點使用多站點，請在遷移中包含 `website_id`。
- 新增選單或權限時，如果它們被快取，請清除快取。
- 遵循 WNCMS 的命名：諸如 `status`、時間戳記和 slugs 等欄位使整合更順暢。

## 快速檢查清單

- ✅ 模型擴展 `Wncms\Models\BaseModel`
- ✅ 模型定義 `public static $modelKey`
- ✅ 模型使用 `$tagMetas` 陣列（而非舊的 `TAG_TYPES`）
- ✅ 後台控制器擴展 `Wncms\Http\Controllers\Backend\BackendController`
- ✅ 遷移已建立並執行，包含 `website_id` 欄位
- ✅ 視圖位於 `resources/views/backend/{plural}/` 下
- ✅ 路由註冊到 `routes/custom_backend.php`
- ✅ 權限已建立並分配
- ✅ 選用：已新增翻譯、媒體集合和可翻譯欄位
