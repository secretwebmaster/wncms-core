---
name: wncms-manager-creation
description: Create host-project managers under app Services that extend WNCMS ModelManager and override core manager resolution safely.
---

## Goal
Generate app managers that are discoverable through `wncms()->{modelKey}()` and can override core managers safely.

## Read Before Coding
- `documentations/manual/developer/manager/base-manager.md`
- `documentations/manual/developer/manager/create-a-manager.md`
- Use concrete references when relevant:
  - `documentations/manual/developer/manager/link-manager.md`
  - `documentations/manual/developer/manager/post-manager.md`

## Hard Rules
- Place class at `app/Services/Managers/{Name}Manager.php`.
- Namespace must be `App\Services\Managers`.
- Extend `Wncms\Services\Managers\ModelManager`.
- Implement `getModelClass(): string` returning `wncms()->getModelClass('{model_key}')`.
- Implement `buildListQuery(array $options): mixed`.
- Set `$cacheKeyPrefix` and `$cacheTags` to model-specific values.

## Recommended Pattern
- Override `get()` and/or `getList()` only when injecting default eager loads.
- In `buildListQuery`, standardize common options:
  - `tags`, `tag_type`, `keywords`, `search_fields`
  - `count`, `offset`, `sort`, `direction`, `status`
  - `wheres`, `website_id`, `ids`, `excluded_ids`, `withs`, `select`
- Use inherited helpers:
  - `applyWebsiteId`, `applyUserId`
  - `applyWiths`, `applyTagFilter`, `applyKeywordFilter`
  - `applyWhereConditions`, `applyIds`, `applyExcludeIds`
  - `applySelect`, `applyOffset`, `applyLimit`
  - `applyStatus`, `applyOrdering`

## Example Skeleton
```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\ModelManager;

class NovelManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_novel';
    protected string|array $cacheTags = ['novels'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('novel');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyOrdering($q, $options['sort'] ?? 'id', $options['direction'] ?? 'desc', $options['is_random'] ?? false);
        return $q;
    }
}
```

## Resolution Note
- App managers have higher priority than core managers when WNCMS resolves `wncms()->{modelKey}()`.

## Do Not Invent
- Do not put host-project managers under `src/Services/Managers`.
- Do not assume core-only manager conventions that are not documented in the app-level manager docs.
