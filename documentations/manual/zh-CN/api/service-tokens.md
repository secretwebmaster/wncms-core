# Service tokens

Service token 是供服务器工作负载使用的非交互式 credential。管理时需要 interactive access token、`api_token_index`、`api_token_show`、`api_token_create`、`api_token_rotate` 或 `api_token_revoke` 权限，以及 `tokens.*` abilities。

路由为 `GET /api/v2/backend/auth/service-tokens`、`POST /api/v2/backend/auth/service-tokens`、`GET /api/v2/backend/auth/service-tokens/{token_id}`、`POST /api/v2/backend/auth/service-tokens/{token_id}/rotate` 与 `DELETE /api/v2/backend/auth/service-tokens/{token_id}`。Mutation 需要 `Idempotency-Key`；创建、轮换与撤销另需如 `service_token.create` 的用途限定 step-up proof。

请选择 ability template、明确 `website_ids` 与有限期限。Plaintext 只在创建／轮换 response boundary 返回，应存入 secrets manager；`wncms_st_EXAMPLE.NOT_A_REAL_SECRET` 是无法使用的示例。Service token 不可管理 credential 或签发 step-up proof。

参阅[Capabilities](./capabilities.md)与[安全策略](./security-policy.md)。
