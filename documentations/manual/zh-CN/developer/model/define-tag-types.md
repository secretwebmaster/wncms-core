# 在 Model 上定义 Tag Types

WNCMS models 可以使用静态属性 `$tagMetas` 来宣告 **tag types**。  
这允许每个 model 描述它支援哪些 tag types，以及可选的路由元资料。

## 基本结构

直接在 model 上定义 `$tagMetas`：

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

每个项目描述**一种 tag type**。

## 栏位说明

| 栏位    | 必填 | 说明                                              |
| ------- | ---- | ------------------------------------------------- |
| `key`   | 是   | 储存在 tag model 上的 tag type（`tags.type`）。   |
| `short` | 是   | 您的程式码在识别此类型时使用的简短别名。          |
| `route` | 否   | 您的专案在生成 tag 连结时可能使用的前台路由名称。 |

WNCMS 除了透过 `BaseModel::getTagMeta()` 回传此元资料外，不强制执行任何行为。

## BaseModel 如何处理 Tag Types

`BaseModel` 定义：

```php
protected static array $tagMetas = [];
```

当 model 覆盖它时，`BaseModel::getTagMeta()` 会回传一个阵列，其中每个 tag type 都会被丰富以下资讯：

- `model` – model 类别
- `model_key` – model 的 `$modelKey`
- `package` – model 的 `$packageId`
- `label` – 从套件和 tag key 生成的翻译键

您的应用程式或套件可以读取此元资料并决定如何使用它。

## 范例：Novel Model

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

## 空的 Tag 定义

如果 model 不支援 tags，保持为空：

```php
protected static array $tagMetas = [];
```

`BaseModel::getTagMeta()` 将回传空阵列。
