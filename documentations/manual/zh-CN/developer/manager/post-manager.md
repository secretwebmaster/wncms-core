# Post Manager

**PostManager** 处理 `Post` model 的所有资料操作，包括过滤、关联、基于 tag 的查询、快取与相关贴文撷取。它继承 WNCMS 核心的 `ModelManager` 来提供统一、高效能的查询层。

## Class 概述

```php
namespace Wncms\Services\Managers;

class PostManager extends ModelManager
```

**用途：**
为 backend、frontend 与 API controllers 管理所有 `Post` model 的资料查询，并具有一致的行为。

## 主要属性

| 属性              | 类型         | 说明                             |
| ----------------- | ------------ | -------------------------------- |
| `$cacheKeyPrefix` | string       | 用于快取键生成（`wncms_post`）   |
| `$defaultTagType` | string       | 预设 tag 类型（`post_category`） |
| `$shouldAuth`     | bool         | 是否包含基于使用者的快取范围     |
| `$cacheTags`      | string/array | 用于失效的快取标签（`posts`）    |

## getModelClass()

```php
public function getModelClass(): string
{
    return wncms()->getModelClass('post');
}
```

回传此 Manager 处理的 model class 名称。
WNCMS 会自动解析 `config/wncms.php` 中定义的自订覆写。

## get()

```php
public function get(array $options = []): ?Model
```

依 `id`、`slug` 或 `name` 撷取单一 post。

**行为：**

- 自动 eager-load：`media`、`comments`、`tags` 与 `translations`。
- 接受标准的 `ModelManager::get()` 选项（`id`、`slug`、`wheres`、`cache` 等）。

**范例：**

```php
$post = wncms()->post()->get(['slug' => 'my-first-post']);
```

## getList()

```php
public function getList(array $options = []): Collection|LengthAwarePaginator
```

撷取 collection 或分页的 posts 清单。
它合并与 `get()` 相同的预设关联。

**范例：**

```php
$posts = wncms()->post()->getList([
    'status' => 'published',
    'order' => 'created_at',
    'sequence' => 'desc',
    'page_size' => 10,
]);
```

**预设 eager-loaded relations：**
`media`、`comments`、`tags`、`translations`

## getRelated()

```php
public function getRelated(array|Model|int|null $post, array $options = []): Collection|LengthAwarePaginator
```

撷取分享相同 tag 类型（预设：`post_category`）的相关 posts。

**参数：**

| 键                     | 类型               | 说明                                       |
| ---------------------- | ------------------ | ------------------------------------------ |
| `$post`                | Model / ID / Array | 参考 post                                  |
| `$options['tag_type']` | string             | 要匹配的 Tag 类型（预设：`post_category`） |
| `$options['cache']`    | bool               | 启用或停用快取                             |

**行为：**

- 自动排除目前的 post ID。
- 匹配来自 `$post->tagsWithType($tagType)` 的 tag 名称。

**范例：**

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

定义 `PostManager` 如何建构用于列出 posts 的查询。
这是 `getList()` 流程的核心。

### 支援的选项

| 选项                | 类型         | 说明                                   |
| ------------------- | ------------ | -------------------------------------- |
| `tags`              | array/string | 依 tag 名称或 ID 过滤 posts            |
| `tag_type`          | string       | Tag 类型（预设：`post_category`）      |
| `keywords`          | array/string | 在 title、label、excerpt、content 搜寻 |
| `count`             | int          | 限制 posts 数量                        |
| `offset`            | int          | 在限制前跳过 posts                     |
| `order`             | string       | 排序栏位（预设：`id`）                 |
| `sequence`          | string       | asc / desc                             |
| `status`            | string       | Post 状态（预设：`published`）         |
| `wheres`            | array        | 额外的 where 条件                      |
| `website_id`        | int          | 依 website 限定范围                    |
| `excluded_post_ids` | array        | 排除 post ID                           |
| `excluded_tag_ids`  | array        | 排除 tag ID                            |
| `ids`               | array        | 依 ID 过滤                             |
| `select`            | array        | 选择特定栏位                           |
| `withs`             | array        | 额外的 relations                       |
| `is_random`         | bool         | 随机化顺序                             |

## Query 行为

**Tag Filter：**
使用 `applyTagFilter()` 来包含具有指定 tags 的 posts。

**Keyword Search：**
跨多个栏位搜寻：

- `title`、`label`、`excerpt`、`content`

**Website Scoping：**
为 multi-site 模式套用 `applyWebsiteId()`。

**Exclusion：**
跳过在 `excluded_post_ids` 或 `excluded_tag_ids` 中定义的 posts 或 tags。

**Ordering：**
预设为 `orderBy('id', 'desc')`，或当 `is_random = true` 时随机排序。

**Status Filter：**
预设仅包含 `status = 'published'` 的 posts。

**Comment Count：**
自动为每个结果加入 `withCount('comments')`。

## 使用范例

**1. 依 slug 撷取已发布的 post**

```php
$post = wncms()->post()->get(['slug' => 'hello-world']);
```

**2. 列出某个 category 中最新的 5 篇 posts**

```php
$posts = wncms()->post()->getList([
    'tags' => 'news',
    'tag_type' => 'post_category',
    'count' => 5,
]);
```

**3. 取得相关 posts**

```php
$related = wncms()->post()->getRelated($post, [
    'count' => 4,
    'is_random' => true,
]);
```

**4. 分页 posts**

```php
$posts = wncms()->post()->getList([
    'page_size' => 10,
    'page' => request('page', 1),
]);
```

## 总结

`PostManager` 扩充核心 `ModelManager` 来提供：

- 自动关联载入（`media`、`tags`、`translations` 等）
- Multi-site 与 tag-type 过滤
- 快取查询以提升效能
- 相关 post 侦测
- 为 backend、frontend 与 API 使用提供统一介面
