# Asynchronous Operations

The API v2 Contract Kernel defines a stable, provisional cache-backed state
contract for long-running work. The public API currently allows an actor to
read or cancel an operation; operation producers queue and transition work
through the internal `OperationService`. There is no public
`POST /api/v2/backend/operations` endpoint.

## Endpoints And Authorization

| Method | Endpoint | Requirements |
| --- | --- | --- |
| `GET` | `/api/v2/backend/operations/{id}` | Authenticated owner |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | Authenticated owner, `operation_cancel`, `Idempotency-Key` |

These endpoints require `enable_api_access`, the whitelist gate, and an
authenticated session or bearer token. They are not website-context gated.
Missing, expired, and other actors' operation IDs all return the same HTTP
`404` `resource.not_found` response.

```bash
curl -X POST "https://your-domain.com/api/v2/backend/operations/OPERATION_UUID/cancel" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Idempotency-Key: cancel-operation-2026-0001" \
  -H "Accept: application/json"
```

## Existing-Install Release Blocker

New installations receive `operation_cancel` from `RolesSeeder`. Existing
installations must not expose cancellation until the approved v7 upgrade flow
has seeded the permission and explicitly assigned it to the intended roles or
users. Before that upgrade, cancellation is hidden from runtime capabilities
and direct requests fail with HTTP `403`; there is no fail-open fallback.

## Operation Object

An operation contains `id`, `type`, `status`, `progress`, `cancellable`,
`actor_id`, `website_ids`, `result`, `error`, `created_at`, `updated_at`, and
`expires_at`. IDs are UUIDs, progress is `0..100`, and timestamps use canonical
UTC `YYYY-MM-DDTHH:MM:SSZ` form.

## States And Transitions

| From | Action | To | Notes |
| --- | --- | --- | --- |
| none | queue | `queued` | Progress starts at `0` |
| `queued` | start | `running` | Preserves the immutable expiry |
| `running` | progress | `running` | Progress must remain `0..100` |
| `running` | succeed | `succeeded` | Progress becomes `100`; result may be set |
| `running` | fail | `failed` | Structured error is set |
| `queued` or `running` | cancel | `cancelled` | Requires `cancellable: true` |
| `cancelled` | cancel again | `cancelled` | Idempotent; lifetime is not extended |

`succeeded`, `failed`, and `cancelled` are terminal and no longer cancellable.
Invalid or concurrent transitions return HTTP `409` with `request.conflict`.
Transitions use bounded compare-and-swap retries under a per-operation atomic
lock so a stale worker cannot revive a terminal state.

## TTL And Storage Limitation

The default operation TTL is `86400` seconds. `expires_at` is fixed when the
operation is queued; reads and transitions do not extend it. Expired records
behave as missing and are cleaned up safely under a lock.

This repository is provisional cache-backed operation state, not durable job
history or an audit log. Its cache store must support atomic locks. In
production, the exact trusted defaults are Laravel Redis, Memcached, and
Database stores. Array, null, failover, and DynamoDB stores are rejected.
FileStore may be explicitly allowlisted only when every API and queue worker
uses the same shared volume. All processes must use the same configured store.

Configure the store and TTL with `WNCMS_API_V2_OPERATION_STORE` and
`wncms-api-v2.operations.ttl_seconds`.

## Idempotency

Cancellation requires the configured `Idempotency-Key` header. Keys must be
valid UTF-8 between `8` and `255` bytes. The cache scope includes actor, token/session,
operation, trusted website context, and key; the request fingerprint includes
method, route parameters, query, body, and upload metadata/content hashes.

The first completed JSON response below HTTP `500` is cached for `86400`
seconds by default. An exact repeat returns the original status, raw body,
content type, and original `X-Request-ID`, plus
`Idempotency-Replayed: true`. Reusing the key with different input returns HTTP
`409` `idempotency.key_conflict`; a concurrent identical mutation returns
`idempotency.in_progress`. HTTP `5xx` responses are not cached.

The idempotency store must support atomic locks. In production, the exact
trusted defaults are Laravel Redis, Memcached, and Database stores. Array,
null, failover, and DynamoDB stores are rejected. FileStore and unknown
lock-capable stores require exact class allowlisting; FileStore is safe only
when every API process shares the same volume. Configure the store with
`WNCMS_API_V2_IDEMPOTENCY_STORE`, the exact class list with
`wncms-api-v2.idempotency.allowed_shared_store_classes`, and the default TTL
with `wncms-api-v2.idempotency.ttl_seconds`.

## Resource Revisions

Formal domain mutations can additionally emit `ETag` and require `If-Match`.
Missing or stale revisions fail with HTTP `409` `request.conflict`. This
optimistic-concurrency primitive is separate from idempotency: `If-Match`
protects resource state, while `Idempotency-Key` protects mutation replay.
