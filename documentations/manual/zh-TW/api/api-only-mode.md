# API-only mode

將 `blade_enabled` 設為 false，WNCMS Blade 與 plugin UI 路由會回傳純文字 `404`；API v2、callbacks、宿主路由與 CLI recovery 維持可用。

API 管理使用 `GET /api/v2/backend/security/blade` 與 `PATCH /api/v2/backend/security/blade`，並需 `blade_mode_manage`、`security.blade`、`Idempotency-Key` 與 `blade.mode` step-up proof。

復原 runbook：

```bash
php artisan wncms:blade:status
php artisan wncms:blade:disable --force
php artisan wncms:blade:enable
```

Policy 缺少時視為啟用。已安裝系統若 policy 無效或無法取得則 fail closed。Emergency enable 優先復原；event store 不可用時會回報 audit warning。

參閱[安全政策](./security-policy.md)與[疑難排解](./troubleshooting.md)。
