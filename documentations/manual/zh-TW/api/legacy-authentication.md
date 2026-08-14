# Legacy authentication migration

Legacy personal access token 是限期 migration adapter，預設由 `api_legacy_personal_tokens_enabled` 停用。請設定明確 UTC `api_legacy_personal_tokens_cutoff_at`，migration window 不應超過 90 天。

接受 legacy credential 的 response 會帶 `Deprecation`、`Sunset` 與 `Link` headers。Legacy token 僅限明確 allowlist 的非 critical operations，永遠不能取得 step-up 或 credential-management 權限。

操作命令：

```bash
php artisan wncms:auth:legacy-status --json
php artisan wncms:auth:legacy-cutoff 2026-11-12T00:00:00Z --force
php artisan wncms:auth:legacy-revoke-all --force
```

將工作負載移至具 scope 的[service tokens](./service-tokens.md)，確認 capability metadata、撤銷 legacy rows，最後停用 adapter。穩定錯誤包括 `authentication.legacy_disabled`、`authentication.legacy_expired` 與 `authentication.invalid_token`。
