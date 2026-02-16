# Base Manager

WNCMS 提供 `ModelManager` 作為所有 Manager classes 的基礎。它集中管理資料擷取、過濾、排序、快取與分頁。

## 概述

`ModelManager` 是一個抽象類別，位於：

```
Wncms\Services\Managers\ModelManager
```

每個具體的 Manager（如 `PostManager`、`LinkManager`）都應該繼承它，並實作必要的方法：`getModelClass()` 和 `buildListQuery()`。

## 主要特性

- **統一的查詢建構**：標準化的方式來建構查詢（tags、keywords、status 等）
- **快取支援**：整合 WNCMS Cache 機制以提升效能
- **網站範圍**：自動套用 multi-site 過濾（若啟用）
- **分頁與限制**：內建 `count`、`offset`、`page_size` 處理
- **Eager loading**：簡化關聯載入
- **可擴充性**：易於覆寫方法來客製化行為

## 必須實作的方法

### `getModelClass()`

```php
abstract public function getModelClass(): string;
```

**用途：**
回傳此 Manager 所處理的 Model class 名稱。

**範例：**

```php
public function getModelClass(): string
{
    return wncms()->getModelClass('post');
}
```

此方法使用 `wncms()->getModelClass()` 來尊重 `config/wncms.php` 中的 model 覆寫設定。

### `buildListQuery()`

```php
abstract protected function buildListQuery(array $options): mixed;
```

**用途：**
定義如何建構清單查詢。此方法套用過濾條件、排序、限制等。

**參數：**

| 參數       | 類型  | 說明                     |
| ---------- | ----- | ------------------------ |
| `$options` | array | 過濾、排序、分頁相關選項 |

**預期回傳：**
一個 Eloquent Builder 實例或原始 query。

**範例：**

```php
protected function buildListQuery(array $options): mixed
{
    $q = $this->query();

    $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? 'post_category');
    $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title', 'content']);
    $this->applyStatus($q, 'status', $options['status'] ?? 'published');
    $sort = $options['sort'] ?? 'id';
    $direction = $options['direction'] ?? 'desc';
    $this->applyOrdering($q, $sort, $direction, $sort === 'random');

    return $q;
}
```

## 常用公開方法

### `get(array $options = []): ?Model`

擷取單一記錄。

**常見選項：**

| 選項      | 類型   | 說明                                                      |
| --------- | ------ | --------------------------------------------------------- |
| `id`      | int    | 以 ID 擷取                                                |
| `slug`    | string | 以 slug 擷取                                              |
| `name`    | string | 以 name 擷取                                              |
| `withs`   | array  | 要 eager load 的 relations                                |
| `wheres`  | array  | 額外的 where 條件                                         |
| `cache`   | bool   | 是否使用快取（預設 `true`）                               |
| `seconds` | int    | 快取時間（秒），預設為 `gss('data_cache_time')` 或 `3600` |

**範例：**

```php
$post = wncms()->post()->get(['slug' => 'hello-world']);
```

### `getList(array $options = []): mixed`

擷取記錄集合或分頁結果。

**常見選項：**

| 選項        | 類型   | 說明                                                      |
| ----------- | ------ | --------------------------------------------------------- |
| `page`      | int    | 分頁頁碼                                                  |
| `page_size` | int    | 每頁筆數                                                  |
| `count`     | int    | 限制筆數（不分頁時使用，0 = 不限制）                      |
| `offset`    | int    | 跳過筆數                                                  |
| `cache`     | bool   | 是否使用快取（預設 `true`）                               |
| `seconds`   | int    | 快取時間（秒），預設為 `gss('data_cache_time')` 或 `3600` |
| `withs`     | array  | 要 eager load 的 relations                                |
| `wheres`    | array  | 額外的 where 條件                                         |
| `sort`      | string | 建議使用的排序欄位                                        |
| `direction` | string | 建議使用的排序方向：`asc` 或 `desc`                       |

**範例：**

```php
$posts = wncms()->post()->getList([
    'page_size' => 10,
    'status' => 'published',
    'sort' => 'created_at',
    'direction' => 'desc',
]);
```

### 排序參數規範

建議在 Manager 對外選項中優先使用 `sort` 與 `direction`。

### `run(array $options = []): mixed`

在沒有快取的情況下執行 `buildListQuery()`。

**範例：**

```php
$query = wncms()->post()->run([
    'tags' => ['news'],
    'count' => 5,
]);
```

回傳的是 Builder 實例，你可以繼續鏈結額外的查詢方法。

## 內建的過濾 Helpers

`ModelManager` 提供多個 helper 方法來簡化過濾邏輯：

| Method                                             | 說明                |
| -------------------------------------------------- | ------------------- |
| `applyIds($q, $column, $ids)`                      | 依 ID 過濾          |
| `applyExcludeIds($q, $column, $ids)`               | 排除特定 ID         |
| `applyTagFilter($q, $tags, $type)`                 | 依 tag 類型過濾     |
| `applyExcludedTags($q, $excludedTagIds)`           | 排除特定 tag ID     |
| `applyKeywordFilter($q, $keywords, $columns)`      | 套用關鍵字搜尋      |
| `applyStatus($q, $column, $status)`                | 依狀態過濾          |
| `applyWebsiteId($q, $websiteId)`                   | 依網站 ID 限定範圍  |
| `applyOrdering($q, $column, $sequence, $isRandom)` | 套用排序            |
| `applyLimit($q, $count)`                           | 限制筆數            |
| `applyOffset($q, $offset)`                         | 跳過記錄            |
| `applyWiths($q, $relations)`                       | Eager load 多個關聯 |

這些方法確保所有 WNCMS Manager 的行為一致。

### 多站點模式 Helper

Manager 也提供集中式模式檢查方法：

```php
public function getModelMultiWebsiteMode(): string
public function isModelWebsiteScoped(): bool
```

這些方法會優先使用模型方法（`getMultiWebsiteMode()` / `getWebsiteMode()`），因此可以尊重執行時模式覆寫。

## 顯式 `false` 選項值

`ModelManager` 現在會把顯式傳入的 `false` 視為有效過濾值（例如 `status => false`），不會再被當成空值忽略。

當你需要過濾布林欄位時，可直接這樣寫：

```php
$items = wncms()->advertisement()->getList([
    'status' => false,
]);
```

## Advertisement `type` 過濾範例

`AdvertisementManager` 現在支援依廣告類型過濾（單一或多個）。

單一類型可使用 `type`：

```php
$items = wncms()->advertisement()->getList([
    'type' => 'image',
]);
```

多類型可使用 `types`：

```php
$items = wncms()->advertisement()->getList([
    'types' => ['image', 'card'],
]);
```

## 快取支援

`ModelManager` 與 `wncms()->cache()` 整合，自動處理快取鍵生成與失效。

**設定屬性：**

| 屬性              | 類型         | 說明                                        |
| ----------------- | ------------ | ------------------------------------------- |
| `$cacheKeyPrefix` | string       | 用於生成快取鍵的前綴（例如 `wncms_post`）   |
| `$cacheTags`      | string/array | 快取標籤，用於批次失效（例如 `['posts']`）  |
| `$shouldAuth`     | bool         | 是否在快取鍵中包含使用者 ID（預設 `false`） |

**範例：**

```php
protected string $cacheKeyPrefix = 'wncms_product';
protected string|array $cacheTags = ['products'];
protected bool $shouldAuth = false;
```

快取會在 `get()` 與 `getList()` 中自動啟用，除非明確設定 `cache => false`。

## 擴充 ModelManager

要建立自訂 Manager：

1. 繼承 `ModelManager`
2. 實作 `getModelClass()`
3. 實作 `buildListQuery()`
4. （選用）覆寫 `get()` 或 `getList()` 來自訂行為

## 自訂 App Manager 解析

當你用動態方式呼叫 manager（例如 `wncms()->post()`）時，WNCMS 會依照這個順序解析：

1. `App\Services\Managers\{Name}Manager`
2. `Wncms\Services\Managers\{Name}Manager`

`App` manager 會優先透過 Laravel container 解析，因此建構子相依性可自動注入。

WNCMS 也支援單複數別名查找。例如 `wncms()->catalog_item()` 與 `wncms()->catalog_items()` 都可解析到 `App\Services\Managers\CatalogItemManager`。

**範例：**

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

詳細指南請參閱 [Create a Manager](create-a-manager.md)。

## 核心對齊說明

- Core 已移除 `BannerManager`。橫幅/廣告位請統一使用 `AdvertisementManager`（`wncms()->advertisement()`）。
- `StarterManager` 現在是基於 `ModelManager` 的腳手架範本，用於複製後快速建立新 manager。
- `SettingManager` 仍是特殊的鍵值管理器，不繼承 `ModelManager`，以維持 `gss()` / `uss()` 依賴的 `get($key, $fallback)`、`update($key, $value)` API。
- `SettingManager` 仍已對齊動態模型解析：查詢前會透過 `wncms()->getModelClass('setting')` 解析模型類別。

## Tag 模型相容性

`ModelManager::applyTagFilter()` 現在接受彈性輸入（`mixed`），並動態解析 tag 模型類別。

解析順序：

1. `config('wncms.models.tag.class')` 或 `config('wncms.models.tag')`
2. `wncms()->getModelClass('tag')`

這可相容 package/custom tag 模型實例，不再強制依賴繼承 `Wncms\Models\Tag`。
