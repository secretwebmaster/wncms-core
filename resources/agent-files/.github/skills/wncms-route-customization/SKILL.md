---
name: wncms-route-customization
description: Use documented WNCMS route extension points and route conventions for custom web, backend, frontend, and API routes.
---

## Goal
Add or modify routes using the documented WNCMS route structure instead of editing core route files blindly.

## When To Use
- creating `routes/custom_frontend.php`
- creating `routes/custom_backend.php`
- creating `routes/custom_api.php`
- extending route groups in app or package code
- checking route naming, prefixes, middleware, or versioned API structure

## Read First
- `documentations/manual/developer/route/add-routes.md`
- `documentations/manual/developer/route/web.md`
- `documentations/manual/developer/route/backend.md`
- `documentations/manual/developer/route/frontend.md`
- `documentations/manual/developer/route/api.md`

## Hard Rules
- Prefer documented custom route files instead of modifying core package route files directly.
- `routes/custom_frontend.php` routes inherit the frontend middleware stack and should be named with the `frontend.` route namespace.
- `routes/custom_backend.php` routes inherit the `/panel` backend context with `auth`, `is_installed`, and `has_website`.
- Backend authorization should use `can:{model}_{action}` middleware with singular permission keys such as `post_index`.
- API routes are versioned under `v1` and use the `api.v1.` route name prefix.
- Homepage links should use `frontend.pages.home`, not `frontend.index`.

## Do Not Invent
- Do not add undocumented middleware, route name patterns, or custom file names.
- If route behavior is unclear, inspect the actual route file before changing it.
