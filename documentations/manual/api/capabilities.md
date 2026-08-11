# Runtime Capabilities

`GET /api/v2/capabilities` reports the operations that the installed WNCMS
system can expose to the current actor. Clients should use it for runtime
feature discovery instead of assuming that every configured or documented
operation is available.

## Request

The endpoint requires `enable_api_access`, the API whitelist gate, and an
authenticated session or personal access bearer token. External clients should
send the token returned by `/api/v2/backend/auth/login`.

```bash
curl "https://your-domain.com/api/v2/capabilities" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Accept: application/json"
```

The endpoint itself does not require a current website, because clients need
capabilities in order to diagnose missing website context.

## Permission And Website Filtering

Permission filtering is fail-closed:

- An operation with no permission is visible to an authenticated actor.
- An operation with a permission is omitted completely when `user->can(...)`
  denies it.
- An authorized website-scoped operation remains visible when there is no
  current website, but has `available: false` and
  `disabled_reasons: ["website.context_missing"]`.
- With website context present, that operation has `available: true` and an
  empty `disabled_reasons` list.

Domains remain in the response even when permission filtering leaves their
`operations` map empty. The empty map is serialized as a JSON object, not an
array.

## Response Shape

The standard six-key API v2 envelope contains `data.schema_version` and a
domain-keyed `data.domains` object. Each visible operation contains:

- `method`, `path`, `permission`, `ability`
- `website_scoped`, `risk`, `implementation`, `idempotent`
- `filters`, `sorts`, `includes`, `fields`
- `available`, `disabled_reasons`
- `request_schema`, `response_schema`

```json
{
  "code": 200,
  "status": "success",
  "message": "success",
  "data": {
    "schema_version": "2.0.0",
    "domains": {
      "links": {
        "key": "links",
        "label": "Links",
        "operations": {
          "backend.links.index": {
            "method": "GET",
            "path": "/api/v2/backend/links",
            "permission": "link_index",
            "ability": null,
            "website_scoped": true,
            "risk": "read",
            "implementation": "domain",
            "idempotent": false,
            "filters": [],
            "sorts": [],
            "includes": [],
            "fields": [],
            "available": true,
            "disabled_reasons": [],
            "request_schema": {},
            "response_schema": {}
          }
        }
      }
    }
  },
  "meta": {
    "request_id": "123e4567-e89b-42d3-a456-426614174000"
  },
  "errors": []
}
```

The schemas are JSON Schema 2020-12 compatible and may themselves be boolean
schemas. Extensions registered through configured contract providers appear
dynamically in the same response.

## Parity Interpretation

Use `implementation` when planning a complete admin client. Operations marked
`legacy_resource`, `legacy_controller`, or `legacy_bridge` are usable and
discoverable, but remain migration inventory and do not count as final v7
domain parity. Only a completed formal `domain` contract closes that parity gap.

The cancellation capability is visible only when the actor has
`operation_cancel`. See [Asynchronous Operations](./operations.md) for the
existing-install upgrade requirement.
