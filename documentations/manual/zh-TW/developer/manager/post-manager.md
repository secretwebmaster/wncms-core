# Post Manager

**PostManager** 處理 `Post` model 的所有資料操作，包括過濾、關聯、基於 tag 的查詢、快取與相關貼文擷取。它繼承 WNCMS 核心的 `ModelManager` 來提供統一、高效能的查詢層。

## Class 概述

```php
namespace Wncms\Services\Managers;

class PostManager extends ModelManager
```

**用途：**
為 backend、frontend 與 API controllers 管理所有 `Post` model 的資料查詢，並具有一致的行為。

## 主要屬性

| 屬性              | 類型         | 說明                             |
| ----------------- | ------------ | -------------------------------- |
| `$cacheKeyPrefix` | string       | 用於快取鍵生成（`wncms_post`）   |
| `$defaultTagType` | string       | 預設 tag 類型（`post_category`） |
| `$shouldAuth`     | bool         | 是否包含基於使用者的快取範圍     |
| `$cacheTags`      | string/array | 用於失效的快取標籤（`posts`）    |

## getModelClass()

```php
public function getModelClass(): string
{
    return wncms()->getModelClass('post');
}
```

回傳此 Manager 處理的 model class 名稱。
WNCMS 會自動解析 `config/wncms.php` 中定義的自訂覆寫。

## get()

```php
public function get(array $options = []): ?Model
```

依 `id`、`slug` 或 `name` 擷取單一 post。

**行為：**

- 自動 eager-load：`media`、`comments`、`tags` 與 `translations`。
- 接受標準的 `ModelManager::get()` 選項（`id`、`slug`、`wheres`、`cache` 等）。

**範例：**

```php
$post = wncms()->post()->get(['slug' => 'my-first-post']);
```

## getList()

```php
public function getList(array $options = []): Collection|LengthAwarePaginator
```

擷取 collection 或分頁的 posts 清單。
它合併與 `get()` 相同的預設關聯。

**範例：**

```php
$posts = wncms()->post()->getList([
    'status' => 'published',
    'order' => 'created_at',
    'sequence' => 'desc',
    'page_size' => 10,
]);
```

**預設 eager-loaded relations：**
`media`、`comments`、`tags`、`translations`

## getRelated()

```php
public function getRelated(array|Model|int|null $post, array $options = []): Collection|LengthAwarePaginator
```

擷取分享相同 tag 類型（預設：`post_category`）的相關 posts。

**參數：**

| 鍵                     | 類型               | 說明                                       |
| ---------------------- | ------------------ | ------------------------------------------ |
| `$post`                | Model / ID / Array | 參考 post                                  |
| `$options['tag_type']` | string             | 要匹配的 Tag 類型（預設：`post_category`） |
| `$options['cache']`    | bool               | 啟用或停用快取                             |

**行為：**

- 自動排除目前的 post ID。
- 匹配來自 `$post->tagsWithType($tagType)` 的 tag 名稱。

**範例：**

```php
$related = wncms()->post()->getRelated($post, [
    'count' => 6,
    'is_random' => true,
]);
```

## buildListQuery()

```php
protected function buildListQuery(array $options): mixed
```

定義 `PostManager` 如何建構用於列出 posts 的查詢。
這是 `getList()` 流程的核心。

### 支援的選項

| 選項                | 類型         | 說明                                   |
| ------------------- | ------------ | -------------------------------------- |
| `tags`              | array/string | 依 tag 名稱或 ID 過濾 posts            |
| `tag_type`          | string       | Tag 類型（預設：`post_category`）      |
| `keywords`          | array/string | 在 title、label、excerpt、content 搜尋 |
| `count`             | int          | 限制 posts 數量                        |
| `offset`            | int          | 在限制前跳過 posts                     |
| `order`             | string       | 排序欄位（預設：`id`）                 |
| `sequence`          | string       | asc / desc                             |
| `status`            | string       | Post 狀態（預設：`published`）         |
| `wheres`            | array        | 額外的 where 條件                      |
| `website_id`        | int          | 依 website 限定範圍                    |
| `excluded_post_ids` | array        | 排除 post ID                           |
| `excluded_tag_ids`  | array        | 排除 tag ID                            |
| `ids`               | array        | 依 ID 過濾                             |
| `select`            | array        | 選擇特定欄位                           |
| `withs`             | array        | 額外的 relations                       |
| `is_random`         | bool         | 隨機化順序                             |

## Query 行為

**Tag Filter：**
使用 `applyTagFilter()` 來包含具有指定 tags 的 posts。

**Keyword Search：**
跨多個欄位搜尋：

- `title`、`label`、`excerpt`、`content`

**Website Scoping：**
為 multi-site 模式套用 `applyWebsiteId()`。

**Exclusion：**
跳過在 `excluded_post_ids` 或 `excluded_tag_ids` 中定義的 posts 或 tags。

**Ordering：**
預設為 `orderBy('id', 'desc')`，或當 `is_random = true` 時隨機排序。

**Status Filter：**
預設僅包含 `status = 'published'` 的 posts。

**Comment Count：**
自動為每個結果加入 `withCount('comments')`。

## 使用範例

**1. 依 slug 擷取已發佈的 post**

```php
$post = wncms()->post()->get(['slug' => 'hello-world']);
```

**2. 列出某個 category 中最新的 5 篇 posts**

```php
$posts = wncms()->post()->getList([
    'tags' => 'news',
    'tag_type' => 'post_category',
    'count' => 5,
]);
```

**3. 取得相關 posts**

```php
$related = wncms()->post()->getRelated($post, [
    'count' => 4,
    'is_random' => true,
]);
```

**4. 分頁 posts**

```php
$posts = wncms()->post()->getList([
    'page_size' => 10,
    'page' => request('page', 1),
]);
```

## 總結

`PostManager` 擴充核心 `ModelManager` 來提供：

- 自動關聯載入（`media`、`tags`、`translations` 等）
- Multi-site 與 tag-type 過濾
- 快取查詢以提升效能
- 相關 post 偵測
- 為 backend、frontend 與 API 使用提供統一介面
