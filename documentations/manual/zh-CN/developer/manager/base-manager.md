# Base Manager

WNCMS 提供 `ModelManager` 作为所有 Manager classes 的基础。它集中管理资料撷取、过滤、排序、快取与分页。

## 概述

`ModelManager` 是一个抽象类别，位于：

```
Wncms\Services\Managers\ModelManager
```

每个具体的 Manager（如 `PostManager`、`LinkManager`）都应该继承它，并实作必要的方法：`getModelClass()` 和 `buildListQuery()`。

## 主要特性

- **统一的查询建构**：标准化的方式来建构查询（tags、keywords、status 等）
- **快取支援**：整合 WNCMS Cache 机制以提升效能
- **网站范围**：自动套用 multi-site 过滤（若启用）
- **分页与限制**：内建 `count`、`offset`、`page_size` 处理
- **Eager loading**：简化关联载入
- **可扩充性**：易于覆写方法来客制化行为

## 必须实作的方法

### `getModelClass()`

```php
abstract public function getModelClass(): string;
```

**用途：**
回传此 Manager 所处理的 Model class 名称。

**范例：**

```php
public function getModelClass(): string
{
    return wncms()->getModelClass('post');
}
```

此方法使用 `wncms()->getModelClass()` 来尊重 `config/wncms.php` 中的 model 覆写设定。

### `buildListQuery()`

```php
abstract protected function buildListQuery(array $options): mixed;
```

**用途：**
定义如何建构清单查询。此方法套用过滤条件、排序、限制等。

**参数：**

| 参数       | 类型  | 说明                     |
| ---------- | ----- | ------------------------ |
| `$options` | array | 过滤、排序、分页相关选项 |

**预期回传：**
一个 Eloquent Builder 实例或原始 query。

**范例：**

```php
protected function buildListQuery(array $options): mixed
{
    $q = $this->query();

    $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? 'post_category');
    $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title', 'content']);
    $this->applyStatus($q, 'status', $options['status'] ?? 'published');
    $this->applyOrdering($q, $options['order'] ?? 'id', $options['sequence'] ?? 'desc');

    return $q;
}
```

## 常用公开方法

### `get(array $options = []): ?Model`

撷取单一记录。

**常见选项：**

| 选项      | 类型   | 说明                                                      |
| --------- | ------ | --------------------------------------------------------- |
| `id`      | int    | 以 ID 撷取                                                |
| `slug`    | string | 以 slug 撷取                                              |
| `name`    | string | 以 name 撷取                                              |
| `withs`   | array  | 要 eager load 的 relations                                |
| `wheres`  | array  | 额外的 where 条件                                         |
| `cache`   | bool   | 是否使用快取（预设 `true`）                               |
| `seconds` | int    | 快取时间（秒），预设为 `gss('data_cache_time')` 或 `3600` |

**范例：**

```php
$post = wncms()->post()->get(['slug' => 'hello-world']);
```

### `getList(array $options = []): mixed`

撷取记录集合或分页结果。

**常见选项：**

| 选项        | 类型   | 说明                                                      |
| ----------- | ------ | --------------------------------------------------------- |
| `page`      | int    | 分页页码                                                  |
| `page_size` | int    | 每页笔数                                                  |
| `count`     | int    | 限制笔数（不分页时使用，0 = 不限制）                      |
| `offset`    | int    | 跳过笔数                                                  |
| `cache`     | bool   | 是否使用快取（预设 `true`）                               |
| `seconds`   | int    | 快取时间（秒），预设为 `gss('data_cache_time')` 或 `3600` |
| `withs`     | array  | 要 eager load 的 relations                                |
| `wheres`    | array  | 额外的 where 条件                                         |
| `order`     | string | 排序栏位                                                  |
| `sequence`  | string | `asc` 或 `desc`                                           |

**范例：**

```php
$posts = wncms()->post()->getList([
    'page_size' => 10,
    'status' => 'published',
    'order' => 'created_at',
    'sequence' => 'desc',
]);
```

### `run(array $options = []): mixed`

在没有快取的情况下执行 `buildListQuery()`。

**范例：**

```php
$query = wncms()->post()->run([
    'tags' => ['news'],
    'count' => 5,
]);
```

回传的是 Builder 实例，你可以继续链结额外的查询方法。

## 内建的过滤 Helpers

`ModelManager` 提供多个 helper 方法来简化过滤逻辑：

| Method                                             | 说明                |
| -------------------------------------------------- | ------------------- |
| `applyIds($q, $column, $ids)`                      | 依 ID 过滤          |
| `applyExcludeIds($q, $column, $ids)`               | 排除特定 ID         |
| `applyTagFilter($q, $tags, $type)`                 | 依 tag 类型过滤     |
| `applyExcludedTags($q, $excludedTagIds)`           | 排除特定 tag ID     |
| `applyKeywordFilter($q, $keywords, $columns)`      | 套用关键字搜寻      |
| `applyStatus($q, $column, $status)`                | 依状态过滤          |
| `applyWebsiteId($q, $websiteId)`                   | 依网站 ID 限定范围  |
| `applyOrdering($q, $column, $sequence, $isRandom)` | 套用排序            |
| `applyLimit($q, $count)`                           | 限制笔数            |
| `applyOffset($q, $offset)`                         | 跳过记录            |
| `applyWiths($q, $relations)`                       | Eager load 多个关联 |

这些方法确保所有 WNCMS Manager 的行为一致。

## 显式 `false` 选项值

`ModelManager` 现在会把显式传入的 `false` 视为有效过滤值（例如 `status => false`），而不是当作空值忽略。

当你需要过滤布林栏位时，可直接这样写：

```php
$items = wncms()->advertisement()->getList([
    'status' => false,
]);
```

## 快取支援

`ModelManager` 与 `wncms()->cache()` 整合，自动处理快取键生成与失效。

**设定属性：**

| 属性              | 类型         | 说明                                        |
| ----------------- | ------------ | ------------------------------------------- |
| `$cacheKeyPrefix` | string       | 用于生成快取键的前缀（例如 `wncms_post`）   |
| `$cacheTags`      | string/array | 快取标签，用于批次失效（例如 `['posts']`）  |
| `$shouldAuth`     | bool         | 是否在快取键中包含使用者 ID（预设 `false`） |

**范例：**

```php
protected string $cacheKeyPrefix = 'wncms_product';
protected string|array $cacheTags = ['products'];
protected bool $shouldAuth = false;
```

快取会在 `get()` 与 `getList()` 中自动启用，除非明确设定 `cache => false`。

## 扩充 ModelManager

要建立自订 Manager：

1. 继承 `ModelManager`
2. 实作 `getModelClass()`
3. 实作 `buildListQuery()`
4. （选用）覆写 `get()` 或 `getList()` 来自订行为

## 自订 App Manager 解析

当你用动态方式呼叫 manager（例如 `wncms()->post()`）时，WNCMS 会依照这个顺序解析：

1. `App\Services\Managers\{Name}Manager`
2. `Wncms\Services\Managers\{Name}Manager`

`App` manager 会优先透过 Laravel container 解析，因此建构子相依性可自动注入。

WNCMS 也支援单复数别名查找。例如 `wncms()->catalog_item()` 与 `wncms()->catalog_items()` 都可解析到 `App\Services\Managers\CatalogItemManager`。

**范例：**

```php
namespace App\Services\Managers;

use Wncms\Services\Managers\ModelManager;

class ProductManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_product';
    protected string|array $cacheTags = ['products'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('product');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $this->applyTagFilter($q, $options['tags'] ?? [], 'product_category');
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['name', 'description']);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');

        return $q;
    }
}
```

详细指南请参阅 [Create a Manager](create-a-manager.md)。
