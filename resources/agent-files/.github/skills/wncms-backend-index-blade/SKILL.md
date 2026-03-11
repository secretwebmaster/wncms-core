---
name: wncms-backend-index-blade
description: Standardize backend model index blade tables using shared WNCMS table partials for status, booleans, URLs, dates, and images.
---

## Goal
Keep backend index pages consistent, maintainable, and visually unified by reusing shared table partials.

## Scope
Use this skill when:
- creating a new backend `{model}/index.blade.php`
- refactoring existing backend index table cells
- reviewing table rendering consistency

## Required Pattern
- Reuse toolbar/filter/button includes from `backend/common`.
- Reuse table cell partials from `resources/views/common/table_*.blade.php`.
- Avoid ad-hoc badge/link formatting when a shared partial already exists.

## Table Cell Mapping
Prefer these includes in index table body:

- Status:
```blade
@include('wncms::common.table_status', ['model' => $model])
```

- Status with badge style:
```blade
@include('wncms::common.table_status', ['model' => $model, 'badgeStyle' => true])
```

- Boolean (`is_active`, `is_pinned`, etc.):
```blade
@include('wncms::common.table_is_active', ['model' => $model, 'active_column' => 'is_pinned'])
```

- URL:
```blade
@include('wncms::common.table_url', ['url' => $model->url])
```

- Date/time (`created_at`, `updated_at`, `expired_at`):
```blade
@include('wncms::common.table_date', ['model' => $model, 'column' => 'created_at'])
```

- Image:
```blade
@include('wncms::common.table_image', ['model' => $model, 'attribute' => 'thumbnail'])
```

## Notes
- Keep custom badges only for model-specific states that `table_status` cannot express.
- Keep translation via existing `table_*` partials (`wncms::word.*`) instead of hardcoded text.
- Keep table cells simple; push formatting logic into shared partials where possible.

## Checklist
- Status cell uses `table_status`.
- Boolean-like fields use `table_is_active`.
- URL fields use `table_url`.
- Date fields use `table_date` where conditional coloring is desired.
- Index blade has no duplicated badge/link rendering already covered by shared partials.
