# Sessions

Interactive login creates a short-lived access token, refresh credential, and server-side session. Use `POST /api/v2/backend/auth/login`, then `POST /api/v2/backend/auth/refresh` to rotate the refresh credential. JSON transport accepts `refresh_token` in the request body; Cookie transport uses an `httpOnly` refresh Cookie plus the bound CSRF Cookie/header pair.

Authenticated session operations:

- `GET /api/v2/backend/auth/me`
- `GET /api/v2/backend/auth/sessions`
- `DELETE /api/v2/backend/auth/sessions/{session_id}` with `Idempotency-Key`
- `POST /api/v2/backend/auth/logout-all` with `Idempotency-Key`

Password change and reset revoke every interactive session and access/refresh credential. Treat `authentication.invalid_token`, `authentication.access_token_expired`, and `authentication.refresh_reuse_detected` as a sign-in boundary. Never store a refresh token in `localStorage`; keep browser credentials in secure server-managed Cookies or a server-side BFF session.

See [Authentication](./authentication.md), [Security policy](./security-policy.md), and [Errors](./errors.md).
