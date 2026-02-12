# Developer Commands Overview

This page documents core WNCMS developer scaffolding commands.

## `wncms:create-model`

Create a model scaffold in the host project (model, migration, backend controller, starter views, and permissions).

```bash
php artisan wncms:create-model Novel
```

Behavior summary:
- Generates `app/Models/Novel.php` when missing.
- Generates a migration for `novels` table.
- Generates `app/Http/Controllers/Backend/NovelController.php`.
- Runs `wncms:create-model-view novel`.
- Runs `wncms:create-model-permission novel`.
- Optionally appends routes into `routes/custom_backend.php`.

## `wncms:create-model-view`

Create backend blade files for a model from starter templates.

```bash
php artisan wncms:create-model-view novel
```

Generated files:
- `resources/views/backend/novels/index.blade.php`
- `resources/views/backend/novels/create.blade.php`
- `resources/views/backend/novels/edit.blade.php`
- `resources/views/backend/novels/form-items.blade.php`

Starter template resolution order:
1. `resources/views/backend/starters` from package root
2. `../resources/views/backend/starters` from package root
3. internal fallback: `src/../../resources/views/backend/starters`

If no valid starter path is found, the command exits with failure and prints every checked path.

## `wncms:create-model-permission`

Create common backend permissions for a model key.

```bash
php artisan wncms:create-model-permission novel
```

Typical permission suffixes include:
- `_index`
- `_create`
- `_clone`
- `_edit`
- `_delete`
- `_bulk_delete`

## Troubleshooting

- `Source view file not found`:
  Check that starter blades exist under package `resources/views/backend/starters`.
- Command created no views:
  Confirm target files do not already exist in `resources/views/backend/{plural}/`.
- Route permission denied:
  Re-run `wncms:create-model-permission {model}` and verify role assignment in backend.
