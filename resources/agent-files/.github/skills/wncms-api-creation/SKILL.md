---
name: wncms-api-creation
description: Create app-level WNCMS JSON APIs using ApiController, documented versioned routes, feature checks, and token authentication patterns.
---

## Goal
Implement host-project API endpoints in a way that matches documented WNCMS API controller and route patterns.

## Read Before Coding
- `documentations/manual/developer/controller/api-controller.md`
- `documentations/manual/developer/route/api.md`
- `documentations/manual/developer/route/add-routes.md`
- `documentations/manual/api/getting-started.md`
- `documentations/manual/api/troubleshooting.md`

## Hard Rules
- Place custom API controllers in `app/Http/Controllers/Api/V1`.
- Extend `Wncms\Http\Controllers\Api\V1\ApiController`.
- Use versioned route names under the documented `api.v1.` namespace.
- Use `checkApiEnabled(...)` inside actions when endpoint-level gating is required.
- Use `authenticateByApiToken($request)` when the endpoint requires authenticated mutation access.
- Keep JSON responses consistent with documented success and error payload shapes.
- Prefer Managers for list/detail fetching instead of duplicating query logic in controllers.

## Checklist
1. Create or update the custom API controller in `App\Http\Controllers\Api\V1`.
2. Register route definitions in the correct versioned API group.
3. Add feature checks and token auth only where the endpoint requires them.
4. Return stable JSON payloads with appropriate HTTP status codes.

## Do Not Invent
- Do not claim package-internal API metadata requirements unless the task is explicitly about maintaining `wncms-core`.
- Do not bypass documented API gating and token authentication helpers when the controller extends `ApiController`.
