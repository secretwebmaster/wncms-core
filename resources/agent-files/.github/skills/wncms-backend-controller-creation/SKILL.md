---
name: wncms-backend-controller-creation
description: Create host-project backend controllers that extend WNCMS BackendController and follow documented app route, view, and cache patterns.
---

## Goal
Generate backend CRUD controllers inside the host project.

## Read Before Coding
- `documentations/manual/developer/controller/base-controller.md`
- `documentations/manual/developer/controller/backend-controller.md`
- `documentations/manual/developer/controller/create-a-controller.md`
- `documentations/manual/developer/route/backend.md`
- `documentations/manual/developer/route/add-routes.md`

## Hard Rules
- Place class at `app/Http/Controllers/Backend/{Name}Controller.php`.
- Namespace must be `App\Http\Controllers\Backend`.
- Extend `Wncms\Http\Controllers\Backend\BackendController`.
- Use `$this->modelClass` for model queries to preserve override flexibility.
- Use `$this->view('backend.{plural}.x', [...])` for backend rendering.
- After create/update/delete mutations, call `$this->flush();` (or flush explicit tags).
- Route keys and view folder names must be plural snake_case.
- For not-found cases, return localized `model_not_found` messages.

## CRUD Baseline
- `index(Request $request)` with sorting/filtering + pagination.
- `create($id = null)` for create/clone flows.
- `store(Request $request)` with validation + create + redirect to edit.
- `edit($id)` load model and render edit page.
- `update(Request $request, $id)` validate + update + redirect.
- Use inherited `destroy`/`bulk_delete` unless custom behavior is required.

## Route Convention Reference
Define routes in `routes/custom_backend.php`:
- `GET /{plural}` -> `{plural}.index` with `can:{singular}_index`
- `GET /{plural}/create` -> `{plural}.create` with `can:{singular}_create`
- `GET /{plural}/create/{id}` -> `{plural}.clone` with `can:{singular}_clone`
- `GET /{plural}/{id}/edit` -> `{plural}.edit` with `can:{singular}_edit`
- `POST /{plural}/store` -> `{plural}.store` with `can:{singular}_create`
- `PATCH /{plural}/{id}` -> `{plural}.update` with `can:{singular}_edit`
- `DELETE /{plural}/{id}` -> `{plural}.destroy` with `can:{singular}_delete`
- `POST /{plural}/bulk_delete` -> `{plural}.bulk_delete` with `can:{singular}_bulk_delete`

## Example
For `ArticleController`, use `backend.articles.*` views and route names `articles.*`.

## Do Not Invent
- Do not place host-project controllers under `src/Http/Controllers`.
- Do not register host-project CRUD routes in the package `routes/backend.php`.
