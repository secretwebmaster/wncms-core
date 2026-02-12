# Adding Relationships to Other Models

WNCMS allows any package to extend **core models** (User, Post, Website, etc.) without modifying the core code directly.
This is achieved using the `MacroableModels` facade, which dynamically adds Eloquent relationships, accessors, and custom methods to existing models at runtime.

This document explains how to use `MacroableModels` to attach relationships between:

- **Novel** model
- **NovelChapter** model
- **User** model (example: user → novels)

## 1. When to Use MacroableModels

Use macros when your package needs to:

- Add relationships to core models.
- Add computed attributes (getXxxAttribute).
- Add helper methods (e.g., `hasPurchased()`, `totalCredits()`, etc.).
- Avoid editing WNCMS core files.

Macros are executed in your package's `ServiceProvider`.

## 2. Basic Structure

Inside your package service provider:

```
use Wncms\Facades\MacroableModels;
```

Then register the macros in `boot()`:

```
use Wncms\Facades\MacroableModels;

public function boot(): void
{
    try {
        $userModel = wncms()->getModelClass('user');

        if (class_exists($userModel)) {
            MacroableModels::addMacro($userModel, 'novels', function () {
                return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\Novel::class);
            });
        }
    } catch (\Throwable $e) {
        info('Novel macros not registered: ' . $e->getMessage());
    }
}
```

## 3. Example: Add Relationships for Novel Package

### Relationship 1: User hasMany Novels

```
MacroableModels::addMacro($userModel, 'novels', function () {
    return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\Novel::class);
});
```

Usage:

```
$user->novels;
```

### Relationship 2: Novel hasMany Chapters

```
MacroableModels::addMacro(Novel::class, 'chapters', function () {
    return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\NovelChapter::class);
});
```

Usage:

```
$novel->chapters;
```

### Relationship 3: NovelChapter belongsTo Novel

```
MacroableModels::addMacro(NovelChapter::class, 'novel', function () {
    return $this->belongsTo(\Secretwebmaster\WncmsNovels\Models\Novel::class);
});
```

Usage:

```
$chapter->novel;
```

## 4. Example: Accessor for total chapters on Novel

```
MacroableModels::addMacro(Novel::class, 'getChapterCountAttribute', function () {
    $this->loadMissing('chapters');
    return $this->chapters->count();
});
```

Usage:

```
$novel->chapter_count;
```

## 5. Example: Helper method for Novel

```
MacroableModels::addMacro(Novel::class, 'latestChapter', function () {
    return $this->chapters()->orderBy('number', 'desc')->first();
});
```

Usage:

```
$novel->latestChapter();
```

## 6. Full Example for Your Novel Service Provider

```
public function boot(): void
{
    try {
        $userModel = wncms()->getModelClass('user');

        // User → Novels
        MacroableModels::addMacro($userModel, 'novels', function () {
            return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\Novel::class);
        });

        // Novel → Chapters
        MacroableModels::addMacro(\Secretwebmaster\WncmsNovels\Models\Novel::class, 'chapters', function () {
            return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\NovelChapter::class);
        });

        // Chapter → Novel
        MacroableModels::addMacro(\Secretwebmaster\WncmsNovels\Models\NovelChapter::class, 'novel', function () {
            return $this->belongsTo(\Secretwebmaster\WncmsNovels\Models\Novel::class);
        });

        // Accessor: chapter_count
        MacroableModels::addMacro(\Secretwebmaster\WncmsNovels\Models\Novel::class, 'getChapterCountAttribute', function () {
            $this->loadMissing('chapters');
            return $this->chapters->count();
        });

        // Method: latestChapter()
        MacroableModels::addMacro(\Secretwebmaster\WncmsNovels\Models\Novel::class, 'latestChapter', function () {
            return $this->chapters()->orderBy('number', 'desc')->first();
        });

    } catch (\Throwable $e) {
        info('Novel macros not registered: ' . $e->getMessage());
    }
}
```

## 7. Summary

| Model        | Added Relationship | Description                  |
| ------------ | ------------------ | ---------------------------- |
| User         | novels()           | User has many novels         |
| Novel        | chapters()         | Novel contains many chapters |
| NovelChapter | novel()            | A chapter belongs to a novel |
| Novel        | chapter_count      | Accessor for total chapters  |
| Novel        | latestChapter()    | Get most recent chapter      |

## 8. Notes

- All macros are fully compatible with caching, eager loading, and API resources.
- If a core model is overridden via config (`wncms()->getModelClass('user')`), macros automatically bind to the correct class.
- Macros are only registered once on boot.
