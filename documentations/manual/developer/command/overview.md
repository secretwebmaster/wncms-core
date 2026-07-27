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

## `wncms:create-permission`

Create one or more permissions directly, with optional role assignment.

```bash
php artisan wncms:create-permission article_publish
```

Examples:

```bash
# Create a single permission
php artisan wncms:create-permission article_publish

# Create a single permission and assign it to one role
php artisan wncms:create-permission article_publish editor

# Create multiple permissions at once
php artisan wncms:create-permission article_publish,article_archive

# Create multiple permissions and assign them to multiple roles
php artisan wncms:create-permission article_publish,article_archive editor,admin
```

Behavior summary:
- Accepts comma-separated permission names in `{permission_name}`.
- Accepts an optional comma-separated role list in `{role}`.
- Creates missing permissions with `firstOrCreate`.
- Creates missing roles with `firstOrCreate`.
- Assigns every supplied permission to every supplied role.

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

## `wncms:links:list`

List links from CLI.

```bash
php artisan wncms:links:list
```

Common usage:

```bash
# List active links as JSON
php artisan wncms:links:list --json

# Include every status
php artisan wncms:links:list --status=all --json

# Filter by keyword and website scope
php artisan wncms:links:list --keyword=partner --website=1 --per-page=20 --json
```

Behavior summary:
- Uses `LinkAutomationService` and `LinkManager` for read-only list access.
- Defaults to `--status=active`; use `--status=all` to disable status filtering.
- Supports `--keyword=`, `--website=`, `--page=`, `--per-page=`, `--sort=`, and `--direction=`.
- Returns a table for operators by default, or an API v2 aligned envelope with `--json`.
- Does not mutate data or flush cache.

## `wncms:links:inspect`

Inspect one link by ID or slug from CLI.

```bash
php artisan wncms:links:inspect 123
php artisan wncms:links:inspect my-link-slug --json
```

Behavior summary:
- Accepts a numeric ID or a slug in `{identifier}`.
- Supports `--website=` to scope lookup when Link website mode requires it.
- Returns a key-value table by default, or an API v2 aligned envelope with `--json`.
- Returns failure with `code: 404` when the link cannot be found.
- Does not mutate data or flush cache.

## `wncms:links:create`

Create a link through the guarded automation path.

```bash
# Default mode is dry-run and does not write data
php artisan wncms:links:create --name="Partner" --url=https://example.com --website=1 --json

# Write mode requires --force and an actor user allowed to create links
php artisan wncms:links:create --name="Partner" --url=https://example.com --website=1 --actor-user=1 --force --json
```

Behavior summary:
- Uses `LinkAutomationService` and returns the same automation result envelope as the read-only Link commands.
- Defaults to dry-run unless `--force` is supplied; `--dry-run` always prevents writes.
- Write mode requires an actor from `--actor-user=` or `wncms.automation.system_actor_user_id`.
- The actor must pass `link_create` permission and requested website scope checks.
- Successful writes create the Link, bind requested websites when Link uses scoped website mode, sync requested link categories/tags, flush `links` cache, dispatch existing Link store hooks, and store a `mutation_audits` record.
- Supports `--name=`, `--url=`, `--status=`, `--slug=`, `--tracking-code=`, `--website=`, `--description=`, `--slogan=`, `--external-thumbnail=`, `--remark=`, `--sort=`, `--color=`, `--background=`, `--is-pinned`, `--is-recommended`, `--expired-at=`, `--hit-at=`, `--clicks=`, `--contact=`, `--link-categories=`, and `--link-tags=`.

## `wncms:links:update`

Update selected Link fields through the guarded automation path.

```bash
# Default mode previews the patch and does not write data
php artisan wncms:links:update partner-link --name="Partner Plus" --json

# Write mode requires an actor with link_edit permission
php artisan wncms:links:update partner-link --name="Partner Plus" --is-pinned=false --actor-user=1 --force --json
```

Behavior summary:
- Only supplied patch fields are changed; omitted fields are preserved.
- Defaults to dry-run unless `--force` is supplied; `--dry-run` always prevents writes.
- Write mode requires an actor from `--actor-user=` or `wncms.automation.system_actor_user_id`, with `link_edit` permission.
- `--website=` restricts target lookup. The guard always checks the target Link's existing website IDs, so omitting the option cannot bypass cross-site protection.
- Unknown website IDs return `422`; missing scoped targets return `404`; missing actors return `401`; permission or website-scope failures return `403`.
- A no-op patch returns a successful `200` result without cache flush or audit write. Successful writes dispatch existing Link update hooks, flush `links` cache after the transaction, and write a `mutation_audits` record.
- Supports `--status=`, `--tracking-code=`, `--slug=`, `--name=`, `--url=`, `--slogan=`, `--description=`, `--external-thumbnail=`, `--remark=`, `--sort=`, `--color=`, `--background=`, `--is-pinned=true|false`, `--is-recommended=true|false`, `--expired-at=`, `--hit-at=`, `--clicks=`, `--contact=`, `--website=`, `--actor-user=`, `--dry-run`, `--force`, and `--json`.
- Explicit empty values clear nullable patch fields; explicit empty `status`, `slug`, `name`, or `url` returns `422`. Boolean fields accept only `true`, `false`, `1`, `0`, `yes`, `no`, `on`, or `off`.
- Dry-run does not execute `wncms.backend.links.update.attributes.before`, because hooks may have side effects. The dry-run changes are pre-hook; the successful write response and audit attributes reflect hook-mutated values and changes.

## `wncms:links:delete`

Delete one Link through the guarded automation path.

```bash
# Default: preview only
php artisan wncms:links:delete partner-link --json

# Write mode requires an actor with link_delete
php artisan wncms:links:delete partner-link --actor-user=1 --force --json
```

Behavior summary:

- Defaults to dry-run; `--force` is required to delete and `--dry-run` always prevents writes.
- Requires an actor from `--actor-user=` or the configured system actor, with `link_delete` permission.
- `--website=` limits target lookup; the guard always validates the target's existing website IDs, including when the option is omitted.
- Unknown website IDs return `422`, missing actors return `401`, permission or website scope failures return `403`, and missing/scoped-out targets return `404`.
- A successful delete runs in a transaction, returns the deleted target and audit ID, stores a target snapshot in `mutation_audits`, then flushes `links` cache. Link has no delete hooks, so none are dispatched.
- Supports `{identifier}`, `--website=`, `--actor-user=`, `--dry-run`, `--force`, and `--json`.

## `wncms:links:bulk-update`

Atomically patch the `url` and/or `sort` fields of up to 100 Links.

```bash
# Default mode validates and previews every item without writing
php artisan wncms:links:bulk-update --items='[{"identifier":"partner-link","url":"https://example.com/partner"},{"identifier":42,"sort":10}]' --json

# Write mode requires an allowed actor
php artisan wncms:links:bulk-update --items='[{"identifier":42,"sort":10}]' --website=1 --actor-user=1 --force --json
```

`--items=` must be a JSON array with 1-100 items. Every item requires `identifier` (Link ID or slug), may contain only `url` and `sort`, must include at least one patch field, and a supplied `url` must not be empty.

Behavior summary:

- The command is atomic: malformed input, duplicate resolved targets, missing or website-scoped-out targets, invalid patches, and guard failures prevent every write.
- It is dry-run by default. `--force` enables guarded writes; `--dry-run` always wins and returns `202` without writing.
- Write mode requires an actor from `--actor-user=` or the configured system actor with `link_edit`. `--website=` scopes every lookup, and each target's existing website IDs are always permission-checked.
- Success returns `200`; malformed input returns `422`; missing actors return `401`; permission/site denial returns `403`; missing or scoped-out targets return `404`; cancelled or stale batches return `409`.
- Changed Links receive one `mutation_audits` row each with a shared run ID. No-op items receive no audit row, and the `links` cache flushes once only after a committed batch with changes. No bulk-update hooks are dispatched.

## `wncms:install-default-theme`

Install or reinstall core default theme assets into `public/themes`.

```bash
php artisan wncms:install-default-theme --force
```

Behavior summary:
- Publishes assets from the `wncms-default-assets` publish tag.
- Intended for recovery when default theme assets are edited, missing, or corrupted.
- This command is also used by installer flows (CLI and browser wizard) via shared installer logic.
- If asset copy fails due to filesystem permissions, backend tools return a translatable guidance message asking users to run `Fix Permission` first.

## `wncms:install-agent-files`

Install WNCMS agent files into the host project root.

```bash
php artisan wncms:install-agent-files
```

Common usage:

```bash
# Overwrite all existing target files without prompt
php artisan wncms:install-agent-files --force

# Preview only, do not write files
php artisan wncms:install-agent-files --dry-run
```

Behavior summary:
- Publishes from package source `resources/agent-files`.
- Installs `AGENTS.md` and `.github/skills` into host project root.
- Default mode is interactive for existing targets:
  - asks whether to overwrite `AGENTS.md`
  - asks whether to overwrite `.github/skills`
- `--force` overwrites existing targets without confirmation.
- `--dry-run` prints planned actions and does not modify files.

## `wncms:update-website`

Update one website column from CLI.

```bash
php artisan wncms:update-website {key} {value}
```

Common usage:

```bash
# Switch website theme
php artisan wncms:update-website theme default

# Update site name
php artisan wncms:update-website site_name "My Website"
```

Behavior summary:
- Updates the current website in CLI context; if not resolvable by domain, falls back to the first website record.
- Validates that `{key}` exists as a real column on `websites` table.
- When updating `theme`, auto-seeds missing default theme options for the new theme.
- Flushes `websites` cache tag after update.

## `wncms:update`

Run core update scripts.

```bash
# Normal update flow (remote version list + incremental apply)
php artisan wncms:update core

# Rerun one specific local update file
php artisan wncms:update --rerun-version=6.1.6
php artisan wncms:update --rerun-version=v6.1.6
```

Behavior summary:
- `--rerun-version=` runs exactly one local update script again:
  - `updates/update_core_{version}.php`
- `v` prefix is accepted (for example `v6.1.6` and `6.1.6` behave the same).
- If `--rerun-version` is missing or not found in `updates/`, command returns failure.

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

CLI locale behavior:
- `--app_locale=` controls installer terminal message language.
- Example: `--app_locale=zh_CN` displays install progress messages in Simplified Chinese.
- If locale is unsupported or empty, installer falls back to configured app locale/default supported locale.

CLI agent files behavior:
- `--agent` (or `--agent=1`) publishes the `wncms-agent-files` tag during install.
- Equivalent publish command: `php artisan vendor:publish --tag=wncms-agent-files`.

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
