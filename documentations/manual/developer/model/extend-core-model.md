# Extending WNCMS Core Models

## Morph Class

When you extend a WNCMS core model (for example, creating `App\Models\Page` that extends `Wncms\Models\Page`), you must understand how Laravel stores polymorphic relations.

Laravel saves the **class name** of the model into morph tables such as:

- `tags` (HasTags)
- `translations`
- `media`
- `options` (theme options, template options)

Example row in `options` table:

```
optionable_type = "Wncms\Models\Page"
optionable_id   = 5
```

If you extend this model like:

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
}
```

Laravel now sees **a completely different class name**:

```
App\Models\Page
```

This means:

- `Wncms\Models\Page` options will NOT load in `App\Models\Page`
- `App\Models\Page` options will save under a different morph type
- Tags, translations, media, and options all break because they no longer point to the same morph target

### How to Fix

Add this to your extended model:

```php
protected $morphClass = \Wncms\Models\Page::class;
```

This ensures:

- `App\Models\Page` stores **the same morph type** as WNCMS core
- Both classes share:
  - Options
  - Tags
  - Translations
  - Media
  - Template blocks
  - Theme options
- No data duplication
- No broken relations

### Example

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
    protected $morphClass = \Wncms\Models\Page::class;
}
```
