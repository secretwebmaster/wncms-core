# API-only mode

将 `blade_enabled` 设为 false，WNCMS Blade 与 plugin UI 路由会返回纯文本 `404`；API v2、callbacks、宿主路由与 CLI recovery 保持可用。

API 管理使用 `GET /api/v2/backend/security/blade` 与 `PATCH /api/v2/backend/security/blade`，并需 `blade_mode_manage`、`security.blade`、`Idempotency-Key` 与 `blade.mode` step-up proof。

恢复 runbook：

```bash
php artisan wncms:blade:status
php artisan wncms:blade:disable --force
php artisan wncms:blade:enable
```

Policy 缺少时视为启用。已安装系统若 policy 无效或无法获取则 fail closed。Emergency enable 优先恢复；event store 不可用时会报告 audit warning。

参阅[安全策略](./security-policy.md)与[疑难排解](./troubleshooting.md)。
