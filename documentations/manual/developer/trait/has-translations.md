# HasTranslations Trait

The `HasTranslations` trait allows WNCMS models to store multilingual content for specific attributes. It uses a custom translation system integrated with WNCMS's localization infrastructure.

## Overview

Models using `HasTranslations` can store translated versions of fields like `title`, `content`, `description`, etc., supporting multiple languages simultaneously.

```php
use Wncms\Translatable\Traits\HasTranslations;

class Post extends BaseModel
{
    use HasTranslations;

    protected $translatable = ['title', 'content', 'excerpt'];
}
```

## Configuration

### Define Translatable Attributes

Specify which attributes should support translations:

```php
protected $translatable = [
    'title',
    'content',
    'description',
    'excerpt',
];
```

## Usage Examples

### Setting Translations

```php
$post = Post::create([
    'title' => 'Hello World', // Default locale
    'content' => 'Post content',
]);

// Set translation for specific locale
$post->setTranslation('title', 'zh_TW', '你好世界');
$post->setTranslation('content', 'zh_TW', '文章內容');
$post->save();

// Or use array syntax
$post->setTranslations('title', [
    'en' => 'Hello World',
    'zh_TW' => '你好世界',
    'ja' => 'こんにちは世界',
]);
$post->save();
```

### Retrieving Translations

```php
// Get translation for current locale
$title = $post->getTranslation('title');

// Get translation for specific locale
$titleZh = $post->getTranslation('title', 'zh_TW');

// Get translation with fallback
$title = $post->getTranslation('title', 'ja', false);
// If Japanese not available, returns default locale

// Access directly (uses current locale)
echo $post->title; // Automatically uses app()->getLocale()
```

### Check Translation Existence

```php
// Check if translation exists for field
if ($post->hasTranslation('title', 'zh_TW')) {
    echo $post->getTranslation('title', 'zh_TW');
}

// Get all translations for a field
$allTitles = $post->getTranslations('title');
// Returns: ['en' => 'Hello World', 'zh_TW' => '你好世界']
```

## Advanced Usage

### Translate Multiple Fields at Once

```php
$post->translate('zh_TW', [
    'title' => '你好世界',
    'content' => '文章內容',
    'excerpt' => '摘要',
]);
$post->save();
```

### Get All Translations

```php
// Get all translations for all fields
$translations = $post->translations;

foreach ($translations as $field => $locales) {
    foreach ($locales as $locale => $value) {
        echo "$field [$locale]: $value\n";
    }
}
```

### Forget Translation

```php
// Remove translation for specific locale and field
$post->forgetTranslation('title', 'zh_TW');
$post->save();

// Remove all translations for a field
$post->forgetAllTranslations('title');
$post->save();
```

## Automatic Locale Detection

The trait automatically uses `app()->getLocale()`:

```php
// In controller or route
app()->setLocale('zh_TW');

// This will automatically return Chinese title
echo $post->title;

// Switch locale
app()->setLocale('en');
echo $post->title; // Now returns English title
```

## Manager Integration

Managers can load translations automatically:

```php
class PostManager extends ModelManager
{
    public function get(array $options = []): ?Model
    {
        $q = $this->query();

        // Translations are automatically included when accessing attributes
        return $q->find($options['id']);
    }
}
```

## Database Storage

Translations can be stored in:

1. **JSON column** (recommended for smaller datasets)
2. **Separate `translations` table** (for complex scenarios)

### JSON Column Approach

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->json('title')->nullable();
    $table->json('content')->nullable();
    $table->timestamps();
});
```

### Separate Table Approach

```php
// Migration
Schema::create('translations', function (Blueprint $table) {
    $table->id();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->string('locale', 10);
    $table->string('key'); // field name
    $table->text('value');

    $table->unique(['model_type', 'model_id', 'locale', 'key']);
});
```

## Form Handling

### Backend Form Example

```blade
<form method="POST" action="{{ route('backend.posts.update', $post) }}">
    @foreach(['en', 'zh_TW', 'ja'] as $locale)
        <div class="locale-group">
            <h4>{{ $locale }}</h4>

            <label>Title ({{ $locale }})</label>
            <input type="text"
                   name="translations[{{ $locale }}][title]"
                   value="{{ $post->getTranslation('title', $locale, false) }}">

            <label>Content ({{ $locale }})</label>
            <textarea name="translations[{{ $locale }}][content]">
                {{ $post->getTranslation('content', $locale, false) }}
            </textarea>
        </div>
    @endforeach

    <button type="submit">Save</button>
</form>
```

### Controller Handling

```php
public function update(Request $request, Post $post)
{
    $validated = $request->validate([
        'translations.*.title' => 'required|string|max:255',
        'translations.*.content' => 'required|string',
    ]);

    foreach ($validated['translations'] as $locale => $fields) {
        foreach ($fields as $field => $value) {
            $post->setTranslation($field, $locale, $value);
        }
    }

    $post->save();

    return redirect()->back()->with('success', 'Translations updated');
}
```

## API Response Example

```php
// In API Resource
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title'),
            'content' => $this->getTranslation('content'),
            'translations' => [
                'title' => $this->getTranslations('title'),
                'content' => $this->getTranslations('content'),
            ],
        ];
    }
}
```

## Best Practices

1. **Define Early** - Set `$translatable` property before using the model
2. **Save After Setting** - Always call `save()` after `setTranslation()`
3. **Fallback Strategy** - Use fallback locales for missing translations
4. **Validate Input** - Validate translation data in forms
5. **Index Properly** - Index locale and model_type columns if using separate table
6. **Cache Wisely** - Translation data can be cached per locale

## Troubleshooting

### Translation Not Saving

```php
// Make sure to save after setting
$post->setTranslation('title', 'zh_TW', '標題');
$post->save(); // Don't forget this!
```

### Wrong Locale Returned

```php
// Check current locale
dd(app()->getLocale());

// Force specific locale
$title = $post->getTranslation('title', 'zh_TW');
```

### Missing Fallback

```php
// Enable fallback to default locale
config(['app.fallback_locale' => 'en']);

// Or handle manually
$title = $post->getTranslation('title', $locale, false)
    ?? $post->getTranslation('title', 'en');
```

## Related Documentation

- [Localization Overview](../locale/localization-overview.md)
- [Translation Files](../locale/translation-files.md)
- [Base Model](../model/base-model.md)
