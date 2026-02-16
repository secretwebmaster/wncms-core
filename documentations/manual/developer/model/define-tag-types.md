# Define Tag Types on a Model

WNCMS models may declare **tag types** using a static `$tagMetas` property.  
This allows each model to describe which tag types it supports, along with optional route metadata.

## 1. Basic Structure

Define `$tagMetas` directly on the model:

```php
protected static array $tagMetas = [
    [
        'key'   => 'novel_category',
        'short' => 'category',
        'route' => 'frontend.novels.tag',
    ],
    [
        'key'   => 'novel_tag',
        'short' => 'tag',
        'route' => 'frontend.novels.tag',
    ],
];
```

Each item describes **one tag type**.

## 2. Field Explanation

| Field   | Required | Description                                                           |
| ------- | -------- | --------------------------------------------------------------------- |
| `key`   | Yes      | The tag type stored on the tag model (`tags.type`).                   |
| `short` | Yes      | A short alias used by your own code when identifying this type.       |
| `route` | Optional | A frontend route name your project may use when generating tag links. |

WNCMS does not enforce behavior beyond returning this metadata through `BaseModel::getTagMeta()`.

## 3. How `BaseModel` Processes Tag Types

`BaseModel` defines:

```php
protected static array $tagMetas = [];
```

When a model overrides it, `BaseModel::getTagMeta()` returns an array where each tag type is enriched with:

- `model` – the model class
- `model_key` – the model’s `$modelKey`
- `package` – the model’s `$packageId`
- `label` – a translation key generated from package and tag key

Your application or package can read this metadata and decide how to use it.

## 4. Example: Novel Model

```php
class Novel extends BaseModel implements HasMedia, ApiModelInterface
{
    protected static array $tagMetas = [
        [
            'key'   => 'novel_category',
            'short' => 'category',
            'route' => 'frontend.novels.tag',
        ],
        [
            'key'   => 'novel_tag',
            'short' => 'tag',
            'route' => 'frontend.novels.tag',
        ],
    ];
}
```

## 5. Empty Tag Definition

If a model does not support tags, leave it as:

```php
protected static array $tagMetas = [];
```

`BaseModel::getTagMeta()` will return an empty array.

## 6. Backend Tag-Type Selection and Active Models

In backend tag pages (`tags.index`, `tags.create`, `tags.edit`, and `tags.keywords.index`), type dropdown options are now filtered by active models.

- Source setting: `active_models` (System Settings -> display model)
- Matching rule: compare each tag meta model basename/model_key with enabled model values (case-insensitive normalization)
- Package model behavior: tag types registered by composer package models remain visible in backend tag pages, even if package models are not listed in `active_models`
- Fallback behavior: if `active_models` is empty, backend keeps showing all registered tag types

This keeps backend tag operations aligned with models currently enabled in admin navigation.

## 7. Backend Type Name Resolution (Composer Packages)

Backend tag pages now resolve type names through `TagManager` helper methods instead of hardcoded `wncms::word.{type}` only.

- Preferred path: `TagManager::getTagTypeDisplayName($tagType)`
- If a type is registered by a package model, backend uses:
1. `<package>::word.{tag_type}`
2. `<package>::word.{short}` (fallback)
- If package translations are missing, fallback goes to `wncms::word.{tag_type}` and then a humanized text.

This allows composer packages to provide custom tag type names consistently in:

- `tags.index` type filter and table rows
- `tags.keywords.index` type filter and table rows
