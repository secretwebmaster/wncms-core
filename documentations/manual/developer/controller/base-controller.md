# Base Controller

WNCMS’s base controller is a thin foundation that centralizes view resolution. In most cases, **you should not extend this class directly**. Instead, extend one of its child controllers that encapsulate layer-specific behavior.

## Which controller should I extend?

### For Backend (Admin CRUD)

**Extend:** `Wncms\Http\Controllers\Backend\BackendController`

**Use for:** Admin CRUD pages, settings screens, listing and editing models in the backend.

**Why:** Provides model naming, cache tag helpers, and unified CRUD patterns for backend.

**Namespace:** `App\Http\Controllers\Backend\...` or package backend controllers.

**Views:** `backend.{models}.*` Blade views.

### For Frontend (Public Theme Pages)

**Extend:** `Wncms\Http\Controllers\Frontend\FrontendController`

**Use for:** Public site pages rendered by the active theme (home, posts, pages, tags, etc.).

**Why:** Theme-aware rendering, website context, and frontend conventions.

**Namespace:** `App\Http\Controllers\Frontend\...` or package frontend controllers.

**Views:** `frontend.*` Blade views resolved via theme.

### For API (JSON Endpoints)

**Extend:** `Wncms\Http\Controllers\Api\ApiController`

**Use for:** JSON APIs consumed by external apps (Vue, Next.js, mobile).

**Why:** API concerns such as auth, standardized responses/resources.

**Namespace:** `App\Http\Controllers\Api\V1\...` or package API controllers.

**Response:** JSON responses / API resources.

## When to extend the base class directly

- Building a new controller **layer** (e.g., a specialized subsystem) that other controllers will extend.
- Creating a shared abstraction that adds cross-cutting helpers before layering (rare).

If you don’t fit one of these, use a child controller above.

## Shared multisite helpers

The base `Controller` now provides reusable multisite helper methods so backend/frontend controllers can share the same website resolution and capability checks:

```php
protected function supportsWncmsMultisite(string $modelClass): bool
protected function resolveModelWebsiteIds(string $modelClass, array|string|int|null $websiteIds = null): array
protected function syncModelWebsites($model, array $websiteIds): void
```

- `supportsWncmsMultisite()`:
  - checks model support via `getWebsiteMode()` and `bindWebsites()`
  - treats `single` and `multi` as multisite-enabled modes
- `resolveModelWebsiteIds()`:
  - accepts website IDs as array or comma-separated string
  - in single mode, the first parsed ID is used
  - in multi mode, all parsed IDs are used
  - when `gss('multi_website')` is disabled, falls back to current website id
  - normalizes IDs to existing website records
- `syncModelWebsites()`:
  - syncs binding according to model website mode
  - `single`: bind first selected website
  - `multi`: clear existing bindings then bind selected websites

## Next steps

- Backend: see [Backend Controller](./backend-controller.md)
- Frontend: see [Frontend Controller](./frontend-controller.md)
- API: see [API Controller](./api-controller.md)
- Scaffolding: see [Create a Controller](./create-a-controller.md)
