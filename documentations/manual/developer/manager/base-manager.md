# Base Manager

The **Base Manager** in WNCMS (named `ModelManager`) is an abstract service class that handles model data fetching, filtering, and caching in a standardized way. All specific managers like `PostManager`, `LinkManager`, or custom package managers extend this class to reuse its query and caching logic.

## Purpose

Managers serve as a middle layer between controllers and models. They centralize data access, apply query filters, handle caching, and prepare paginated or collection-based results. This allows developers to keep controllers thin and consistent.

## Base Class

```php
namespace Wncms\Services\Managers;

abstract class ModelManager
```

Every manager must extend `ModelManager` and implement these required methods:

- `getModelClass(): string` — return the model class handled by the manager.
- `buildListQuery(array $options): mixed` — define how to query a list of models.

## Example Structure

```php
class PostManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_post';
    protected string|array $cacheTags = ['posts'];
    protected bool $shouldAuth = false;

    public function getModelClass(): string
    {
        return wncms()->getModelClass('post');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query()->where('status', 'published');
        $this->applyOrdering($q, 'created_at');
        return $q;
    }
}
```

## Key Responsibilities

### Data Retrieval

#### `get(array $options = []): ?Model`

Fetch a single model record using any of these identifiers:

| Option   | Type   | Description                  |
| -------- | ------ | ---------------------------- |
| `id`     | int    | Model ID                     |
| `slug`   | string | Slug field                   |
| `name`   | string | Name field                   |
| `withs`  | array  | Relations to eager-load      |
| `wheres` | array  | Extra where conditions       |
| `cache`  | bool   | Enable cache (default: true) |

This method returns a single `Model` instance or `null`.

### Data Listing

#### `getList(array $options = []): Collection|Paginator`

Get a filtered list of models with optional pagination or caching.

Common options include:

| Option             | Type           | Description              |
| ------------------ | -------------- | ------------------------ |
| `page_size`        | int            | Number of items per page |
| `page`             | int            | Current page number      |
| `order`            | string         | Order column             |
| `sequence`         | string         | asc / desc               |
| `tags`             | array / string | Filter by tag name or ID |
| `excluded_tag_ids` | array / string | Exclude tag IDs          |
| `keywords`         | array / string | Keyword search           |
| `count`            | int            | Limit count              |
| `is_random`        | bool           | Randomize order          |
| `cache`            | bool           | Use cache                |

### Counting

#### `getCount(array $options = []): int`

Count models matching filters.

### Filtering Helpers

These methods help apply query conditions consistently:

| Method                 | Description                         |
| ---------------------- | ----------------------------------- |
| `applyTagFilter()`     | Include models with certain tags    |
| `applyExcludedTags()`  | Exclude models with certain tag IDs |
| `applyKeywordFilter()` | Search across multiple columns      |
| `applyIds()`           | Filter by IDs                       |
| `applyExcludeIds()`    | Exclude IDs                         |
| `applyStatus()`        | Filter by a status column           |
| `applyWebsiteId()`     | Scope by website in multi-site mode |
| `applyWiths()`         | Apply eager loading                 |
| `applyOrdering()`      | Apply order or random order         |
| `applySelect()`        | Limit selected columns              |
| `applyOffset()`        | Apply offset                        |
| `applyLimit()`         | Limit result count                  |

### Explicit `false` option values

`ModelManager` now treats explicit `false` values as valid option inputs for boolean-style filters (for example `status => false`) instead of dropping them as "empty" values.

Use this when you need to filter boolean-backed columns:

```php
$items = wncms()->advertisement()->getList([
    'status' => false,
]);
```

### Query Lifecycle

1. **`buildListQuery()`** — create the base query.
2. **`finalizeResult()`** — handle pagination, count limit, and get results.

### Caching Support

- WNCMS cache system is integrated with tag-based invalidation.
- Controlled by settings `enable_cache` and `data_cache_time`.

| Method                | Purpose                                     |
| --------------------- | ------------------------------------------- |
| `getCacheKeyPrefix()` | Returns cache key prefix                    |
| `getCacheTag()`       | Returns cache tag(s) for invalidation       |
| `getCacheKey()`       | Build unique cache key per query            |
| `getCacheTime()`      | Retrieve cache duration                     |
| `run()`               | Run arbitrary function with caching support |

### Website and Multi-site Support

`applyWebsiteId()` automatically scopes the query when `multi_website` is enabled or when the model supports website filtering through `applyWebsiteScope()`.

### Tag Support

#### `getAllowedTagTypes(): array`

Fetches allowed tag types from the model and formats them for frontend display.

Returns structured array:

```php
[
  [
    'full'  => 'post_category',
    'key'   => 'category',
    'label' => __('wncms::word.post_category'),
  ],
]
```

## Extending `ModelManager`

To build your own manager:

1. Create a class under `app/Services/Managers/`.
2. Extend `ModelManager`.
3. Implement `getModelClass()` and `buildListQuery()`.
4. Use helper methods like `applyTagFilter()`, `applyKeywordFilter()`, and `applyOrdering()`.

## Custom App Manager Resolution

When calling managers dynamically (for example `wncms()->post()`), WNCMS resolves manager classes in this order:

1. `App\Services\Managers\{Name}Manager`
2. `Wncms\Services\Managers\{Name}Manager`

`App` managers are resolved through Laravel's container first, so constructor dependencies are injected automatically.

WNCMS also accepts singular/plural aliases during lookup. For example, both `wncms()->catalog_item()` and `wncms()->catalog_items()` can resolve `App\Services\Managers\CatalogItemManager`.

## Common Use Cases

- Filter and paginate models for frontend lists.
- Cache complex queries for speed.
- Apply global multi-site or tag filters.
- Expose consistent query results to API controllers.
