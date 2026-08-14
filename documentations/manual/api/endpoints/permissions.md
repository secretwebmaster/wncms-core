# Permissions API v2

Permissions are global authorization records. Unlike content resources, these endpoints do not accept or require a website selector. Clients must discover `website_scoped: false` and `website_scope_mode: none` from capabilities instead of sending a synthetic website context.

| Method | Endpoint | Permission |
| --- | --- | --- |
| GET | `/api/v2/backend/permissions` | `permission_index` |
| GET | `/api/v2/backend/permissions/{id}` | `permission_show` |
| POST | `/api/v2/backend/permissions` | `permission_create` |
| PATCH | `/api/v2/backend/permissions/{id}` | `permission_edit` |
| DELETE | `/api/v2/backend/permissions/{id}` | `permission_delete` |
| POST | `/api/v2/backend/permissions/bulk_delete` | `permission_bulk_delete` |

The index accepts `keyword`, `page`, and `per_page` and returns `roles_count`. Create and update accept `name` and optional `guard_name` (`web` by default). Credential-sensitive mutations follow the step-up and risk metadata published by capabilities and OpenAPI. Successful mutations invalidate both Spatie and WNCMS permission caches.
