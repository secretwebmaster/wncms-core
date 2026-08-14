# API v2 Contracts

The API v2 Contract Kernel is the machine-readable source of truth for WNCMS
domains and operations. It drives runtime capabilities, the OpenAPI document,
and contract-parity validation from the same `ApiContractRegistry`.

## Contract Endpoints

| Method | Endpoint | Authentication | Purpose |
| --- | --- | --- | --- |
| `GET` | `/api/v2/openapi.json` | None | Installed-system OpenAPI 3.1 document |
| `GET` | `/api/v2/capabilities` | Authenticated session or personal access bearer token | Operations visible to the current actor |
| `GET` | `/api/v2/backend/operations/{id}` | Authenticated session or personal access bearer token | Read an owned asynchronous operation |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | Authentication, `operation_cancel`, and `Idempotency-Key` | Cancel an owned cancellable operation |

All four endpoints require the global `enable_api_access` setting. They run
through `api_v2_request_id`, `api`, and `api_v2_whitelist` in that order before
endpoint-specific authentication. An active whitelist accepts an exact request
IP or exact `Origin`/`Referer` host.

## Stable Response Envelope

Every enveloped API v2 response has exactly these six top-level keys, in this
order: `code`, `status`, `message`, `data`, `meta`, and `errors`.

```json
{
  "code": 200,
  "status": "success",
  "message": "success",
  "data": {},
  "meta": {
    "request_id": "123e4567-e89b-42d3-a456-426614174000"
  },
  "errors": []
}
```

Failures use `status: "fail"` and add a stable machine-readable
`meta.error_code`. The UUID in `meta.request_id` always equals the
`X-Request-ID` response header. A valid UUID supplied in the request's
`X-Request-ID` is preserved; any missing or malformed value is replaced.

`GET /api/v2/openapi.json` is intentionally the exception: it returns the raw
OpenAPI document rather than an envelope, but still returns `X-Request-ID`.
Unexpected exception details are hidden when `APP_DEBUG=false`.

## Operation Metadata

Each registry operation declares its stable ID, domain, surface, HTTP method,
path, route name, permission, ability, website scope, risk, implementation
classification, request/response JSON Schemas, allowed filters, sorts,
includes, fields, and whether idempotency is required.

`surface` is strictly `frontend` or `backend` and defines the transport and
authentication boundary; it is not an operation-ID namespace. Stable domain
IDs therefore remain valid across transport classification (for example,
`system.translations` is on the `frontend` surface). Query metadata is a
unique list of non-empty, valid UTF-8 ASCII identifiers and may use dotted
segments such as `author.name`.

Implementation classifications have parity meaning:

- `domain`: a formal domain implementation eligible for final v7 parity.
- `legacy_resource`: the generic legacy resource controller.
- `legacy_controller`: a dedicated legacy API v2 controller.
- `legacy_bridge`: a legacy backend bridge action.

Legacy classifications remain discoverable so an API client can use the
installed system, but `legacy_resource`, `legacy_controller`, and
`legacy_bridge` do not satisfy final v7 domain parity.

## List Query Contract

Formal list operations may declare allowlists for `filter`, `sort`, `include`,
and `fields`. The common resolver also accepts `page`, `per_page`, `keyword`,
and `direction`. `page` and `per_page` must be positive integers within PHP's native integer range,
`per_page` is capped at `100`, and `direction` is `asc` or `desc`. Undeclared
filters, sorts, includes, and fields fail with `validation.failed`.

```text
?page=1&per_page=20&keyword=demo&filter[status]=active&sort=id&direction=desc&include=owner&fields=id,name
```

## Optimistic Concurrency

Domain mutations can use the Contract Kernel concurrency primitive to emit an
`ETag` and require the same revision in `If-Match`. The revision covers the
model class, route key, and `updated_at`. A missing or stale `If-Match` fails
with HTTP `409` and `meta.error_code: "request.conflict"`. Weak and quoted ETag
syntax is accepted. This primitive is available to formal domain migrations;
legacy operations are not implied to enforce it.

## Contract Validation

Maintainers can detect route, registry, and OpenAPI drift with:

```bash
php artisan wncms:check-backend-api-v2-parity --contract --json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

See [Runtime Capabilities](./capabilities.md), [OpenAPI 3.1](./openapi.md),
[Asynchronous Operations](./operations.md), and [Error Reference](./errors.md).
## Security contract fields

Contract schema `2.1.0` makes credential types, step-up, action-plan eligibility, legacy access, website scope, idempotency, and refresh transports explicit. Critical operations cannot accept a legacy personal token. Invalid combinations fail contract validation instead of being guessed at runtime.

See [Capabilities](./capabilities.md) and [OpenAPI](./openapi.md).
