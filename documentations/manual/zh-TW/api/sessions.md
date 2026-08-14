# Sessions

互動式登入會建立短效 access token、refresh credential 與伺服器端 session。使用 `POST /api/v2/backend/auth/login` 登入，再以 `POST /api/v2/backend/auth/refresh` 輪替 refresh credential。JSON transport 從 body 接收 `refresh_token`；Cookie transport 使用 `httpOnly` refresh Cookie 與綁定的 CSRF Cookie/header。

已驗證的 session 操作：

- `GET /api/v2/backend/auth/me`
- `GET /api/v2/backend/auth/sessions`
- `DELETE /api/v2/backend/auth/sessions/{session_id}`，帶 `Idempotency-Key`
- `POST /api/v2/backend/auth/logout-all`，帶 `Idempotency-Key`

密碼變更或重設會撤銷所有 interactive session 與 access/refresh credential。遇到 `authentication.invalid_token`、`authentication.access_token_expired` 或 `authentication.refresh_reuse_detected` 時應重新登入。切勿把 refresh token 放進 `localStorage`；瀏覽器應使用安全的伺服器管理 Cookie 或 BFF session。

參閱[驗證](./authentication.md)、[安全政策](./security-policy.md)與[錯誤](./errors.md)。
