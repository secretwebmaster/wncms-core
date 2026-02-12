# Link Manager

`LinkManager` 是 `Link` model 的资料存取层。它继承 `ModelManager` 来提供一致的过滤、排序、multi-site 范围、tag 处理、eager-loading 与快取友善的清单撷取。

## Class 概述

```php
namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Builder;

class LinkManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_link';
    protected string $defaultTagType = 'link_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['links'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('link');
    }
}
```

## 预设值与惯例

- **Model 解析**：`getModelClass()` 尊重透过 `config/wncms.php` 的 model 覆写设定。
- **快取**：使用 `cacheKeyPrefix = wncms_link` 与快取 tag `links`。
- **Tags**：预设 tag 类型为 `link_category`。
- **Auth scoping**：`$shouldAuth = false`（快取键不包含使用者范围）。

## 公开方法

### `get(array $options = []): ?Model`

委派给 `ModelManager::get()`。可使用 `id`、`slug`、`name`、`withs`、`wheres`、`cache` 等选项。

### `getList(array $options = []): mixed`

委派给 `ModelManager::getList()`，由下方的 `buildListQuery()` 支援。

### `getBySlug(string $slug, ?int $websiteId = null)`

便利方法，依 slug 撷取单一 link，可选择性指定 website 范围。

```php
$link = wncms()->link()->getBySlug('my-link', websiteId: 12);
```

## 过滤与选项

`buildListQuery()` 支援以下选项：

**`ids`**

- **类型**：array / string / int
- **用途**：包含特定的 link ID

**`excluded_ids`**

- **类型**：array / string / int
- **用途**：排除特定的 link ID

**`excluded_tag_ids`**

- **类型**：array / string / int
- **用途**：排除拥有这些 tag ID 的 links

**`tags`**

- **类型**：array / string / int
- **用途**：依 tags 过滤（名称、ID 或 Tag models）

**`tag_type`**

- **类型**：string / null
- **用途**：要使用的 Tag 类型，预设为 `link_category`

**`keywords`**

- **类型**：array / string
- **用途**：在 `name` 栏位进行关键字搜寻

**`wheres`**

- **类型**：array
- **用途**：额外的 where 条件与 closures

**`status`**

- **类型**：string / null
- **用途**：Link 状态（预设 `active`）

**`withs`**

- **类型**：array
- **用途**：要 eager load 的 relations

**`order`**

- **类型**：string
- **用途**：排序栏位。特殊值：`random`、`total_views_yesterday`

**`sequence`**

- **类型**：string
- **用途**：`asc` 或 `desc`（预设 `desc`）

**`select`**

- **类型**：array / string
- **用途**：要选取的栏位（预设 `['links.*']`）

**`offset`**

- **类型**：int
- **用途**：批次处理的 offset

**`count`**

- **类型**：int
- **用途**：限制结果数量（0 = 不限制）

**`website_id`**

- **类型**：int / null
- **用途**：限定至特定 website（multi-site 感知）

额外行为：

- 总是 eager-load `media`。
- 套用 `distinct()` 以避免重复的资料列。
- 自动将排序栏位加入 `select` 子句以防止 SQL 错误。

## Tag 过滤

`applyTagFilter()` 接受：

- Tag ID（整数）
- Tag 名称（字串）
- Tag model 实例

它会解析至 `config('wncms.models.tag')` 设定的 model，并套用 `withAnyTags($names, $tagType)`。

要变更预设 tag 类型：

```php
$links = wncms()->link()->getList([
    'tags' => ['promo', 'sale'],
    'tag_type' => 'link_tag',
]);
```

## 关键字搜寻

`applyKeywordFilter()` 在 `name` 栏位进行搜寻：

```php
$links = wncms()->link()->getList([
    'keywords' => ['apple', 'store'],
]);
```

## Website 范围

`applyWebsiteId()` 在以下情况限定至 website：

- `gss('multi_website')` 已启用，或
- Link model 的 `website_mode` 为 `single`/`multi`，且
- Model 支援 `applyWebsiteScope()`。

```php
$links = wncms()->link()->getList([
    'website_id' => 3,
]);
```

## 排序规则

Manager 以特殊情况覆写 `applyOrdering()`：

### 随机排序

```php
$links = wncms()->link()->getList(['order' => 'random']);
```

使用 `inRandomOrder()`。

### 昨日浏览数

```php
$links = wncms()->link()->getList([
    'order' => 'total_views_yesterday',
    'sequence' => 'desc',
]);
```

行为：

- 首先加入 `orderBy('links.is_pinned', 'desc')`。
- Left join `total_views as tv_y` 于昨日日期：

  ```sql
  ON links.id = tv_y.link_id AND tv_y.date = YESTERDAY
  ```

- 依 `tv_y.total` 排序，接着 `links.id desc`。
- 确保 `tv_y.total` 若需要会出现在 `select` 中。

### 预设 / 自订栏位

```php
$links = wncms()->link()->getList([
    'order' => 'order',     // 或 links.* 上的任何栏位
    'sequence' => 'asc',
]);
```

行为：

- 依 `links.{order}` 排序，接着 `links.id desc`。
- 自动选取任何不在 `select` 中的排序栏位。

## 使用范例

依 ID 撷取：

```php
$link = wncms()->link()->get(['id' => 42]);
```

列出最新的 active links 并分页：

```php
$links = wncms()->link()->getList([
    'status'    => 'active',
    'page_size' => 20,
    'order'     => 'order',
    'sequence'  => 'asc',
]);
```

依 category 过滤并排除某些 tags：

```php
$links = wncms()->link()->getList([
    'tags'            => ['news', 'featured'],
    'tag_type'        => 'link_category',
    'excluded_tag_ids'=> [7, 9],
    'count'           => 8,
]);
```

当前 website 的随机精选 links：

```php
$links = wncms()->link()->getList([
    'website_id' => wncms()->website()->id(),
    'wheres'     => [['is_featured', true]],
    'order'      => 'random',
    'count'      => 6,
]);
```

昨日浏览数前几名的 links，置顶优先：

```php
$links = wncms()->link()->getList([
    'order'    => 'total_views_yesterday',
    'sequence' => 'desc',
    'count'    => 10,
]);
```

依 slug 取得：

```php
$link = wncms()->link()->getBySlug('my-affiliate-link', websiteId: null);
```
