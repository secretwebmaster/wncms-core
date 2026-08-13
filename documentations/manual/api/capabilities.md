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
- Generic model operations publish a target template such as `{model}_edit` or
  `{model}_bulk_delete`. They are visible only when the actor has at least one
  matching permission for an eligible model in the configured backend resource
  catalog. Eligibility requires the configured class to be an instantiable
  Eloquent model with an exact public static `$modelKey`; invalid entries cannot
  disclose the operation. At request time, the `model` selector is normalized
  through that allowlist and authorization binds the exact resolved class to a
  server-side request attribute. The controller consumes that class without a
  namespace fallback, after checking the target key, action suffix, and concrete
  permission again. Arbitrary class names, unknown model keys, and client body
  fields that resemble the trusted attribute are rejected.
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

- `method`, `path`, `permission`, `permission_mode`, `ability`
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
            "request_schema": {
              "type": "object",
              "properties": {}
            },
            "response_schema": {
              "type": "object",
              "properties": {}
            }
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

`permission_mode` is `static` for literal WNCMS permissions and
`model_template` only for the validated generic model operations. Consumers
must use this field instead of inferring mode from permission text.

## Parity Interpretation

Use `implementation` when planning a complete admin client. Operations marked
`legacy_resource`, `legacy_controller`, or `legacy_bridge` are usable and
discoverable, but remain migration inventory and do not count as final v7
domain parity. Only a completed formal `domain` contract closes that parity gap.

The cancellation capability is visible only when the actor has
`operation_cancel`. See [Asynchronous Operations](./operations.md) for the
existing-install upgrade requirement.
