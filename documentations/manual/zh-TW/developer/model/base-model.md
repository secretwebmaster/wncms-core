# Base Model

`BaseModel` 是 WNCMS Core v6.x.x 中所有模型的基礎類別。  
它擴展了 Laravel 原生的 `Illuminate\Database\Eloquent\Model` 並實作 `BaseModelInterface`，提供**多站點支援**、**標籤處理**、**動態模型命名**和**屬性事件鉤子**等核心功能。

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

所有 WNCMS 模型（以及您的自訂模型）都應該擴展此類別以繼承：

- 透過 `HasMultisite` trait 實現多站點感知
- 透過 `HasTags` trait 實現標籤關係和篩選
- 必須的模型鍵註冊（`$modelKey`）
- 套件識別（`$packageId`）
- 存取屬性時分派事件
- 可翻譯的模型名稱解析
- 標籤元資料定義支援

## 核心屬性

### 必要屬性

```php
abstract class BaseModel extends Model implements BaseModelInterface
{
    use HasMultisite;
    use HasTags;

    // 套件識別碼（預設：'wncms'）
    public static $packageId = 'wncms';

    // 必要：唯一模型識別碼
    public static $modelKey = '';

    // 標籤元資料定義（選用）
    protected static array $tagMetas = [];
}
```

:::warning 重要
每個擴展 `BaseModel` 的模型**必須**定義 `public static $modelKey`。如果未設定，`booted()` 方法將拋出例外。
:::

### 模型設定範例

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

添加多網站資料隔離支援。  
啟用多站點模式時，每筆記錄可以透過 `website_id` 欄位屬於特定網站。

### HasTags

使用 `wncms-tags` 套件提供統一的標籤指派和篩選介面。  
透過 `$tagMetas` 屬性支援多種標籤類型。

## 標籤元資料系統

### 定義標籤元資料

WNCMS v6.x.x 使用 `$tagMetas` 陣列取代舊的 `TAG_TYPES` 常數：

```php
protected static array $tagMetas = [
    [
        'key'   => 'post_category',  // 標籤類型鍵
        'short' => 'category',       // UI 顯示的短名稱
        'route' => 'frontend.posts.tag', // 前台路由
    ],
    [
        'key'   => 'post_tag',
        'short' => 'tag',
        'route' => 'frontend.posts.tag',
    ],
];
```

### 取得標籤元資料

`getTagMeta()` 方法處理並豐富標籤元資料：

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

這將返回包含模型類別、套件資訊和翻譯鍵的豐富元資料。

## 啟動邏輯

```php
protected static function booted()
{
    if (empty(static::$modelKey)) {
        throw new \Exception(static::class . ' must define public static $modelKey.');
    }
}
```

啟動方法確保每個模型都定義了 `$modelKey`。這是 WNCMS 內部模型註冊系統的**必要條件**。

## 屬性事件鉤子

`BaseModel` 覆寫 `getAttribute()` 以在每次取得屬性時分派自訂事件：

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

這種基於事件的方法允許監聽器（例如關鍵字替換器、過濾器、外掛）在**執行時**修改欄位值，而無需更改資料庫資料。

:::tip 使用情境
您可以監聽 `ModelGettingAttribute` 來轉換內容、注入動態值或在所有模型中套用全域過濾器。
:::

## 模型名稱解析

`getModelName()` 根據優先順序回傳模型的本地化顯示名稱：

```php
public static function getModelName(?string $locale = null): string
{
    $short = strtolower(class_basename(static::class));
    $packageId = static::getPackageId();

    // 1. 使用者透過設定覆寫
    if (function_exists('gss') && $packageId) {
        $override = gss("{$packageId}::{$short}_model_name");
        if (!empty($override)) {
            return $override;
        }
    }

    // 2. 套件翻譯
    if ($packageId) {
        $translated = __("{$packageId}::word.{$short}", locale: $locale);
        if ($translated !== "{$packageId}::word.{$short}") {
            return $translated;
        }
    }

    // 3. 核心翻譯
    $core = __("wncms::word.{$short}", locale: $locale);
    if ($core !== "wncms::word.{$short}") {
        return $core;
    }

    // 4. 降級為格式化的類別名稱
    return ucfirst(str_replace('_', ' ', $short));
}
```

### 優先順序：

1. **使用者定義覆寫** 從設定（`gss()` 函數）
2. **套件專屬翻譯**（例如 `wncms-faqs::word.faq`）
3. **核心翻譯**（`wncms::word.post`）
4. **降級** 為格式化的類別名稱（例如 `Post` → `Post`）

### 使用範例：

```php
Post::getModelName('zh_TW');  // → "文章"
Post::getModelName('en');     // → "Post"

## 多站點模式 Helper

`BaseModel` 提供集中式模式解析，避免在各處重複讀取原始 config：

```php
public static function getMultiWebsiteMode(): string
public static function isWebsiteScopedModel(): bool
```

`getMultiWebsiteMode()` 的解析順序：
1. `config('wncms.models.{model_key}.website_mode')`
2. `config('wncms.model_website_modes.{model_key}')`
3. `gss('model_website_modes')` 執行時覆寫（可用時）

最終標準化為：`global`、`single`、`multi`。
```

## 預設常數

BaseModel 提供可被覆寫的預設常數：

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

這些用於後台控制器和視圖以實現一致的 UI 行為。

## 套件識別

```php
public static function getPackageId(): ?string
{
    return property_exists(static::class, 'packageId')
        ? static::$packageId
        : null;
}
```

返回套件識別碼，用於翻譯查詢和外掛系統整合。

## 實際範例

這是來自 WNCMS Core 的完整範例 - Post 模型：

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

## 總結

`BaseModel` 提供：
✅ 必要的 `$modelKey` 驗證  
✅ 透過 `HasMultisite` 實現多站點支援  
✅ 透過 `HasTags` + `$tagMetas` 實現標籤系統  
✅ 用於執行時修改的屬性事件鉤子  
✅ 具有降級機制的本地化模型名稱  
✅ 套件識別系統  
✅ 後台 UI 的預設常數

所有自訂模型都應該擴展此類別以利用 WNCMS 的強大功能。
