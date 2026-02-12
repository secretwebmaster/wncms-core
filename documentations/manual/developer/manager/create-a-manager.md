# Create a Manager

A **Manager** in WNCMS acts as the logic layer between controllers and models.
It encapsulates query logic, caching, and filtering rules so your controllers remain clean and consistent.

This guide shows how to create a custom manager extending WNCMS’s `ModelManager`.

## Purpose of Managers

Managers are responsible for:

- Building and filtering database queries.
- Handling cache logic via `wncms()->cache()`.
- Applying tag, keyword, and website filters.
- Returning data as Eloquent collections or paginated lists.
- Ensuring consistent behavior across backend, frontend, and API.

## Location and Naming

Managers are stored under:

```
app/Services/Managers/
```

Each manager should follow the naming pattern:

```
{ModelName}Manager.php
```

For example:

```
PostManager.php
ProductManager.php
FaqManager.php
```

## Basic Example

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\ModelManager;
use Illuminate\Database\Eloquent\Builder;

class ProductManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_product';
    protected string $defaultTagType = 'product_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['products'];

    public function getModelClass(): string
    {
        // Return the Product model (custom or WNCMS default)
        return wncms()->getModelClass('product');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        // Apply filters
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? 'product_category');
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title', 'description']);
        $this->applyIds($q, 'id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'id', $options['excluded_ids'] ?? []);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        // Sorting and limit
        $this->applyOrdering($q, $options['order'] ?? 'id', $options['sequence'] ?? 'desc');
        $this->applyLimit($q, $options['count'] ?? 0);
        $this->applyOffset($q, $options['offset'] ?? 0);

        return $q;
    }
}
```

## Overridable Methods

When extending `ModelManager`, you can override these core methods:

| Method                           | Purpose                                        |
| -------------------------------- | ---------------------------------------------- |
| `getModelClass()`                | Return the model class handled by the manager. |
| `buildListQuery(array $options)` | Define how queries are built.                  |
| `get()`                          | Modify how a single record is fetched.         |
| `getList()`                      | Modify how list results are retrieved.         |
| `applyOrdering()`                | Customize ordering logic.                      |

## Common Helper Methods

You can use built-in query helpers inherited from `ModelManager`:

| Method                                             | Description            |
| -------------------------------------------------- | ---------------------- |
| `applyIds($q, $column, $ids)`                      | Filter by IDs          |
| `applyExcludeIds($q, $column, $ids)`               | Exclude IDs            |
| `applyTagFilter($q, $tags, $type)`                 | Filter by tag type     |
| `applyExcludedTags($q, $excludedTagIds)`           | Exclude tag IDs        |
| `applyKeywordFilter($q, $keywords, $columns)`      | Apply keyword search   |
| `applyStatus($q, $column, $status)`                | Filter by status       |
| `applyWebsiteId($q, $websiteId)`                   | Scope query by website |
| `applyOrdering($q, $column, $sequence, $isRandom)` | Apply sorting          |
| `applyLimit($q, $count)`                           | Apply limit            |
| `applyOffset($q, $offset)`                         | Skip records           |
| `applyWiths($q, $relations)`                       | Eager load relations   |

These utilities ensure that all WNCMS managers behave consistently.

## Example Usage in Controller

Once your manager is registered, you can use it directly through `wncms()`:

```php
$products = wncms()->product()->getList([
    'status' => 'active',
    'tags' => ['featured'],
    'order' => 'price',
    'sequence' => 'asc',
    'count' => 10,
]);
```

Or fetch a single record:

```php
$product = wncms()->product()->get(['slug' => 'premium-plan']);
```

## Tips for Custom Managers

- Always define a unique `$cacheKeyPrefix` and `$cacheTags`.
- Set `$defaultTagType` for tag filtering consistency.
- Reuse `ModelManager` helpers instead of raw Eloquent conditions.
- Use `applyWebsiteId()` when your model supports multi-website scope.
- Use `distinct()` in the query if joining tags or counts.
- When supporting boolean filters, pass explicit `false` values directly (for example `'status' => false`) and let `ModelManager` helpers apply them.

## Example Folder Structure

```
app/
 └── Services/
     └── Managers/
         ├── ProductManager.php
         ├── PostManager.php
         └── LinkManager.php
```

Each manager handles its respective model while sharing consistent caching, filtering, and list-building logic.
