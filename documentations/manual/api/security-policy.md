# Security policy

Every API v2 operation publishes `security_risk`, accepted credential types, step-up purposes, action-plan eligibility, website scope mode, and idempotency requirements through `GET /api/v2/capabilities` and `GET /api/v2/openapi.json`.

Sensitive mutations require `X-WNCMS-Step-Up`. High/critical operations may require `X-WNCMS-Confirmation` when `api_high_risk_action_mode` is `planned`. Idempotent mutations require `Idempotency-Key`. Website-bound operations use the intersection of actor access, credential `website_ids`, and explicit request scope; hostnames never expand authority.

Security events are read-only at `GET /api/v2/backend/security/events` and `GET /api/v2/backend/security/events/{event_id}` with `security_event_index` or `security_event_show`. `api_security_event_retention_days` accepts 30–365; `wncms:auth:prune-security-events` is scheduled daily.

See [Contracts](./contracts.md), [Sessions](./sessions.md), and [API-only mode](./api-only-mode.md).
