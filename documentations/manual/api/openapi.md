# OpenAPI 3.1

`GET /api/v2/openapi.json` returns the deterministic OpenAPI description of the
installed API v2 registry.

## Access And Middleware

The document does not require a token or website context. It still requires the
global `enable_api_access` setting and passes through `api`,
`api_v2_request_id`, and `api_v2_whitelist`. The body is the raw OpenAPI JSON
document, not the six-key API envelope; `X-Request-ID` is still included.

```bash
curl "https://your-domain.com/api/v2/openapi.json" \
  -H "Accept: application/json"
```

## Document Contract

The root declares:

- `openapi: 3.1.0`
- `jsonSchemaDialect: https://json-schema.org/draft/2020-12/schema`
- configured API title and version
- one path/method entry for every registry operation
- shared bearer authentication and success/error envelope schemas

Backend operations declare `bearerAuth`; frontend operations have an empty
security requirement. Operation request and response schemas come directly
from the same registry used by runtime capabilities.

## WNCMS Extensions

Every operation includes exactly these six WNCMS extension fields:

| Extension | Meaning |
| --- | --- |
| `x-wncms-permission` | WNCMS permission required from the actor, or `null` |
| `x-wncms-permission-mode` | `static` or validated `model_template` permission semantics |
| `x-wncms-ability` | Additional named ability, or `null` |
| `x-wncms-website-scoped` | Whether current website context is required |
| `x-wncms-risk` | Declared risk classification such as `read`, `write`, or `destructive` |
| `x-wncms-implementation` | `domain` or a legacy implementation classification |

For target-specific generic model operations, `x-wncms-permission` contains a
validated template such as `{model}_edit` or `{model}_bulk_delete`; the runtime
resolves it only against eligible model keys in the configured backend resource
catalog. Consumers must read `x-wncms-permission-mode`; literal permissions that
contain `{model}` are rejected and are never interpreted as templates.
The registry accepts this mode only for `backend.models.update` with
`{model}_edit`, and `backend.models.bulk_delete` or
`backend.models.bulk_force_delete` with `{model}_bulk_delete`.

```json
{
  "operationId": "backend.operations.cancel",
  "security": [{ "bearerAuth": [] }],
  "x-wncms-permission": "operation_cancel",
  "x-wncms-permission-mode": "static",
  "x-wncms-ability": null,
  "x-wncms-website-scoped": false,
  "x-wncms-risk": "destructive",
  "x-wncms-implementation": "domain"
}
```

`legacy_resource`, `legacy_controller`, and `legacy_bridge` entries are
intentionally present in OpenAPI for compatibility and discovery, but they do
not satisfy final v7 domain parity.

## Snapshot Workflow

The committed snapshot is `resources/api/openapi-v2.json`. Generate or check it
from the host application root:

```bash
php artisan wncms:api-v2-openapi --write=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

`--check` exits non-zero if the snapshot differs, is invalid, or cannot be
read. Contract validation also rejects duplicate operation IDs, duplicate
path/method pairs, missing routes, extra registered routes, and registry/OpenAPI
coverage drift.
## Authentication security

The generated `2.1.0` document includes Bearer, refresh Cookie, and CSRF schemes, public-operation security, write-only request secrets, and `x-wncms-*` security metadata. Generate clients from `GET /api/v2/openapi.json`, but use `GET /api/v2/capabilities` for the current actor's availability.

See [Contracts](./contracts.md) and [Security policy](./security-policy.md).
