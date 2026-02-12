# HasMultisite Trait

The `HasMultisite` trait enables WNCMS models to support multi-website scenarios, where the same model instance can belong to one or multiple websites depending on its **website mode** configuration.

## Overview

WNCMS supports three website modes for each model:

| Mode     | Description                                    |
| -------- | ---------------------------------------------- |
| `global` | Not scoped to any website (shared across all)  |
| `single` | Belongs to exactly one website                 |
| `multi`  | Can belong to multiple websites simultaneously |

The mode is configured in `config/wncms.php`:

```php
'models' => [
    'post' => [
        'class' => \Wncms\Models\Post::class,
        'website_mode' => 'multi',
    ],
    'page' => [
        'class' => \Wncms\Models\Page::class,
        'website_mode' => 'single',
    ],
],
```

## Core Methods

### Get Website Mode

```php
$mode = Post::getWebsiteMode();
// Returns: 'global', 'single', or 'multi'
```

### Access Website Relationship

```php
// Get all associated websites (BelongsToMany)
$websites = $post->websites;

// Get first website (convenience attribute)
$website = $post->website;
```

### Scope Queries by Website

```php
// Filter posts for current website
$posts = Post::forWebsite()->get();

// Filter posts for specific website
$posts = Post::forWebsite(5)->get();

// Using static helper
$query = Post::query();
Post::applyWebsiteScope($query, $websiteId);
$posts = $query->get();
```

## Binding and Unbinding Websites

### Bind Websites

```php
// Single website
$post->bindWebsites(1);

// Multiple websites
$post->bindWebsites([1, 2, 3]);
```

**Behavior:**

- **single mode**: Only the first website ID is bound
- **multi mode**: All provided website IDs are synced without detaching

### Unbind Websites

```php
// Remove specific websites
$post->unbindWebsites([1, 2]);

// Remove all websites
$post->unbindAllWebsites();
```

### Bind All Websites

```php
// Associate with all existing websites
$post->bindAllWebsites();
```

**Behavior:**

- **single mode**: Binds only the first website
- **multi mode**: Binds all websites

## Database Structure

The trait uses a polymorphic pivot table:

```sql
CREATE TABLE model_has_websites (
    model_type VARCHAR(255),
    model_id BIGINT,
    website_id BIGINT,
    PRIMARY KEY (model_type, model_id, website_id)
);
```

## Manager Integration

WNCMS managers automatically apply website scoping:

```php
class PostManager extends ModelManager
{
    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        // Apply website filter
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        return $q;
    }
}
```

The `applyWebsiteId()` method internally uses `applyWebsiteScope()`.

## Usage Examples

### Create Post for Specific Website

```php
$post = Post::create([
    'title' => 'Hello World',
    'content' => 'Post content',
]);

// Bind to current website
$post->bindWebsites(wncms()->website()->id());
```

### Query Posts for Current Website

```php
$posts = Post::forWebsite()->where('status', 'published')->get();
```

### Multi-Website Product

```php
$product = Product::find(1);

// Assign to multiple websites
$product->bindWebsites([1, 2, 3]);

// Check which websites
foreach ($product->websites as $website) {
    echo $website->domain;
}
```

### Global Content (Not Website-Scoped)

If `website_mode` is `global`:

```php
// All queries return all records regardless of website
$banners = Banner::all();

// bindWebsites() has no effect
$banner->bindWebsites([1, 2]); // No-op
```

## Controller Example

```php
class PostController extends BackendController
{
    public function index()
    {
        $currentWebsiteId = wncms()->website()->id();

        $posts = Post::forWebsite($currentWebsiteId)
            ->where('status', 'published')
            ->paginate(20);

        return view('backend.posts.index', compact('posts'));
    }
}
```

## Advanced: Manual Pivot Access

```php
// Access pivot data
$website = $post->websites()->wherePivot('website_id', 1)->first();

// Attach with timestamp
$post->websites()->attach(1, ['created_at' => now()]);

// Sync specific websites
$post->websites()->sync([1, 2, 3]);

// Sync without detaching
$post->websites()->syncWithoutDetaching([4, 5]);
```

## Migration Example

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateModelHasWebsitesTable extends Migration
{
    public function up()
    {
        Schema::create('model_has_websites', function (Blueprint $table) {
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('website_id');

            $table->primary(['model_type', 'model_id', 'website_id']);

            $table->foreign('website_id')
                ->references('id')
                ->on('websites')
                ->onDelete('cascade');
        });
    }
}
```

## Best Practices

1. **Configure Mode Early** - Set `website_mode` in `config/wncms.php` before creating records
2. **Use Scopes** - Always use `forWebsite()` scope in multi-site scenarios
3. **Bind After Creation** - Create model first, then bind websites
4. **Manager Integration** - Let managers handle website filtering automatically
5. **Check Mode** - Use `getWebsiteMode()` when behavior depends on configuration

## Related Documentation

- [Base Model](../model/base-model.md)
- [ModelManager](../manager/base-manager.md)
- [Link Manager](../manager/link-manager.md) - Website scoping example
