# Create a Trait

Traits in WNCMS provide reusable functionality that can be shared across multiple models. This guide shows how to create custom traits following WNCMS conventions.

## Purpose of Traits

Traits allow you to:

- Share functionality across multiple models
- Keep models clean and focused
- Implement cross-cutting concerns (logging, scoping, timestamps)
- Extend WNCMS without modifying core files

## Location and Naming

Custom traits should be stored in:

```
app/Traits/
```

Follow the naming pattern:

```
Has{Feature}.php
Use{Behavior}.php
```

Examples:

```
HasReviews.php
HasPricing.php
UsesCaching.php
LogsActivity.php
```

## Basic Trait Structure

```php
<?php

namespace App\Traits;

trait HasReviews
{
    /**
     * Get all reviews for this model.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get average rating.
     */
    public function averageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Check if model has reviews.
     */
    public function hasReviews(): bool
    {
        return $this->reviews()->exists();
    }
}
```

## Using Boot Methods

Traits can hook into model lifecycle events:

```php
<?php

namespace App\Traits;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            activity()
                ->performedOn($model)
                ->log('created');
        });

        static::updated(function ($model) {
            activity()
                ->performedOn($model)
                ->log('updated');
        });

        static::deleted(function ($model) {
            activity()
                ->performedOn($model)
                ->log('deleted');
        });
    }
}
```

## Trait with Configuration

```php
<?php

namespace App\Traits;

trait HasPricing
{
    /**
     * Get price attribute (override this in models).
     */
    abstract public function getPriceAttribute();

    /**
     * Calculate discounted price.
     */
    public function getDiscountedPrice(int $discountPercent): float
    {
        $price = $this->price;
        return $price - ($price * $discountPercent / 100);
    }

    /**
     * Format price for display.
     */
    public function formattedPrice(): string
    {
        $currency = config('app.currency', 'USD');
        return number_format($this->price, 2) . ' ' . $currency;
    }

    /**
     * Scope: Filter by price range.
     */
    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }
}
```

## Trait with Relationships

```php
<?php

namespace App\Traits;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Categorizable
{
    /**
     * Get categories relationship.
     */
    public function categories(): BelongsToMany
    {
        return $this->morphToMany(
            Category::class,
            'categorizable',
            'model_has_categories'
        );
    }

    /**
     * Attach categories.
     */
    public function attachCategories(array $categoryIds): void
    {
        $this->categories()->syncWithoutDetaching($categoryIds);
    }

    /**
     * Detach categories.
     */
    public function detachCategories(array $categoryIds): void
    {
        $this->categories()->detach($categoryIds);
    }

    /**
     * Sync categories.
     */
    public function syncCategories(array $categoryIds): void
    {
        $this->categories()->sync($categoryIds);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }
}
```

## Using Traits in Models

```php
<?php

namespace App\Models;

use Wncms\Models\BaseModel;
use App\Traits\HasReviews;
use App\Traits\HasPricing;
use App\Traits\Categorizable;

class Product extends BaseModel
{
    use HasReviews;
    use HasPricing;
    use Categorizable;

    protected $fillable = ['name', 'description', 'price', 'stock'];

    /**
     * Get the price attribute (required by HasPricing).
     */
    public function getPriceAttribute($value)
    {
        return (float) $value;
    }
}
```

## Advanced: Trait with Properties

```php
<?php

namespace App\Traits;

trait UsesCaching
{
    /**
     * Cache key prefix.
     */
    protected string $cacheKeyPrefix = 'model';

    /**
     * Cache duration in seconds.
     */
    protected int $cacheDuration = 3600;

    /**
     * Get cached model.
     */
    public static function getCached(int $id)
    {
        $instance = new static;
        $key = $instance->getCacheKey($id);

        return cache()->remember($key, $instance->cacheDuration, function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Get cache key.
     */
    protected function getCacheKey(int $id): string
    {
        return "{$this->cacheKeyPrefix}:{$this->getTable()}:{$id}";
    }

    /**
     * Clear cache for this model.
     */
    public function clearCache(): void
    {
        $key = $this->getCacheKey($this->id);
        cache()->forget($key);
    }

    /**
     * Boot the trait.
     */
    protected static function bootUsesCaching()
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

## Testing Traits

```php
<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Product;
use App\Traits\HasReviews;

class HasReviewsTest extends TestCase
{
    public function test_model_can_have_reviews()
    {
        $product = Product::factory()->create();

        $review = $product->reviews()->create([
            'rating' => 5,
            'comment' => 'Great product!',
        ]);

        $this->assertTrue($product->hasReviews());
        $this->assertEquals(1, $product->reviews()->count());
    }

    public function test_average_rating_calculation()
    {
        $product = Product::factory()->create();

        $product->reviews()->createMany([
            ['rating' => 5, 'comment' => 'Excellent'],
            ['rating' => 4, 'comment' => 'Good'],
            ['rating' => 3, 'comment' => 'OK'],
        ]);

        $this->assertEquals(4.0, $product->averageRating());
    }
}
```

## Best Practices

1. **Single Responsibility** - Each trait should focus on one concern
2. **Naming Convention** - Use `Has*` for relationships, `Uses*` for behaviors
3. **Document Methods** - Add PHPDoc blocks explaining purpose and usage
4. **Boot Methods** - Use `boot{TraitName}` for lifecycle hooks
5. **Abstract Methods** - Define contracts when models need to implement specific methods
6. **Avoid Conflicts** - Check for method name collisions with other traits
7. **Type Hints** - Use strict types and return type declarations
8. **Testing** - Write unit tests for trait functionality

## Common Pitfalls

### Method Name Conflicts

```php
// BAD: Two traits with same method name
trait HasStatus {
    public function getStatus() { ... }
}

trait HasWorkflow {
    public function getStatus() { ... }
}

// GOOD: Rename or use alias
use HasStatus { getStatus as getStatusValue; }
use HasWorkflow { getStatus as getWorkflowStatus; }
```

### Accessing Model Properties

```php
// Make sure properties exist in model
trait NeedsOwner
{
    public function owner()
    {
        // Ensure 'owner_id' column exists in model table
        return $this->belongsTo(User::class, 'owner_id');
    }
}
```

### Boot Method Naming

```php
// WRONG: Missing trait name
protected static function boot() { }

// CORRECT: Include trait name
protected static function bootHasReviews() { }
```

## Example: Complete Trait

```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    /**
     * Available statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Scope: Published items only.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope: Draft items only.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Check if published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Publish this item.
     */
    public function publish(): bool
    {
        $this->status = self::STATUS_PUBLISHED;
        return $this->save();
    }

    /**
     * Archive this item.
     */
    public function archive(): bool
    {
        $this->status = self::STATUS_ARCHIVED;
        return $this->save();
    }
}
```

## Related Documentation

- [HasTags](./has-tags.md)
- [HasMultisite](./has-multisite.md)
- [HasTranslations](./has-translations.md)
- [Base Model](../model/base-model.md)
