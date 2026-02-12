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
