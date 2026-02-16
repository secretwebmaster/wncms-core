# Create a Manager

WNCMS 中的 **Manager** 扮演 controllers 與 models 之間的邏輯層。
它封裝查詢邏輯、快取與過濾規則，使你的 controllers 保持整潔且一致。

本指南展示如何建立繼承 WNCMS `ModelManager` 的自訂 Manager。

## Managers 的用途

Managers 負責：

- 建構與過濾資料庫查詢。
- 透過 `wncms()->cache()` 處理快取邏輯。
- 套用 tag、keyword 與 website 過濾器。
- 以 Eloquent collections 或分頁清單回傳資料。
- 確保 backend、frontend 與 API 之間行為一致。

## 位置與命名

Managers 儲存於：

```
app/Services/Managers/
```

每個 Manager 應遵循命名模式：

```
{ModelName}Manager.php
```

例如：

```
PostManager.php
ProductManager.php
FaqManager.php
```

## 基本範例

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
        // 回傳 Product model（自訂或 WNCMS 預設）
        return wncms()->getModelClass('product');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        // 套用過濾器
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? 'product_category');
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title', 'description']);
        $this->applyIds($q, 'id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'id', $options['excluded_ids'] ?? []);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        // 排序與限制
        $sort = $options['sort'] ?? 'id';
        $direction = $options['direction'] ?? 'desc';
        $this->applyOrdering($q, $sort, $direction, $sort === 'random');
        $this->applyLimit($q, $options['count'] ?? 0);
        $this->applyOffset($q, $options['offset'] ?? 0);

        return $q;
    }
}
```

## 可覆寫的方法

繼承 `ModelManager` 時，你可以覆寫這些核心方法：

| Method                           | 用途                              |
| -------------------------------- | --------------------------------- |
| `getModelClass()`                | 回傳 Manager 處理的 model class。 |
| `buildListQuery(array $options)` | 定義如何建構查詢。                |
| `get()`                          | 修改單一記錄的擷取方式。          |
| `getList()`                      | 修改清單結果的擷取方式。          |
| `applyOrdering()`                | 自訂排序邏輯。                    |

## 常用 Helper 方法

你可以使用從 `ModelManager` 繼承的內建查詢 helpers：

| Method                                             | 說明                 |
| -------------------------------------------------- | -------------------- |
| `applyIds($q, $column, $ids)`                      | 依 ID 過濾           |
| `applyExcludeIds($q, $column, $ids)`               | 排除 ID              |
| `applyTagFilter($q, $tags, $type)`                 | 依 tag 類型過濾      |
| `applyExcludedTags($q, $excludedTagIds)`           | 排除 tag ID          |
| `applyKeywordFilter($q, $keywords, $columns)`      | 套用關鍵字搜尋       |
| `applyStatus($q, $column, $status)`                | 依狀態過濾           |
| `applyWebsiteId($q, $websiteId)`                   | 依 website 限定範圍  |
| `applyOrdering($q, $column, $sequence, $isRandom)` | 套用排序             |
| `applyLimit($q, $count)`                           | 套用限制             |
| `applyOffset($q, $offset)`                         | 跳過記錄             |
| `applyWiths($q, $relations)`                       | Eager load relations |

這些工具確保所有 WNCMS Managers 行為一致。

## 在 Controller 中使用範例

一旦註冊你的 Manager，就可以直接透過 `wncms()` 使用：

```php
$products = wncms()->product()->getList([
    'status' => 'active',
    'tags' => ['featured'],
    'sort' => 'price',
    'direction' => 'asc',
    'count' => 10,
]);
```

或擷取單一記錄：

```php
$product = wncms()->product()->get(['slug' => 'premium-plan']);
```

## 解析行為

建立 `app/Services/Managers/ProductManager.php` 之後，WNCMS 會透過 `wncms()->product()` 自動解析。

- `App\Services\Managers\*Manager` 優先於 core managers。
- Manager 會先透過 Laravel container 建立，因此可使用建構子相依注入。
- 單數與複數別名都可使用（`wncms()->product()` 與 `wncms()->products()`）。

## 自訂 Managers 的提示

- 總是定義唯一的 `$cacheKeyPrefix` 與 `$cacheTags`。
- 設定 `$defaultTagType` 以確保 tag 過濾的一致性。
- 重複使用 `ModelManager` helpers 而非原始 Eloquent 條件。
- 當你的 model 支援 multi-website 範圍時，使用 `applyWebsiteId()`。
- 若 join tags 或 counts，在查詢中使用 `distinct()`。
- 若有布林過濾條件，可直接傳入顯式 `false`（例如 `'status' => false`），`ModelManager` helper 會正確套用。
- 對外選項建議使用 `sort`/`direction`。

## 範例資料夾結構

```
app/
 └── Services/
     └── Managers/
         ├── ProductManager.php
         ├── PostManager.php
         └── LinkManager.php
```

每個 Manager 處理其各自的 model，同時共享一致的快取、過濾與清單建構邏輯。

## Core Starter 範本

Core 提供 `Wncms\Services\Managers\StarterManager` 作為可複製的 manager 腳手架，且已繼承 `ModelManager`。

複製後請至少修改：

1. 類別名稱與檔名改為 `{ModelName}Manager`。
2. `getModelClass()` 中的 model key。
3. `buildListQuery()` 內的預設 tag type、搜尋欄位與查詢選項映射。

請勿在未替換 model key 的情況下，直接將 `StarterManager` 用於正式邏輯。

## 特例：SettingManager

`SettingManager` 刻意不繼承 `ModelManager`。它是供 `gss()` / `uss()` 使用的鍵值服務，API 簽名與資源模型 manager 不同（例如 `get($key, $fallback)`）。
