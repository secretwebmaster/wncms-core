---
name: wncms-feature-scaffold
description: End-to-end host-project scaffold workflow for a new WNCMS-backed feature using app-level files and documented custom route/view conventions.
---

## Goal
When asked to create a new entity in a host project, produce a complete local feature set instead of only one file.

## Read Before Coding
- `documentations/manual/developer/model/create-a-model.md`
- `documentations/manual/developer/controller/create-a-controller.md`
- `documentations/manual/developer/manager/create-a-manager.md`
- `documentations/manual/developer/route/backend.md`
- `documentations/manual/developer/route/add-routes.md`

## Execution Checklist
1. Prefer `php artisan wncms:create-model {Name}` when the task wants backend CRUD scaffolding.
2. Create model at `app/Models/{Name}.php` using `BaseModel` and `$modelKey`.
3. Create migration for `{plural_table}` in `database/migrations`.
4. Create backend controller at `app/Http/Controllers/Backend/{Name}Controller.php` extending `BackendController`.
5. Create manager at `app/Services/Managers/{Name}Manager.php` when custom query logic is needed.
6. Add backend routes in `routes/custom_backend.php` with `can:{singular}_{action}` permissions.
7. Ensure permissions exist:
   - `{singular}_index`
   - `{singular}_create`
   - `{singular}_clone`
   - `{singular}_edit`
   - `{singular}_delete`
   - `{singular}_bulk_delete`
8. Create backend blade views under `resources/views/backend/{plural}/`:
   - `index.blade.php`
   - `create.blade.php`
   - `edit.blade.php`
9. If needed, add translations in `lang/*/custom.php` or existing project translation files.
10. If needed, add tests under the host project `tests/`.

## Naming Rules
- Model key: singular snake_case (`novel`).
- Table name: plural snake_case (`novels`).
- Route names: plural snake_case (`novels.index`).
- Permission names: singular snake_case + suffix (`novel_index`).
- Cache tags: plural snake_case (`novels`).

## Definition Of Done
- Feature compiles with correct namespaces/imports.
- Local model is discoverable through `wncms()->getModelClass('novel')`.
- Backend pages are routable through `routes/custom_backend.php` and authorized by permission middleware.
- CRUD mutations clear cache via controller `flush()` when using `BackendController` patterns.

## Do Not Invent
- Do not scaffold host-project features into `src/` as if the task were modifying `wncms-core`.
- Do not depend on empty `documentations/manual/package/*` pages for host-project behavior.
