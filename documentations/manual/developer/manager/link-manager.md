# Link Manager

`LinkManager` is the data-access layer for the `Link` model. It extends `ModelManager` to provide consistent filtering, ordering, multi-site scoping, tag handling, eager-loading, and cache-friendly list retrieval.

## Class overview

```php
namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Builder;

class LinkManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_link';
    protected string $defaultTagType = 'link_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['links'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('link');
    }
}
```

## Defaults and conventions

- **Model resolution**: `getModelClass()` honors model overrides via `config/wncms.php`.
- **Caching**: Uses `cacheKeyPrefix = wncms_link` and cache tag `links`.
- **Tags**: Default tag type is `link_category`.
- **Auth scoping**: `$shouldAuth = false` (cache keys are not user-scoped).

## Public methods

### `get(array $options = []): ?Model`

Delegates to `ModelManager::get()`. Use `id`, `slug`, `name`, `withs`, `wheres`, `cache` as needed.

### `getList(array $options = []): mixed`

Delegates to `ModelManager::getList()`, backed by `buildListQuery()` below.

### `getBySlug(string $slug, ?int $websiteId = null)`

Convenience method to fetch a single link by slug with optional website scoping.

```php
$link = wncms()->link()->getBySlug('my-link', websiteId: 12);
```

## Filtering and options

`buildListQuery()` supports the following options:

| Key | Type | Purpose |
| --- | --- | --- |
| `ids` | array/string/int | Include specific link IDs |
| `excluded_ids` | array/string/int | Exclude link IDs |
| `excluded_tag_ids` | array/string/int | Exclude links having these tag IDs |
| `tags` | array/string/int | Filter by tags (names, IDs, or Tag models) |
| `tag_type` | string/null | Tag type to use, defaults to `link_category` |
| `keywords` | array/string | Keyword search on `name` |
| `wheres` | array | Additional where conditions and closures |
| `status` | string/null | Link status (default `active`) |
| `withs` | array | Relations to eager load |
| `sort` | string | Preferred sort key. Special values: `random`, `total_views_yesterday` |
| `direction` | string | Preferred direction: `asc` or `desc` (default `desc`) |
| `order` | string | Backward-compatible alias of `sort` |
| `sequence` | string | Backward-compatible alias of `direction` |
| `select` | array/string | Columns to select (default `['links.*']`) |
| `offset` | int | Offset for batching |
| `count` | int | Limit result size (0 = no limit) |
| `website_id` | int/null | Scope to website (multi-site aware) |

Additional behavior:

- Always eager-loads `media`.
- Applies `distinct()` to avoid duplicated rows.
- Auto-adds orderBy columns to the `select` clause to prevent SQL errors.
- Invalid sort values are normalized to `sort`.

## Tag filtering

`applyTagFilter()` accepts:

- Tag IDs (ints)
- Tag names (strings)
- Tag model instances

It resolves to the model configured at `config('wncms.models.tag')` and applies `withAnyTags($names, $tagType)`.

To change the default tag type:

```php
$links = wncms()->link()->getList([
    'tags' => ['promo', 'sale'],
    'tag_type' => 'link_tag',
]);
```

## Keyword search

`applyKeywordFilter()` searches over the `name` column:

```php
$links = wncms()->link()->getList([
    'keywords' => ['apple', 'store'],
]);
```

## Website scoping

`applyWebsiteId()` scopes to a website if:

- `gss('multi_website')` is enabled, or
- The link model’s `website_mode` is `single`/`multi`, and
- The model supports `applyWebsiteScope()`.

```php
$links = wncms()->link()->getList([
    'website_id' => 3,
]);
```

## Ordering rules

The manager overrides `applyOrdering()` with special cases:

- **Random**

  ```php
  $links = wncms()->link()->getList(['sort' => 'random']);
  ```

  Uses `inRandomOrder()`.

- **Yesterday’s views**

  ```php
  $links = wncms()->link()->getList([
      'sort' => 'total_views_yesterday',
      'direction' => 'desc',
  ]);
  ```

  Behavior:

  - Temporarily disabled in current phase (no `wn_total_views` dependency).
  - Falls back to ordering by `links.sort` then `links.id desc`.
  - Keep this option for backward compatibility and future re-enable.

- **Default / custom column**

  ```php
  $links = wncms()->link()->getList([
      'sort' => 'sort',     // or any safe column on links.*
      'direction' => 'asc',
  ]);
  ```

  Behavior:

  - Orders by `links.{sort}` then `links.id desc`.
  - Auto-selects any order columns not present in `select`.

## Example usages

Fetch by ID:

```php
$link = wncms()->link()->get(['id' => 42]);
```

List latest active links with pagination:

```php
$links = wncms()->link()->getList([
    'status'    => 'active',
    'page_size' => 20,
    'sort'      => 'sort',
    'direction' => 'asc',
]);
```

Filter by category and exclude some tags:

```php
$links = wncms()->link()->getList([
    'tags'            => ['news', 'featured'],
    'tag_type'        => 'link_category',
    'excluded_tag_ids'=> [7, 9],
    'count'           => 8,
]);
```

Random featured links on current website:

```php
$links = wncms()->link()->getList([
    'website_id' => wncms()->website()->id(),
    'wheres'     => [['is_featured', true]],
    'sort'       => 'random',
    'count'      => 6,
]);
```

Top links by yesterday’s views, pinned first:

```php
$links = wncms()->link()->getList([
    'sort'      => 'total_views_yesterday',
    'direction' => 'desc',
    'count'    => 10,
]);
```

Get by slug:

```php
$link = wncms()->link()->getBySlug('my-affiliate-link', websiteId: null);
```
