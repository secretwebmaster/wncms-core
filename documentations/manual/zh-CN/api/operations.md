# 异步 Operations

API v2 Contract Kernel 为耗时工作定义了稳定但暂定的 cache-backed state
契约。公开 API 目前只允许 actor 读取或取消 operation；operation producer
透过内部 `OperationService` 排队及转换状态。系统没有公开
`POST /api/v2/backend/operations` 端点。

## 端点与授权

| 方法 | 端点 | 要求 |
| --- | --- | --- |
| `GET` | `/api/v2/backend/operations/{id}` | 已验证 owner |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | 已验证 owner、`operation_cancel`、`Idempotency-Key` |

这些端点要求 `enable_api_access`、whitelist gate，以及已验证 session 或
bearer token，不受 website-context gate 限制。不存在、已过期、属于其他 actor
的 operation ID 都会返回相同的 HTTP `404` `resource.not_found` 回应。

```bash
curl -X POST "https://your-domain.com/api/v2/backend/operations/OPERATION_UUID/cancel" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Idempotency-Key: cancel-operation-2026-0001" \
  -H "Accept: application/json"
```

## 既有安装的 Release Blocker

新安装会由 `RolesSeeder` 建立 `operation_cancel`。既有安装在获批的 v7 upgrade
flow 完成 permission seed，并明确指派给预期 role 或 user 以前，不得公开
cancellation。升级前，runtime capabilities 会隐藏 cancellation，直接请求会
返回 HTTP `403`；系统没有 fail-open fallback。

## Operation Object

Operation 包含 `id`、`type`、`status`、`progress`、`cancellable`、
`actor_id`、`website_ids`、`result`、`error`、`created_at`、`updated_at`、
`expires_at`。ID 为 UUID，progress 范围是 `0..100`，timestamp 使用标准 UTC
`YYYY-MM-DDTHH:MM:SSZ` 格式。

## 状态与转换

| From | Action | To | 说明 |
| --- | --- | --- | --- |
| none | queue | `queued` | Progress 从 `0` 开始 |
| `queued` | start | `running` | 保留不可变 expiry |
| `running` | progress | `running` | Progress 必须保持在 `0..100` |
| `running` | succeed | `succeeded` | Progress 变为 `100`，可设定 result |
| `running` | fail | `failed` | 设定 structured error |
| `queued` 或 `running` | cancel | `cancelled` | 要求 `cancellable: true` |
| `cancelled` | 再次 cancel | `cancelled` | Idempotent，不延长 lifetime |

`succeeded`、`failed`、`cancelled` 都是 terminal 且不再可取消。无效或并发
transition 返回 HTTP `409` `request.conflict`。Transition 会在每个 operation
的 atomic lock 下进行有限次 compare-and-swap retry，避免 stale worker 复活
terminal state。

## TTL 与 Storage 限制

Operation 默认 TTL 为 `86400` 秒。`expires_at` 在 queue 时固定；read 与
transition 都不会延长。Expired record 会视为不存在，并在 lock 下安全清除。

此 repository 是暂定的 cache-backed operation state，不是 durable job history
或 audit log。Cache store 必须支援 atomic lock。Production 预设精确允许 Laravel
Redis、Memcached、Database store；array、null、failover、DynamoDB 会被拒绝。
只有在所有 API 与 queue worker 共用同一个 shared volume 时，才可明确将
FileStore 加入 allowlist。所有 process 必须使用相同的已设定 store。

请使用 `WNCMS_API_V2_OPERATION_STORE` 与
`wncms-api-v2.operations.ttl_seconds` 设定 store 与 TTL。

## Idempotency

Cancellation 要求已设定的 `Idempotency-Key` header，key 长度必须是 `8` 到
`255` bytes。Cache scope 包含 actor、token/session、operation、受信任 website
context 与 key；request fingerprint 包含 method、route parameter、query、body，
以及 upload metadata/content hash。

第一个低于 HTTP `500` 的完整 JSON response 默认会 cache `86400` 秒。完全相同
的重复请求会返回原始 status、raw body、content type、原始 `X-Request-ID`，
并加入 `Idempotency-Replayed: true`。相同 key 搭配不同输入会返回 HTTP `409`
`idempotency.key_conflict`；并发的相同 mutation 返回
`idempotency.in_progress`。HTTP `5xx` 不会被 cache。

Idempotency store 必须支援 atomic lock；production 环境还必须由所有 process
共享。请使用 `WNCMS_API_V2_IDEMPOTENCY_STORE` 设定，默认 TTL 由
`wncms-api-v2.idempotency.ttl_seconds` 控制。

## Resource Revisions

正式 domain mutation 也可输出 `ETag` 并要求 `If-Match`。缺少或过期 revision
会以 HTTP `409` `request.conflict` 失败。此 optimistic-concurrency primitive
与 idempotency 分开：`If-Match` 保护 resource state，`Idempotency-Key` 保护
mutation replay。
