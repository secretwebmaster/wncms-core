# API-only mode

Setting `blade_enabled` to false makes WNCMS-owned Blade routes and plugin UI routes return a plain `404`. API v2, callbacks, host application routes, and CLI recovery remain available.

API management uses `GET /api/v2/backend/security/blade` and `PATCH /api/v2/backend/security/blade` with `blade_mode_manage`, `security.blade`, `Idempotency-Key`, and a `blade.mode` step-up proof.

Recovery runbook:

```bash
php artisan wncms:blade:status
php artisan wncms:blade:disable --force
php artisan wncms:blade:enable
```

A missing policy means enabled. On an installed system, invalid or unavailable policy fails closed. Emergency enable favors recovery and reports an audit warning if the event store is unavailable.

See [Security policy](./security-policy.md) and [Troubleshooting](./troubleshooting.md).
