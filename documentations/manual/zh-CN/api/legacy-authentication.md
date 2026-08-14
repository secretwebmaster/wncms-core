# Legacy authentication migration

Legacy personal access token 是限期 migration adapter，默认由 `api_legacy_personal_tokens_enabled` 停用。请设置明确 UTC `api_legacy_personal_tokens_cutoff_at`，migration window 不应超过 90 天。

接受 legacy credential 的 response 会带 `Deprecation`、`Sunset` 与 `Link` headers。Legacy token 仅限明确 allowlist 的非 critical operations，永远不能取得 step-up 或 credential-management 权限。

操作命令：

```bash
php artisan wncms:auth:legacy-status --json
php artisan wncms:auth:legacy-cutoff 2026-11-12T00:00:00Z --force
php artisan wncms:auth:legacy-revoke-all --force
```

将工作负载迁移到具 scope 的[service tokens](./service-tokens.md)，确认 capability metadata、撤销 legacy rows，最后停用 adapter。稳定错误包括 `authentication.legacy_disabled`、`authentication.legacy_expired` 与 `authentication.invalid_token`。
