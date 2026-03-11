---
name: wncms-model-creation
description: Create host-project models that extend WNCMS BaseModel and follow documented app-level naming, tag, and relationship conventions.
---

## Goal
Generate app models that extend WNCMS cleanly from the host project.

## Read Before Coding
- `documentations/manual/developer/model/base-model.md`
- `documentations/manual/developer/model/create-a-model.md`
- `documentations/manual/developer/model/define-tag-types.md`
- `documentations/manual/developer/model/adding-relationship-to-core-models.md`

## Hard Rules
- Place new models in `app/Models/{Name}.php`.
- Use namespace `App\Models`.
- Extend `Wncms\Models\BaseModel` unless the request explicitly needs another documented base.
- Always define `public static $modelKey = '{snake_case_singular}';`.
- Set `protected $guarded = [];` unless user provides a guarded/fillable requirement.
- Add constants only when needed (`ICONS`, `ROUTES`, `SORTS`, `STATUSES`, `VISIBILITIES`).
- For tag-enabled models, define `protected static array $tagMetas = [...]`.
- Use `wncms()->getModelClass('{key}')` inside relationships for compatibility.
- Prefer the documented quick command `php artisan wncms:create-model {Name}` when backend CRUD scaffolding is desired.

## Optional Traits/Interfaces
- `HasFactory` for factory usage.
- `SoftDeletes` only if table has `deleted_at`.
- Spatie media: `HasMedia` + `InteractsWithMedia` + `registerMediaCollections()`.
- API exposure: use `.github/skills/wncms-api-creation/SKILL.md`.
- Translations: `HasTranslations` + `$translatable`.

## Required Companion Changes
- Add/adjust migration in `database/migrations`.
- If backend CRUD is required, also create backend controller + routes + permissions.
- If using manual scaffolding, register backend routes in `routes/custom_backend.php`.

## Example Skeleton
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Novel extends BaseModel
{
    use HasFactory;

    public static $modelKey = 'novel';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-book',
    ];
}
```

## Do Not Invent
- Do not place host-project models under `src/Models`.
- Do not claim package-internal registration steps unless the task is explicitly about maintaining `wncms-core`.
