# HasTags Trait

The `HasTags` trait comes from the **spatie/laravel-tags** package and is automatically included in all WNCMS models via `BaseModel`.

It allows models to be tagged with one or more tags, where each tag can have a specific **type** (e.g., `post_category`, `product_tag`, `link_category`).

## Overview

WNCMS uses the Spatie Tags package to provide a powerful and flexible tagging system. Every model extending `BaseModel` automatically inherits tag functionality.

```php
use Wncms\Models\BaseModel;

class Post extends BaseModel
{
    // HasTags is already included via BaseModel
}
```

## Core Concepts

### Tag Structure

Tags in WNCMS have:

- **Name**: The tag name (e.g., "Technology", "News")
- **Slug**: URL-friendly version
- **Type**: Categories tags by purpose (e.g., `post_category`, `post_tag`)
- **Locale**: Language-specific tags

### Tag Types

Tag types let you organize tags for different purposes on the same model:

```php
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
```

## Common Methods

### Attach Tags

```php
// Single tag
$post->attachTag('Technology');

// Multiple tags
$post->attachTags(['Technology', 'Programming', 'Laravel']);

// With type
$post->attachTag('News', 'post_category');
```

### Sync Tags (Replace All)

```php
// Replace all tags with new set
$post->syncTags(['PHP', 'Laravel']);

// Sync tags of specific type
$post->syncTagsWithType(['Featured', 'Popular'], 'post_tag');
```

### Detach Tags

```php
// Remove specific tag
$post->detachTag('Technology');

// Remove multiple tags
$post->detachTags(['PHP', 'Laravel']);

// Remove all tags
$post->detachTags();
```

### Query Models by Tags

```php
// Get posts with ANY of these tags
$posts = Post::withAnyTags(['Laravel', 'PHP'])->get();

// Get posts with ALL of these tags
$posts = Post::withAllTags(['Laravel', 'PHP'])->get();

// Get posts with specific tag type
$posts = Post::withAnyTags(['News', 'Tech'], 'post_category')->get();
```

### Retrieve Tags from Model

```php
// Get all tags
$tags = $post->tags;

// Get tags of specific type
$categories = $post->tagsWithType('post_category');

// Get tag names as array
$tagNames = $post->tags->pluck('name')->toArray();
```

## Tag Meta Configuration

In your model, define `$tagMetas` to register tag types:

```php
class Product extends BaseModel
{
    protected static array $tagMetas = [
        [
            'key'   => 'product_category',
            'short' => 'category',
            'route' => 'frontend.products.category',
        ],
        [
            'key'   => 'product_brand',
            'short' => 'brand',
            'route' => 'frontend.products.brand',
        ],
    ];
}
```

**Properties:**

- `key`: Unique identifier for the tag type
- `short`: Abbreviated name for internal use
- `route`: Frontend route name for tag pages

## Advanced Usage

### Tag Counts

```php
// Load models with tag count
$posts = Post::withCount('tags')->get();

foreach ($posts as $post) {
    echo $post->tags_count;
}
```

### Eager Loading

```php
// Avoid N+1 queries
$posts = Post::with('tags')->get();

foreach ($posts as $post) {
    foreach ($post->tags as $tag) {
        echo $tag->name;
    }
}
```

### Filter by Multiple Tag Types

```php
$posts = Post::query()
    ->withAnyTags(['Technology'], 'post_category')
    ->withAnyTags(['Featured'], 'post_tag')
    ->get();
```

## Creating Tags Programmatically

```php
use Wncms\Models\Tag;

// Create a tag
$tag = Tag::findOrCreate('Laravel', 'post_category');

// Create with locale
$tag = Tag::findOrCreate('技術', 'post_category', 'zh_TW');

// Create multiple tags
$tags = Tag::findOrCreateMultiple(['PHP', 'JavaScript'], 'post_tag');
```

## Manager Integration

WNCMS managers provide `applyTagFilter()` for consistent tag filtering:

```php
class PostManager extends ModelManager
{
    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $this->applyTagFilter(
            $q,
            $options['tags'] ?? [],
            $options['tag_type'] ?? 'post_category'
        );

        return $q;
    }
}
```

Usage:

```php
$posts = wncms()->post()->getList([
    'tags' => ['Technology', 'News'],
    'tag_type' => 'post_category',
]);
```

### Keyword Binding by Model Field

`TagManager::getTagsToBind()` supports field-level matching for auto-generate flows.

```php
$tagNames = wncms()->tag()->getTagsToBind(
    tagType: 'post_category',
    contents: [
        'title' => $request->title,
        'content' => $request->content,
        'excerpt' => $request->excerpt,
    ],
    column: 'name',
    modelKey: 'post'
);
```

In backend keyword binding (`tags.keywords.index`), each keyword set can choose `field` (for example `title` or `content`).  
When `field` is `*`, matching checks all provided content fields.

## Best Practices

1. **Define Tag Metas** - Always configure `$tagMetas` in your models
2. **Use Type Consistently** - Stick to naming conventions like `{model}_category`, `{model}_tag`
3. **Eager Load** - Use `with('tags')` to prevent N+1 queries
4. **Sync vs Attach** - Use `syncTags()` for complete replacement, `attachTag()` for additions
5. **Index Tags** - Ensure `name` and `type` columns are indexed for performance

## Related Documentation

- [Define Tag Types](../model/define-tag-types.md)
- [Base Model](../model/base-model.md)
- [Link Manager](../manager/link-manager.md) - Tag filtering examples
