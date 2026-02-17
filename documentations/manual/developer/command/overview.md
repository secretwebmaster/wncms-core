# Developer Commands Overview

This page documents core WNCMS developer scaffolding commands.

## `wncms:create-model`

Create a model scaffold in the host project (model, migration, backend controller, starter views, and permissions).

```bash
php artisan wncms:create-model Novel
```

Behavior summary:
- Generates `app/Models/Novel.php` when missing.
- Generated model extends `Wncms\Models\BaseModel` and includes a `modelKey` fallback (auto-derived from class name when left empty).
- Generates a migration for `novels` table.
- Generates `app/Http/Controllers/Backend/NovelController.php`.
- Generated backend controller methods follow `BackendController` compatible signatures (`create($id)`, `edit($id)`, `update(Request, $id)`, `destroy($id)`).
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

## `wncms:activate-plugin`

Activate a plugin from CLI the same way as backend activation (`status` => `active`).

```bash
php artisan wncms:activate-plugin wncms-users-hook-test
```

Behavior summary:
- Accepts plugin `name`, `plugin_id`, or folder `path`.
- Scans `public/plugins` and auto-syncs missing directory plugins into `plugins` table.
- Runs plugin lifecycle `activate()` when standardized plugin class exists.
- Activates the matched plugin by setting `status` to `active`.
- Returns failure when `plugins` table is missing or no plugin can be matched.

## `wncms:verify-plugin-hooks`

Run release-gate checks for plugin manifests and users hook hard-cut migration.

```bash
php artisan wncms:verify-plugin-hooks
```

Behavior summary:
- Verifies plugin root directory (`public/plugins`) exists.
- Verifies every plugin directory has valid `plugin.json` (`id`, `name`, `version`).
- Verifies legacy users hook names are removed from core user controllers.
- Verifies `plugins` table exists and has no `[MANIFEST_ERROR]` / `[LOAD_ERROR]` records.
- Returns failure when any gate fails (release should be blocked).

## `wncms:hook-list`

Inspect the hook/extension registry for plugin development.

```bash
php artisan wncms:hook-list
```

Common usage:

```bash
# Include listener details for each hook
php artisan wncms:hook-list --listeners

# Show only hooks that currently have listeners
php artisan wncms:hook-list --only-listened

# Export as JSON for automation
php artisan wncms:hook-list --json
```

Behavior summary:
- Scans WNCMS core `src` (and host app `app`) for dispatched hook names (`Event::dispatch(...)` / `event(...)`).
- Lists each hook with dispatch-point count and current runtime listener count.
- Optional `--listeners` mode prints listener identities per hook.
- Includes extension registry data from `macroable-models` (registered query macros by model).

Expected output format (abridged):

```text
WNCMS Hook / Extension Registry
Hooks: 40, Macros: 2

+---------------------------------------------+-----------------+-----------+
| Hook                                        | Dispatch Points | Listeners |
+---------------------------------------------+-----------------+-----------+
| wncms.frontend.users.login.before           | 1               | 0         |
| wncms.frontend.users.register.after         | 1               | 1         |
+---------------------------------------------+-----------------+-----------+

Registered Macros (Extension Registry)
+----------------+------------------------+-------------+
| Macro          | Models                 | Model Count |
+----------------+------------------------+-------------+
| wherePublished | Wncms\Models\Post      | 1           |
+----------------+------------------------+-------------+
```

## `wncms:install-default-theme`

Install or reinstall core default theme assets into `public/themes`.

```bash
php artisan wncms:install-default-theme --force
```

Behavior summary:
- Publishes assets from the `wncms-default-assets` publish tag.
- Intended for recovery when default theme assets are edited, missing, or corrupted.
- This command is also used by installer flows (CLI and browser wizard) via shared installer logic.

## `wncms:update`

Run core update scripts.

```bash
# Normal update flow (remote version list + incremental apply)
php artisan wncms:update core

# Rerun one specific local update file
php artisan wncms:update --version=6.1.6
php artisan wncms:update --version=v6.1.6
```

Behavior summary:
- `--version=` runs exactly one local update script again:
  - `updates/update_core_{version}.php`
- `v` prefix is accepted (for example `v6.1.6` and `6.1.6` behave the same).
- If `--version` is missing or not found in `updates/`, command returns failure.

## Installation Modes (`wncms:install` + Browser Wizard)

WNCMS supports two installation entry points:

1. CLI command: `php artisan wncms:install ...`
2. Browser wizard: `/install/wizard`

Both modes now use the same shared installer pipeline in `InstallerManager`, so behavior is aligned across:
- DB connection check
- `.env` write
- app key generation
- database setup
- asset publishing (`wncms-core-assets`, `wncms-stubs`, `wncms-default-assets`)
- custom language/route file bootstrap
- system settings initialization
- install marker + cache finalize

### Multi-site default behavior

- `multi_website` default is `false`.
- CLI: multi-site is enabled only when passing `--multi_website`.
- Wizard: multi-site is enabled only when the checkbox is checked.

After installation you can verify:

```bash
php artisan tinker
```

```php
gss('multi_website');
```

## Troubleshooting

- `Source view file not found`:
  Check that starter blades exist under package `resources/views/backend/starters`.
- Command created no views:
  Confirm target files do not already exist in `resources/views/backend/{plural}/`.
- Route permission denied:
  Re-run `wncms:create-model-permission {model}` and verify role assignment in backend.
- Link backend route permission denied on upgraded projects:
  Update to core `6.1.9+` and run `php artisan wncms:update core` so Link permissions are backfilled during update.
