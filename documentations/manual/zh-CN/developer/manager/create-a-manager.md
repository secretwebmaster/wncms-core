# Create a Manager

WNCMS 中的 **Manager** 扮演 controllers 与 models 之间的逻辑层。
它封装查询逻辑、快取与过滤规则，使你的 controllers 保持整洁且一致。

本指南展示如何建立继承 WNCMS `ModelManager` 的自订 Manager。

## Managers 的用途

Managers 负责：

- 建构与过滤资料库查询。
- 透过 `wncms()->cache()` 处理快取逻辑。
- 套用 tag、keyword 与 website 过滤器。
- 以 Eloquent collections 或分页清单回传资料。
- 确保 backend、frontend 与 API 之间行为一致。

## 位置与命名

Managers 储存于：

```
app/Services/Managers/
```

每个 Manager 应遵循命名模式：

```
{ModelName}Manager.php
```

例如：

```
PostManager.php
ProductManager.php
FaqManager.php
```

## 基本范例

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\ModelManager;
use Illuminate\Database\Eloquent\Builder;

class ProductManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_product';
    protected string $defaultTagType = 'product_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['products'];

    public function getModelClass(): string
    {
        // 回传 Product model（自订或 WNCMS 预设）
        return wncms()->getModelClass('product');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        // 套用过滤器
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? 'product_category');
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title', 'description']);
        $this->applyIds($q, 'id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'id', $options['excluded_ids'] ?? []);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        // 排序与限制
        $this->applyOrdering($q, $options['order'] ?? 'id', $options['sequence'] ?? 'desc');
        $this->applyLimit($q, $options['count'] ?? 0);
        $this->applyOffset($q, $options['offset'] ?? 0);

        return $q;
    }
}
```

## 可覆写的方法

继承 `ModelManager` 时，你可以覆写这些核心方法：

| Method                           | 用途                              |
| -------------------------------- | --------------------------------- |
| `getModelClass()`                | 回传 Manager 处理的 model class。 |
| `buildListQuery(array $options)` | 定义如何建构查询。                |
| `get()`                          | 修改单一记录的撷取方式。          |
| `getList()`                      | 修改清单结果的撷取方式。          |
| `applyOrdering()`                | 自订排序逻辑。                    |

## 常用 Helper 方法

你可以使用从 `ModelManager` 继承的内建查询 helpers：

| Method                                             | 说明                 |
| -------------------------------------------------- | -------------------- |
| `applyIds($q, $column, $ids)`                      | 依 ID 过滤           |
| `applyExcludeIds($q, $column, $ids)`               | 排除 ID              |
| `applyTagFilter($q, $tags, $type)`                 | 依 tag 类型过滤      |
| `applyExcludedTags($q, $excludedTagIds)`           | 排除 tag ID          |
| `applyKeywordFilter($q, $keywords, $columns)`      | 套用关键字搜寻       |
| `applyStatus($q, $column, $status)`                | 依状态过滤           |
| `applyWebsiteId($q, $websiteId)`                   | 依 website 限定范围  |
| `applyOrdering($q, $column, $sequence, $isRandom)` | 套用排序             |
| `applyLimit($q, $count)`                           | 套用限制             |
| `applyOffset($q, $offset)`                         | 跳过记录             |
| `applyWiths($q, $relations)`                       | Eager load relations |

这些工具确保所有 WNCMS Managers 行为一致。

## 在 Controller 中使用范例

一旦注册你的 Manager，就可以直接透过 `wncms()` 使用：

```php
$products = wncms()->product()->getList([
    'status' => 'active',
    'tags' => ['featured'],
    'order' => 'price',
    'sequence' => 'asc',
    'count' => 10,
]);
```

或撷取单一记录：

```php
$product = wncms()->product()->get(['slug' => 'premium-plan']);
```

## 解析行为

建立 `app/Services/Managers/ProductManager.php` 之后，WNCMS 会透过 `wncms()->product()` 自动解析。

- `App\Services\Managers\*Manager` 优先于 core managers。
- Manager 会先透过 Laravel container 建立，因此可使用建构子相依注入。
- 单数与复数别名都可使用（`wncms()->product()` 与 `wncms()->products()`）。

## 自订 Managers 的提示

- 总是定义唯一的 `$cacheKeyPrefix` 与 `$cacheTags`。
- 设定 `$defaultTagType` 以确保 tag 过滤的一致性。
- 重复使用 `ModelManager` helpers 而非原始 Eloquent 条件。
- 当你的 model 支援 multi-website 范围时，使用 `applyWebsiteId()`。
- 若 join tags 或 counts，在查询中使用 `distinct()`。
- 若有布林过滤条件，可直接传入显式 `false`（例如 `'status' => false`），`ModelManager` helper 会正确套用。

## 范例资料夹结构

```
app/
 └── Services/
     └── Managers/
         ├── ProductManager.php
         ├── PostManager.php
         └── LinkManager.php
```

每个 Manager 处理其各自的 model，同时共享一致的快取、过滤与清单建构逻辑。
