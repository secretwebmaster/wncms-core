# Service tokens

Service token 是供伺服器工作負載使用的非互動式 credential。管理時需要 interactive access token、`api_token_index`、`api_token_show`、`api_token_create`、`api_token_rotate` 或 `api_token_revoke` 權限，以及 `tokens.*` abilities。

路由為 `GET /api/v2/backend/auth/service-tokens`、`POST /api/v2/backend/auth/service-tokens`、`GET /api/v2/backend/auth/service-tokens/{token_id}`、`POST /api/v2/backend/auth/service-tokens/{token_id}/rotate` 與 `DELETE /api/v2/backend/auth/service-tokens/{token_id}`。Mutation 需要 `Idempotency-Key`；建立、輪替與撤銷另需如 `service_token.create` 的用途限定 step-up proof。

請選擇 ability template、明確 `website_ids` 與有限期限。Plaintext 只在建立／輪替 response boundary 回傳，應存入 secrets manager；`wncms_st_EXAMPLE.NOT_A_REAL_SECRET` 是無法使用的範例。Service token 不可管理 credential 或簽發 step-up proof。

參閱[Capabilities](./capabilities.md)與[安全政策](./security-policy.md)。
