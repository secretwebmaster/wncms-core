# Post Manager

The **PostManager** handles all data operations for the `Post` model, including filtering, relations, tag-based queries, caching, and related post retrieval. It extends WNCMS’s core `ModelManager` to provide a unified, high-performance query layer.

## Class Overview

```php
namespace Wncms\Services\Managers;

class PostManager extends ModelManager
```

**Purpose:**
Manage all `Post` model data queries for backend, frontend, and API controllers with consistent behavior.

## Key Properties

| Property          | Type         | Description                                  |
| ----------------- | ------------ | -------------------------------------------- |
| `$cacheKeyPrefix` | string       | Used for cache key generation (`wncms_post`) |
| `$defaultTagType` | string       | Default tag type (`post_category`)           |
| `$shouldAuth`     | bool         | Whether to include user-based cache scope    |
| `$cacheTags`      | string/array | Cache tags used for invalidation (`posts`)   |

## getModelClass()

```php
public function getModelClass(): string
{
    return wncms()->getModelClass('post');
}
```

Returns the class name of the model handled by this manager.
WNCMS automatically resolves custom overrides defined in `config/wncms.php`.

## get()

```php
public function get(array $options = []): ?Model
```

Retrieves a single post by `id`, `slug`, or `name`.

**Behavior:**

- Automatically eager-loads: `media`, `comments`, `tags`, and `translations`.
- Accepts standard `ModelManager::get()` options (`id`, `slug`, `wheres`, `cache`, etc.).

**Example:**

```php
$post = wncms()->post()->get(['slug' => 'my-first-post']);
```

## getList()

```php
public function getList(array $options = []): Collection|LengthAwarePaginator
```

Retrieves a collection or paginated list of posts.
It merges the same default relations as `get()`.

**Example:**

```php
$posts = wncms()->post()->getList([
    'status' => 'published',
    'order' => 'created_at',
    'sequence' => 'desc',
    'page_size' => 10,
]);
```

**Default eager-loaded relations:**
`media`, `comments`, `tags`, `translations`

## getRelated()

```php
public function getRelated(array|Model|int|null $post, array $options = []): Collection|LengthAwarePaginator
```

Fetches related posts that share the same tag type (default: `post_category`).

**Parameters:**

| Key                    | Type               | Description                                  |
| ---------------------- | ------------------ | -------------------------------------------- |
| `$post`                | Model / ID / Array | The reference post                           |
| `$options['tag_type']` | string             | Tag type to match (default: `post_category`) |
| `$options['cache']`    | bool               | Enable or disable caching                    |

**Behavior:**

- Automatically excludes the current post ID.
- Matches tag names from `$post->tagsWithType($tagType)`.

**Example:**

```php
$related = wncms()->post()->getRelated($post, [
    'count' => 6,
    'is_random' => true,
]);
```

## buildListQuery()

```php
protected function buildListQuery(array $options): mixed
```

Defines how `PostManager` builds queries for listing posts.
This is the core of the `getList()` process.

### Supported Options

| Option              | Type         | Description                              |
| ------------------- | ------------ | ---------------------------------------- |
| `tags`              | array/string | Filter posts by tag names or IDs         |
| `tag_type`          | string       | Tag type (default: `post_category`)      |
| `keywords`          | array/string | Search in title, label, excerpt, content |
| `count`             | int          | Limit number of posts                    |
| `offset`            | int          | Skip posts before limit                  |
| `order`             | string       | Column to order by (default: `id`)       |
| `sequence`          | string       | asc / desc                               |
| `status`            | string       | Post status (default: `published`)       |
| `wheres`            | array        | Extra where conditions                   |
| `website_id`        | int          | Scope by website                         |
| `excluded_post_ids` | array        | Exclude post IDs                         |
| `excluded_tag_ids`  | array        | Exclude tag IDs                          |
| `ids`               | array        | Filter by IDs                            |
| `select`            | array        | Select specific columns                  |
| `withs`             | array        | Additional relations                     |
| `is_random`         | bool         | Randomize order                          |

## Query Behavior

- **Tag Filter:**
  Uses `applyTagFilter()` to include posts with specified tags.

- **Keyword Search:**
  Searches across multiple columns:

  - `title`, `label`, `excerpt`, `content`

- **Website Scoping:**
  Applies `applyWebsiteId()` for multi-site mode.

- **Exclusion:**
  Skips posts or tags defined in `excluded_post_ids` or `excluded_tag_ids`.

- **Ordering:**
  Defaults to `orderBy('id', 'desc')`, or random order when `is_random = true`.

- **Status Filter:**
  Only includes posts with `status = 'published'` by default.

- **Comment Count:**
  Adds `withCount('comments')` automatically to each result.

## Example Usage

**1. Fetch a published post by slug**

```php
$post = wncms()->post()->get(['slug' => 'hello-world']);
```

**2. List latest 5 posts in a category**

```php
$posts = wncms()->post()->getList([
    'tags' => 'news',
    'tag_type' => 'post_category',
    'count' => 5,
]);
```

**3. Get related posts**

```php
$related = wncms()->post()->getRelated($post, [
    'count' => 4,
    'is_random' => true,
]);
```

**4. Paginate posts**

```php
$posts = wncms()->post()->getList([
    'page_size' => 10,
    'page' => request('page', 1),
]);
```

## Summary

`PostManager` extends the core `ModelManager` to provide:

- Automatic relation loading (`media`, `tags`, `translations`, etc.)
- Multi-site and tag-type filtering
- Cached queries for performance
- Related post detection
- Unified interface for backend, frontend, and API usage
