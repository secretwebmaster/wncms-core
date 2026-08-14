# 安全政策

每個 API v2 operation 都透過 `GET /api/v2/capabilities` 與 `GET /api/v2/openapi.json` 公布 `security_risk`、允許的 credential types、step-up purposes、action-plan eligibility、website scope mode 與 idempotency requirements。

敏感 mutation 需要 `X-WNCMS-Step-Up`。當 `api_high_risk_action_mode` 為 `planned` 時，高／關鍵風險操作可能需要 `X-WNCMS-Confirmation`。Idempotent mutation 需要 `Idempotency-Key`。網站權限取 actor、credential `website_ids` 與明確 request scope 的交集；hostname 永不擴張權限。

安全事件僅能以 `GET /api/v2/backend/security/events` 與 `GET /api/v2/backend/security/events/{event_id}` 讀取，並需 `security_event_index` 或 `security_event_show`。`api_security_event_retention_days` 為 30–365；`wncms:auth:prune-security-events` 每日執行。

參閱[Contracts](./contracts.md)、[Sessions](./sessions.md)與[API-only mode](./api-only-mode.md)。
