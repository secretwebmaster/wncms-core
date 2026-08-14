# Sessions

交互式登录会创建短效 access token、refresh credential 与服务器端 session。使用 `POST /api/v2/backend/auth/login` 登录，再以 `POST /api/v2/backend/auth/refresh` 轮换 refresh credential。JSON transport 从 body 接收 `refresh_token`；Cookie transport 使用 `httpOnly` refresh Cookie 与绑定的 CSRF Cookie/header。

已验证的 session 操作：

- `GET /api/v2/backend/auth/me`
- `GET /api/v2/backend/auth/sessions`
- `DELETE /api/v2/backend/auth/sessions/{session_id}`，带 `Idempotency-Key`
- `POST /api/v2/backend/auth/logout-all`，带 `Idempotency-Key`

密码变更或重置会撤销所有 interactive session 与 access/refresh credential。遇到 `authentication.invalid_token`、`authentication.access_token_expired` 或 `authentication.refresh_reuse_detected` 时应重新登录。切勿把 refresh token 放进 `localStorage`；浏览器应使用安全的服务器管理 Cookie 或 BFF session。

参阅[验证](./authentication.md)、[安全策略](./security-policy.md)与[错误](./errors.md)。
