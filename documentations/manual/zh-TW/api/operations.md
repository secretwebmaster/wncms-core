# 非同步 Operations

API v2 Contract Kernel 為耗時工作定義了穩定但暫定的 cache-backed state
契約。公開 API 目前只允許 actor 讀取或取消 operation；operation producer
透過內部 `OperationService` 排隊及轉換狀態。系統沒有公開
`POST /api/v2/backend/operations` 端點。

## 端點與授權

| 方法 | 端點 | 要求 |
| --- | --- | --- |
| `GET` | `/api/v2/backend/operations/{id}` | 已驗證 owner |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | 已驗證 owner、`operation_cancel`、`Idempotency-Key` |

這些端點要求 `enable_api_access`、whitelist gate，以及已驗證 session 或
bearer token，不受 website-context gate 限制。不存在、已過期、屬於其他 actor
的 operation ID 都會回傳相同的 HTTP `404` `resource.not_found` 回應。

```bash
curl -X POST "https://your-domain.com/api/v2/backend/operations/OPERATION_UUID/cancel" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Idempotency-Key: cancel-operation-2026-0001" \
  -H "Accept: application/json"
```

## 既有安裝的 Release Blocker

新安裝會由 `RolesSeeder` 建立 `operation_cancel`。既有安裝在獲准的 v7 upgrade
flow 完成 permission seed，並明確指派給預期 role 或 user 以前，不得公開
cancellation。升級前，runtime capabilities 會隱藏 cancellation，直接請求會
回傳 HTTP `403`；系統沒有 fail-open fallback。

## Operation Object

Operation 包含 `id`、`type`、`status`、`progress`、`cancellable`、
`actor_id`、`website_ids`、`result`、`error`、`created_at`、`updated_at`、
`expires_at`。ID 為 UUID，progress 範圍是 `0..100`，timestamp 使用標準 UTC
`YYYY-MM-DDTHH:MM:SSZ` 格式。

## 狀態與轉換

| From | Action | To | 說明 |
| --- | --- | --- | --- |
| none | queue | `queued` | Progress 從 `0` 開始 |
| `queued` | start | `running` | 保留不可變 expiry |
| `running` | progress | `running` | Progress 必須維持在 `0..100` |
| `running` | succeed | `succeeded` | Progress 變為 `100`，可設定 result |
| `running` | fail | `failed` | 設定 structured error |
| `queued` 或 `running` | cancel | `cancelled` | 要求 `cancellable: true` |
| `cancelled` | 再次 cancel | `cancelled` | Idempotent，不延長 lifetime |

`succeeded`、`failed`、`cancelled` 都是 terminal 且不再可取消。無效或並行
transition 回傳 HTTP `409` `request.conflict`。Transition 會在每個 operation
的 atomic lock 下進行有限次 compare-and-swap retry，避免 stale worker 復活
terminal state。

## TTL 與 Storage 限制

Operation 預設 TTL 為 `86400` 秒。`expires_at` 在 queue 時固定；read 與
transition 都不會延長。Expired record 會視為不存在，並在 lock 下安全清除。

此 repository 是暫定的 cache-backed operation state，不是 durable job history
或 audit log。Cache store 必須支援 atomic lock。Production 預設精確允許 Laravel
Redis、Memcached、Database store；array、null、failover、DynamoDB 會被拒絕。
只有在所有 API 與 queue worker 共用同一個 shared volume 時，才可明確將
FileStore 加入 allowlist。所有 process 必須使用相同的已設定 store。

請使用 `WNCMS_API_V2_OPERATION_STORE` 與
`wncms-api-v2.operations.ttl_seconds` 設定 store 與 TTL。

## Idempotency

Cancellation 要求已設定的 `Idempotency-Key` header，key 長度必須是 `8` 到
`255` bytes。Cache scope 包含 actor、token/session、operation、受信任 website
context 與 key；request fingerprint 包含 method、route parameter、query、body，
以及 upload metadata/content hash。

第一個低於 HTTP `500` 的完整 JSON response 預設會 cache `86400` 秒。完全相同
的重複請求會回傳原始 status、raw body、content type、原始 `X-Request-ID`，
並加入 `Idempotency-Replayed: true`。相同 key 搭配不同輸入會回傳 HTTP `409`
`idempotency.key_conflict`；並行的相同 mutation 回傳
`idempotency.in_progress`。HTTP `5xx` 不會被 cache。

Idempotency store 必須支援 atomic lock；production 環境還必須由所有 process
共享。請使用 `WNCMS_API_V2_IDEMPOTENCY_STORE` 設定，預設 TTL 由
`wncms-api-v2.idempotency.ttl_seconds` 控制。

## Resource Revisions

正式 domain mutation 也可輸出 `ETag` 並要求 `If-Match`。缺少或過期 revision
會以 HTTP `409` `request.conflict` 失敗。此 optimistic-concurrency primitive
與 idempotency 分開：`If-Match` 保護 resource state，`Idempotency-Key` 保護
mutation replay。
