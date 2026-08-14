# Legacy authentication migration

Legacy personal access tokens are a bounded migration adapter, disabled by default through `api_legacy_personal_tokens_enabled`. Set an explicit UTC `api_legacy_personal_tokens_cutoff_at`; use a migration window no longer than 90 days.

Accepted legacy responses carry `Deprecation`, `Sunset`, and `Link` headers. Legacy tokens are restricted to explicitly allowlisted, non-critical operations and never receive step-up or credential-management authority.

Operational commands:

```bash
php artisan wncms:auth:legacy-status --json
php artisan wncms:auth:legacy-cutoff 2026-11-12T00:00:00Z --force
php artisan wncms:auth:legacy-revoke-all --force
```

Move workloads to scoped [service tokens](./service-tokens.md), verify capability metadata, revoke legacy rows, then disable the adapter. Stable failures include `authentication.legacy_disabled`, `authentication.legacy_expired`, and `authentication.invalid_token`.
