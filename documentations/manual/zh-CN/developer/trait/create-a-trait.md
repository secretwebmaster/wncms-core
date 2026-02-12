# 建立自订 Trait

## 概述

Traits 是在 PHP 中重用程式码的强大方式。在 WNCMS 中，traits 广泛用于为模型、控制器和其他类别新增可重用功能。本指南将教您如何在 WNCMS 专案中建立和使用自订 traits。

## 何时使用 Traits

在以下情况下考虑建立 trait：

- 您有多个类别需要相同功能
- 您想避免深层继承层次结构
- 您想在不相关的类别之间共享方法
- 您想建立模组化、可重用的元件

## 基本 Trait 结构

### 简单 Trait 范例

```php
<?php

namespace App\Traits;

trait HasSlug
{
    /**
     * 从标题生成别名
     */
    public function generateSlug(): string
    {
        return str($this->title)->slug()->toString();
    }

    /**
     * 取得带有别名的路由
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

## 进阶 Trait 功能

### Boot 方法

Traits 可以定义 boot 方法，在模型启动时自动执行：

```php
<?php

namespace App\Traits;

trait HasUuid
{
    /**
     * Trait 启动方法
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
     * 使用 UUID 寻找模型
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }
}
```

**命名约定**：boot 方法必须命名为 `boot{TraitName}`。

### 属性和关联

Traits 可以定义属性和 Eloquent 关联：

```php
<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * 取得所有模型的评论
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->latest();
    }

    /**
     * 取得已核准的评论
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->where('status', 'approved');
    }

    /**
     * 新增评论到模型
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

### 查询作用域

Traits 可以定义可重用的查询作用域：

```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasPublishStatus
{
    /**
     * 仅查询已发布的记录
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * 仅查询草稿
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * 仅查询排程的内容
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '>', now());
    }

    /**
     * 发布模型
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
// 取得所有已发布的文章
$posts = Post::published()->get();

// 取得草稿
$drafts = Post::draft()->get();

// 发布文章
$post->publish();
```

## 实用 Trait 范例

### 1. 评分系统 Trait

```php
<?php

namespace App\Traits;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReviews
{
    /**
     * 取得所有评论
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * 取得平均评分
     */
    public function averageRating(): float
    {
        return (float) $this->reviews()
            ->avg('rating');
    }

    /**
     * 取得评论总数
     */
    public function reviewCount(): int
    {
        return $this->reviews()->count();
    }

    /**
     * 新增评论
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

### 2. 定价功能 Trait

```php
<?php

namespace App\Traits;

trait HasPricing
{
    /**
     * 取得格式化价格
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * 取得带税价格
     */
    public function getPriceWithTax(float $taxRate = 0.1): float
    {
        return $this->price * (1 + $taxRate);
    }

    /**
     * 取得折扣价
     */
    public function getDiscountedPrice(float $discountPercentage): float
    {
        return $this->price * (1 - $discountPercentage / 100);
    }

    /**
     * 检查是否有折扣
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

### 3. 分类系统 Trait

```php
<?php

namespace App\Traits;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait Categorizable
{
    /**
     * 取得所有类别
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * 附加类别
     */
    public function attachCategories(array|int $categories): void
    {
        $this->categories()->attach($categories);
    }

    /**
     * 同步类别
     */
    public function syncCategories(array $categories): void
    {
        $this->categories()->sync($categories);
    }

    /**
     * 移除类别
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
     * 检查是否属于类别
     */
    public function hasCategory(int $categoryId): bool
    {
        return $this->categories()->where('id', $categoryId)->exists();
    }

    /**
     * 依类别筛选
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
     * 取得快取键
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
     * 启动方法以在更新时清除快取
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

### 5. 状态机 Trait

```php
<?php

namespace App\Traits;

trait HasStatus
{
    /**
     * 取得可用状态
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'draft' => '草稿',
            'pending' => '待审核',
            'published' => '已发布',
            'archived' => '已封存',
        ];
    }

    /**
     * 检查状态
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * 变更状态
     */
    public function changeStatus(string $status): bool
    {
        if (!array_key_exists($status, self::getAvailableStatuses())) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return $this->update(['status' => $status]);
    }

    /**
     * 状态作用域
     */
    public function scopeWithStatus($query, string|array $status)
    {
        return $query->whereIn('status', (array) $status);
    }

    /**
     * 检查是否为草稿
     */
    public function isDraft(): bool
    {
        return $this->hasStatus('draft');
    }

    /**
     * 检查是否已发布
     */
    public function isPublished(): bool
    {
        return $this->hasStatus('published');
    }
}
```

## 最佳实践

### 1. 命名约定

```php
// 好的 trait 名称
trait HasComments      // 表示关联或能力
trait Publishable      // 表示行为
trait UsesUuid         // 表示特性
trait Searchable       // 表示能力

// 避免
trait CommentsTrait    // 冗余的 "Trait" 后缀
trait Misc             // 不具描述性
```

### 2. 单一职责

每个 trait 应有明确的单一目的：

```php
// 好：专注的 trait
trait HasSlug { }
trait HasUuid { }
trait Publishable { }

// 避免：做太多事的 trait
trait ModelHelpers {
    // 混合别名、UUID、发布等功能
}
```

### 3. 提供文件

记录 trait 的方法和用途：

```php
/**
 * 为模型新增软删除评论功能
 *
 * 此 trait 提供评论管理的方法，包括新增、
 * 核准和删除评论。
 *
 * @property-read \Illuminate\Database\Eloquent\Collection $comments
 */
trait HasComments
{
    /**
     * 取得所有模型的评论
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments(): MorphMany
    {
        // ...
    }
}
```

### 4. 处理冲突

当使用多个 traits 时，明确解决方法冲突：

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

### 5. 测试 Traits

为 traits 建立专用测试：

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

## 常见陷阱

### 1. 忘记 Boot 方法命名

```php
// 错误：不会被呼叫
protected static function boot() { }

// 正确：会自动被呼叫
protected static function bootHasUuid() { }
```

### 2. 属性冲突

```php
// 避免在多个 traits 中定义相同属性
trait TraitA {
    protected $status = 'active';
}

trait TraitB {
    protected $status = 'pending'; // 冲突！
}
```

### 3. 过度使用 Traits

```php
// 避免：太多 traits
class Post extends Model {
    use HasSlug, HasUuid, HasComments, HasTags,
        HasReviews, Publishable, Searchable,
        Cacheable, HasMetadata; // 考虑重构
}

// 好：合理数量的 traits
class Post extends Model {
    use HasSlug, HasComments, Publishable;
}
```

## 效能考量

### 懒载入关联

```php
trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // 新增计数属性以避免 N+1 查询
    public function getCommentsCountAttribute(): int
    {
        return $this->comments_count ?? $this->comments()->count();
    }
}

// 使用时预载入
$posts = Post::withCount('comments')->get();
```

### 快取昂贵操作

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
        // 昂贵的计算
    }
}
```

## 另请参阅

- [HasTags Trait](./has-tags.md)
- [HasMultisite Trait](./has-multisite.md)
- [HasTranslations Trait](./has-translations.md)
- [Base Model](../model/base-model.md)
