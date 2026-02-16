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

## 后台标签类型选择与启用模型

在后台标签页面（`tags.index`、`tags.create`、`tags.edit`、`tags.keywords.index`）中，标签类型下拉选项现在会依启用模型过滤。

- 设定来源：`active_models`（系统设定 -> 显示模型）
- 匹配规则：以 tag meta 的 model basename/model_key 与启用模型值做不区分大小写标准化比对
- 套件模型行为：composer 套件 model 注册的标签类型会保持显示，即使该套件 model 不在 `active_models` 列表内
- 回退行为：若 `active_models` 为空，后台仍显示所有已注册的标签类型

这样可让后台标签操作与目前在后台导航中启用的模型保持一致。

## 后台类型名称解析（Composer 套件）

后台标签页面现在会透过 `TagManager` 辅助方法解析类型名称，而不是只依赖硬编码 `wncms::word.{type}`。

- 首选方法：`TagManager::getTagTypeDisplayName($tagType)`
- 当类型来自套件 model 时，后台会依序尝试：
1. `<package>::word.{tag_type}`
2. `<package>::word.{short}`（回退）
- 若套件翻译不存在，再回退到 `wncms::word.{tag_type}`，最后使用可读化文字。

这样可让 composer 套件自订的标签类型名称在以下页面一致显示：

- `tags.index` 类型筛选与表格列
- `tags.keywords.index` 类型筛选与表格列
