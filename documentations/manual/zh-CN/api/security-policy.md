# 安全策略

每个 API v2 operation 都通过 `GET /api/v2/capabilities` 与 `GET /api/v2/openapi.json` 公布 `security_risk`、允许的 credential types、step-up purposes、action-plan eligibility、website scope mode 与 idempotency requirements。

敏感 mutation 需要 `X-WNCMS-Step-Up`。当 `api_high_risk_action_mode` 为 `planned` 时，高／关键风险操作可能需要 `X-WNCMS-Confirmation`。Idempotent mutation 需要 `Idempotency-Key`。网站权限取 actor、credential `website_ids` 与明确 request scope 的交集；hostname 永不扩张权限。

安全事件只能以 `GET /api/v2/backend/security/events` 与 `GET /api/v2/backend/security/events/{event_id}` 读取，并需 `security_event_index` 或 `security_event_show`。`api_security_event_retention_days` 为 30–365；`wncms:auth:prune-security-events` 每日执行。

参阅[Contracts](./contracts.md)、[Sessions](./sessions.md)与[API-only mode](./api-only-mode.md)。
