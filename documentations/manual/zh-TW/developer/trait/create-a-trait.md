# 建立自訂 Trait

## 概述

Traits 是在 PHP 中重用程式碼的強大方式。在 WNCMS 中，traits 廣泛用於為模型、控制器和其他類別新增可重用功能。本指南將教您如何在 WNCMS 專案中建立和使用自訂 traits。

## 何時使用 Traits

在以下情況下考慮建立 trait：

- 您有多個類別需要相同功能
- 您想避免深層繼承層次結構
- 您想在不相關的類別之間共享方法
- 您想建立模組化、可重用的元件

## 基本 Trait 結構

### 簡單 Trait 範例

```php
<?php

namespace App\Traits;

trait HasSlug
{
    /**
     * 從標題生成別名
     */
    public function generateSlug(): string
    {
        return str($this->title)->slug()->toString();
    }

    /**
     * 取得帶有別名的路由
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

### 在模型中使用

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;

class Article extends Model
{
    use HasSlug;

    protected $fillable = ['title', 'slug', 'content'];
}
```

## 進階 Trait 功能

### Boot 方法

Traits 可以定義 boot 方法，在模型啟動時自動執行：

```php
<?php

namespace App\Traits;

trait HasUuid
{
    /**
     * Trait 啟動方法
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = str()->uuid()->toString();
            }
        });
    }

    /**
     * 使用 UUID 尋找模型
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }
}
```

**命名約定**：boot 方法必須命名為 `boot{TraitName}`。

### 屬性和關聯

Traits 可以定義屬性和 Eloquent 關聯：

```php
<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * 取得所有模型的評論
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->latest();
    }

    /**
     * 取得已核准的評論
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->where('status', 'approved');
    }

    /**
     * 新增評論到模型
     */
    public function addComment(string $content, ?int $userId = null): Comment
    {
        return $this->comments()->create([
            'content' => $content,
            'user_id' => $userId ?? auth()->id(),
            'status' => 'pending',
        ]);
    }
}
```

### 查詢作用域

Traits 可以定義可重用的查詢作用域：

```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasPublishStatus
{
    /**
     * 僅查詢已發布的記錄
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * 僅查詢草稿
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * 僅查詢排程的內容
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '>', now());
    }

    /**
     * 發布模型
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
```

使用方式：

```php
// 取得所有已發布的文章
$posts = Post::published()->get();

// 取得草稿
$drafts = Post::draft()->get();

// 發布文章
$post->publish();
```

## 實用 Trait 範例

### 1. 評分系統 Trait

```php
<?php

namespace App\Traits;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReviews
{
    /**
     * 取得所有評論
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * 取得平均評分
     */
    public function averageRating(): float
    {
        return (float) $this->reviews()
            ->avg('rating');
    }

    /**
     * 取得評論總數
     */
    public function reviewCount(): int
    {
        return $this->reviews()->count();
    }

    /**
     * 新增評論
     */
    public function addReview(int $rating, ?string $comment = null): Review
    {
        return $this->reviews()->create([
            'rating' => $rating,
            'comment' => $comment,
            'user_id' => auth()->id(),
        ]);
    }
}
```

### 2. 定價功能 Trait

```php
<?php

namespace App\Traits;

trait HasPricing
{
    /**
     * 取得格式化價格
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * 取得帶稅價格
     */
    public function getPriceWithTax(float $taxRate = 0.1): float
    {
        return $this->price * (1 + $taxRate);
    }

    /**
     * 取得折扣價
     */
    public function getDiscountedPrice(float $discountPercentage): float
    {
        return $this->price * (1 - $discountPercentage / 100);
    }

    /**
     * 檢查是否有折扣
     */
    public function hasDiscount(): bool
    {
        return isset($this->discount_percentage)
            && $this->discount_percentage > 0;
    }

    /**
     * 套用折扣
     */
    public function applyDiscount(float $percentage): bool
    {
        return $this->update([
            'discount_percentage' => $percentage,
            'discounted_price' => $this->getDiscountedPrice($percentage),
        ]);
    }
}
```

### 3. 分類系統 Trait

```php
<?php

namespace App\Traits;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait Categorizable
{
    /**
     * 取得所有類別
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * 附加類別
     */
    public function attachCategories(array|int $categories): void
    {
        $this->categories()->attach($categories);
    }

    /**
     * 同步類別
     */
    public function syncCategories(array $categories): void
    {
        $this->categories()->sync($categories);
    }

    /**
     * 移除類別
     */
    public function detachCategories(array|int $categories = []): void
    {
        if (empty($categories)) {
            $this->categories()->detach();
        } else {
            $this->categories()->detach($categories);
        }
    }

    /**
     * 檢查是否屬於類別
     */
    public function hasCategory(int $categoryId): bool
    {
        return $this->categories()->where('id', $categoryId)->exists();
    }

    /**
     * 依類別篩選
     */
    public function scopeInCategory($query, int|array $categoryIds)
    {
        return $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('id', (array) $categoryIds);
        });
    }
}
```

### 4. 快取功能 Trait

```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait UsesCaching
{
    /**
     * 取得快取鍵
     */
    public function getCacheKey(): string
    {
        return sprintf(
            '%s:%s',
            $this->getTable(),
            $this->getKey()
        );
    }

    /**
     * 快取模型
     */
    public function cache(int $minutes = 60): self
    {
        return Cache::remember(
            $this->getCacheKey(),
            now()->addMinutes($minutes),
            fn() => $this
        );
    }

    /**
     * 清除快取
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * 啟動方法以在更新時清除快取
     */
    protected static function bootUsesCaching(): void
    {
        static::updated(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
```

### 5. 狀態機 Trait

```php
<?php

namespace App\Traits;

trait HasStatus
{
    /**
     * 取得可用狀態
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'draft' => '草稿',
            'pending' => '待審核',
            'published' => '已發布',
            'archived' => '已封存',
        ];
    }

    /**
     * 檢查狀態
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * 變更狀態
     */
    public function changeStatus(string $status): bool
    {
        if (!array_key_exists($status, self::getAvailableStatuses())) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return $this->update(['status' => $status]);
    }

    /**
     * 狀態作用域
     */
    public function scopeWithStatus($query, string|array $status)
    {
        return $query->whereIn('status', (array) $status);
    }

    /**
     * 檢查是否為草稿
     */
    public function isDraft(): bool
    {
        return $this->hasStatus('draft');
    }

    /**
     * 檢查是否已發布
     */
    public function isPublished(): bool
    {
        return $this->hasStatus('published');
    }
}
```

## 最佳實踐

### 1. 命名約定

```php
// 好的 trait 名稱
trait HasComments      // 表示關聯或能力
trait Publishable      // 表示行為
trait UsesUuid         // 表示特性
trait Searchable       // 表示能力

// 避免
trait CommentsTrait    // 冗餘的 "Trait" 後綴
trait Misc             // 不具描述性
```

### 2. 單一職責

每個 trait 應有明確的單一目的：

```php
// 好：專注的 trait
trait HasSlug { }
trait HasUuid { }
trait Publishable { }

// 避免：做太多事的 trait
trait ModelHelpers {
    // 混合別名、UUID、發布等功能
}
```

### 3. 提供文件

記錄 trait 的方法和用途：

```php
/**
 * 為模型新增軟刪除評論功能
 *
 * 此 trait 提供評論管理的方法，包括新增、
 * 核准和刪除評論。
 *
 * @property-read \Illuminate\Database\Eloquent\Collection $comments
 */
trait HasComments
{
    /**
     * 取得所有模型的評論
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments(): MorphMany
    {
        // ...
    }
}
```

### 4. 處理衝突

當使用多個 traits 時，明確解決方法衝突：

```php
class Post extends Model
{
    use HasSlug, HasUuid {
        HasSlug::boot as bootSlug;
        HasUuid::boot as bootUuid;
    }

    protected static function boot()
    {
        parent::boot();
        self::bootSlug();
        self::bootUuid();
    }
}
```

### 5. 測試 Traits

為 traits 建立專用測試：

```php
<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Post;
use App\Traits\HasSlug;

class HasSlugTest extends TestCase
{
    public function test_generates_slug_from_title()
    {
        $post = new Post();
        $post->title = 'My Test Article';

        $slug = $post->generateSlug();

        $this->assertEquals('my-test-article', $slug);
    }

    public function test_uses_slug_as_route_key()
    {
        $post = new Post();

        $this->assertEquals('slug', $post->getRouteKeyName());
    }
}
```

## 常見陷阱

### 1. 忘記 Boot 方法命名

```php
// 錯誤：不會被呼叫
protected static function boot() { }

// 正確：會自動被呼叫
protected static function bootHasUuid() { }
```

### 2. 屬性衝突

```php
// 避免在多個 traits 中定義相同屬性
trait TraitA {
    protected $status = 'active';
}

trait TraitB {
    protected $status = 'pending'; // 衝突！
}
```

### 3. 過度使用 Traits

```php
// 避免：太多 traits
class Post extends Model {
    use HasSlug, HasUuid, HasComments, HasTags,
        HasReviews, Publishable, Searchable,
        Cacheable, HasMetadata; // 考慮重構
}

// 好：合理數量的 traits
class Post extends Model {
    use HasSlug, HasComments, Publishable;
}
```

## 效能考量

### 懶載入關聯

```php
trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // 新增計數屬性以避免 N+1 查詢
    public function getCommentsCountAttribute(): int
    {
        return $this->comments_count ?? $this->comments()->count();
    }
}

// 使用時預載入
$posts = Post::withCount('comments')->get();
```

### 快取昂貴操作

```php
trait HasStatistics
{
    public function getStatistics(): array
    {
        return Cache::remember(
            "model:{$this->id}:statistics",
            3600,
            fn() => $this->calculateStatistics()
        );
    }

    protected function calculateStatistics(): array
    {
        // 昂貴的計算
    }
}
```

## 另請參閱

- [HasTags Trait](./has-tags.md)
- [HasMultisite Trait](./has-multisite.md)
- [HasTranslations Trait](./has-translations.md)
- [Base Model](../model/base-model.md)
