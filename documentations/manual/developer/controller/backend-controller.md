# Backend Controller

`Wncms\Http\Controllers\Backend\BackendController` is the base for **admin CRUD** controllers. It standardizes model resolution, naming, cache-tag handling, and common actions (`index/create/store/edit/update/destroy/bulk_delete`). Extend it for each backend resource.

## Key responsibilities

- Resolve the **model class** from controller name (`PostController` → model key `post` → `wncms()->getModelClass('post')`).
- Derive **table / singular / plural** names used for views and labels.
- Provide **cache tag** helpers and a `flush()` method.
- Offer opinionated **CRUD** methods with sensible defaults and JSON responses for AJAX.

## Properties and defaults

| Property      | Type     | Source                | Default behavior                                                                                        |
| ------------- | -------- | --------------------- | ------------------------------------------------------------------------------------------------------- |
| `$modelClass` | `string` | `getModelClass()`     | From controller basename without `Controller`, snake-cased, resolved via `wncms()->getModelClass(...)`. |
| `$cacheTags`  | `array`  | `getModelCacheTags()` | Defaults to `[$this->getModelTable()]`.                                                                 |
| `$singular`   | `string` | `getModelSingular()`  | `str()->singular($this->getModelTable())`.                                                              |
| `$plural`     | `string` | `getModelPlural()`    | `str()->plural($this->getModelSingular())`.                                                             |

> Override any of these in your child controller as protected properties when needed.

## Overridable helpers

```php
// Resolve model class from controller name; override if custom mapping is needed.
public function getModelClass(): string

// Get underlying Eloquent table name.
protected function getModelTable()

// Provide custom cache tags for this resource.
protected function getModelCacheTags(): array

// Customize resource nouns.
protected function getModelSingular(): string
protected function getModelPlural(): string

// Apply current-website list scoping for single/multi website modes.
protected function applyBackendListWebsiteScope(Builder $q): void
```

## Cache control

```php
public function flush(string|array|null $tags = null): bool
```

- Flushes tagged caches via `wncms()->cache()->tags($tag)->flush()`.
- If `$tags` is `null`, uses `$this->cacheTags`.

## Multisite list filtering helper

`applyBackendListWebsiteScope()` standardizes backend index filtering for models whose website mode is `single` or `multi`.

- Reads the current website ID from `wncms()->website()->get()?->id`.
- Applies model `applyWebsiteScope(...)` only when multisite behavior is supported.
- No-op for `global` models or when no current website is resolved.
- For index toolbar filters, standardize request key to `website_id` and keep `website` as backward-compatible alias when reading request input.

## Built-in CRUD actions

All actions assume standard backend Blade paths: `backend.{plural}.*`.

- `index(Request $request)`

  - Builds a base query on `$modelClass`, orders by `id desc`, returns `backend.{plural}.index`.
  - Passes `page_title`, `models`.

- `create(int|string|null $id = null)`

  - New instance or loads existing for “duplicate/edit-as-new” patterns.
  - Returns `backend.{plural}.create` with `model`.

- `store(Request $request)`

  - `create($request->all())`, then:

    - If AJAX: JSON `{ status, message, redirect }`.
    - Else: redirect to `route('{plural}.edit', ['id' => $model->id])`.

- `edit(int|string $id)`

  - Loads model, returns `backend.{plural}.edit` with `model`.

- `update(Request $request, $id)`

  - `findOrFail`-like behavior (returns message if missing), `update($request->all())`.
  - If AJAX: JSON `{ status, message, redirect }`.
  - Else: redirect back to edit.

- `destroy($id)`

  - Deletes model, calls `$this->flush()`, redirects to index with success message.

- `bulk_delete(Request $request)`

  - Accepts `model_ids` as CSV or array, deletes in batch.
  - If AJAX: JSON with deleted count; else `back()` with message.

> Messages follow WNCMS translations (e.g., `__('wncms::word.successfully_updated')`). Titles use `__('wncms::word.' . $this->singular)`.

## Minimal subclass example

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class ProductController extends BackendController
{
    // Optional: override naming or cache tags
    protected array $cacheTags = ['products', 'prices'];

    // Optional: customize model mapping if the default name-based resolver isn’t desired
    public function getModelClass(): string
    {
        return wncms()->getModelClass('product'); // explicit
    }

    // Optional: extend index filters/sorting/pagination
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        if ($kw = $request->keyword) {
            $q->where(function ($sub) use ($kw) {
                $sub->where('name', 'like', "%{$kw}%")
                    ->orWhere('slug', 'like', "%{$kw}%");
            });
        }

        $q->orderByDesc('id');

        $models = $q->paginate($request->page_size ?? 50);

        return $this->view("backend.{$this->plural}.index", [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'models' => $models,
        ]);
    }
}
```

## View conventions

- Index: `backend.{plural}.index`
- Create: `backend.{plural}.create`
- Edit: `backend.{plural}.edit`

> The base `Controller::view()` delegates to `wncms()->view(...)`, so app/theme/package overrides are respected.

## Route naming convention

Use plural resource prefixes for route names:

- `'{plural}.index'`, `'{plural}.create'`, `'{plural}.edit'`, `'{plural}.store'`, `'{plural}.update'`, `'{plural}.destroy'`, `'{plural}.bulk_delete'`.

> See the Routes section for complete backend route patterns.

## Customization tips

- Add validation (e.g., Form Requests) in `store()` / `update()`.
- Apply authorization (e.g., policies or middleware) at the route group or controller.
- If you manage files/media/tags (e.g., via Spatie Media Library or Tagify), perform those operations around `store()` / `update()` and call `$this->flush()` for relevant tags.
- For heavy lists, prefer pagination over `get()` and add indexes to frequently filtered columns.
- For cross-version compatibility, avoid hardcoding legacy foreign-key columns (for example `website_id`) when WNCMS multisite relation binding is available.

### WNCMS multisite compatibility pattern

When the model supports WNCMS multisite methods, write normal model fields only, then bind website relation via `bindWebsites(...)`:

```php
$payload = [
    'name' => $request->name,
    'type' => $request->type,
];

$model->update($payload);

if (method_exists($model, 'bindWebsites') && method_exists($model, 'getWebsiteMode')) {
    if (in_array($model::getWebsiteMode(), ['single', 'multi'], true) && $websiteId) {
        $model->bindWebsites($websiteId);
    }
}
```

## When to override vs. extend

- **Override** `index/create/edit` to add filters, joins, or eager loads.
- **Keep** `store/update/destroy/bulk_delete` unless your resource has non-standard persistence rules.
- **Always** maintain consistent messages and redirects to ensure a uniform admin UX.
