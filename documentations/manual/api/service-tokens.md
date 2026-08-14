# Service tokens

Service tokens are non-interactive credentials for server workloads. Management requires an interactive access token, permissions `api_token_index`, `api_token_show`, `api_token_create`, `api_token_rotate`, or `api_token_revoke`, and abilities under `tokens.*`.

Routes are `GET /api/v2/backend/auth/service-tokens`, `POST /api/v2/backend/auth/service-tokens`, `GET /api/v2/backend/auth/service-tokens/{token_id}`, `POST /api/v2/backend/auth/service-tokens/{token_id}/rotate`, and `DELETE /api/v2/backend/auth/service-tokens/{token_id}`. Mutations require `Idempotency-Key`; create, rotate, and revoke require a purpose-bound step-up proof such as `service_token.create`.

Choose an ability template, explicit `website_ids`, and a bounded expiry. Plaintext is returned only at create/rotate response boundaries. Store it in a secrets manager; examples such as `wncms_st_EXAMPLE.NOT_A_REAL_SECRET` are intentionally nonworking. Service tokens cannot manage credentials or issue step-up proofs.

See [Capabilities](./capabilities.md) and [Security policy](./security-policy.md).
