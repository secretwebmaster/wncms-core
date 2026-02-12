# Link Manager

`LinkManager` 是 `Link` model 的資料存取層。它繼承 `ModelManager` 來提供一致的過濾、排序、multi-site 範圍、tag 處理、eager-loading 與快取友善的清單擷取。

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

## 預設值與慣例

- **Model 解析**：`getModelClass()` 尊重透過 `config/wncms.php` 的 model 覆寫設定。
- **快取**：使用 `cacheKeyPrefix = wncms_link` 與快取 tag `links`。
- **Tags**：預設 tag 類型為 `link_category`。
- **Auth scoping**：`$shouldAuth = false`（快取鍵不包含使用者範圍）。

## 公開方法

### `get(array $options = []): ?Model`

委派給 `ModelManager::get()`。可使用 `id`、`slug`、`name`、`withs`、`wheres`、`cache` 等選項。

### `getList(array $options = []): mixed`

委派給 `ModelManager::getList()`，由下方的 `buildListQuery()` 支援。

### `getBySlug(string $slug, ?int $websiteId = null)`

便利方法，依 slug 擷取單一 link，可選擇性指定 website 範圍。

```php
$link = wncms()->link()->getBySlug('my-link', websiteId: 12);
```

## 過濾與選項

`buildListQuery()` 支援以下選項：

**`ids`**

- **類型**：array / string / int
- **用途**：包含特定的 link ID

**`excluded_ids`**

- **類型**：array / string / int
- **用途**：排除特定的 link ID

**`excluded_tag_ids`**

- **類型**：array / string / int
- **用途**：排除擁有這些 tag ID 的 links

**`tags`**

- **類型**：array / string / int
- **用途**：依 tags 過濾（名稱、ID 或 Tag models）

**`tag_type`**

- **類型**：string / null
- **用途**：要使用的 Tag 類型，預設為 `link_category`

**`keywords`**

- **類型**：array / string
- **用途**：在 `name` 欄位進行關鍵字搜尋

**`wheres`**

- **類型**：array
- **用途**：額外的 where 條件與 closures

**`status`**

- **類型**：string / null
- **用途**：Link 狀態（預設 `active`）

**`withs`**

- **類型**：array
- **用途**：要 eager load 的 relations

**`order`**

- **類型**：string
- **用途**：排序欄位。特殊值：`random`、`total_views_yesterday`

**`sequence`**

- **類型**：string
- **用途**：`asc` 或 `desc`（預設 `desc`）

**`select`**

- **類型**：array / string
- **用途**：要選取的欄位（預設 `['links.*']`）

**`offset`**

- **類型**：int
- **用途**：批次處理的 offset

**`count`**

- **類型**：int
- **用途**：限制結果數量（0 = 不限制）

**`website_id`**

- **類型**：int / null
- **用途**：限定至特定 website（multi-site 感知）

額外行為：

- 總是 eager-load `media`。
- 套用 `distinct()` 以避免重複的資料列。
- 自動將排序欄位加入 `select` 子句以防止 SQL 錯誤。

## Tag 過濾

`applyTagFilter()` 接受：

- Tag ID（整數）
- Tag 名稱（字串）
- Tag model 實例

它會解析至 `config('wncms.models.tag')` 設定的 model，並套用 `withAnyTags($names, $tagType)`。

要變更預設 tag 類型：

```php
$links = wncms()->link()->getList([
    'tags' => ['promo', 'sale'],
    'tag_type' => 'link_tag',
]);
```

## 關鍵字搜尋

`applyKeywordFilter()` 在 `name` 欄位進行搜尋：

```php
$links = wncms()->link()->getList([
    'keywords' => ['apple', 'store'],
]);
```

## Website 範圍

`applyWebsiteId()` 在以下情況限定至 website：

- `gss('multi_website')` 已啟用，或
- Link model 的 `website_mode` 為 `single`/`multi`，且
- Model 支援 `applyWebsiteScope()`。

```php
$links = wncms()->link()->getList([
    'website_id' => 3,
]);
```

## 排序規則

Manager 以特殊情況覆寫 `applyOrdering()`：

### 隨機排序

```php
$links = wncms()->link()->getList(['order' => 'random']);
```

使用 `inRandomOrder()`。

### 昨日瀏覽數

```php
$links = wncms()->link()->getList([
    'order' => 'total_views_yesterday',
    'sequence' => 'desc',
]);
```

行為：

- 首先加入 `orderBy('links.is_pinned', 'desc')`。
- Left join `total_views as tv_y` 於昨日日期：

  ```sql
  ON links.id = tv_y.link_id AND tv_y.date = YESTERDAY
  ```

- 依 `tv_y.total` 排序，接著 `links.id desc`。
- 確保 `tv_y.total` 若需要會出現在 `select` 中。

### 預設 / 自訂欄位

```php
$links = wncms()->link()->getList([
    'order' => 'order',     // 或 links.* 上的任何欄位
    'sequence' => 'asc',
]);
```

行為：

- 依 `links.{order}` 排序，接著 `links.id desc`。
- 自動選取任何不在 `select` 中的排序欄位。

## 使用範例

依 ID 擷取：

```php
$link = wncms()->link()->get(['id' => 42]);
```

列出最新的 active links 並分頁：

```php
$links = wncms()->link()->getList([
    'status'    => 'active',
    'page_size' => 20,
    'order'     => 'order',
    'sequence'  => 'asc',
]);
```

依 category 過濾並排除某些 tags：

```php
$links = wncms()->link()->getList([
    'tags'            => ['news', 'featured'],
    'tag_type'        => 'link_category',
    'excluded_tag_ids'=> [7, 9],
    'count'           => 8,
]);
```

當前 website 的隨機精選 links：

```php
$links = wncms()->link()->getList([
    'website_id' => wncms()->website()->id(),
    'wheres'     => [['is_featured', true]],
    'order'      => 'random',
    'count'      => 6,
]);
```

昨日瀏覽數前幾名的 links，置頂優先：

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
