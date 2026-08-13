# Task 3 Report — WNCMS-Owned Credential Schema And Models

## Scope

Implemented the five WNCMS-owned authentication/security tables, their shared
schema builder, Eloquent models, and `User` credential relations. The
host-owned `personal_access_tokens` schema was not changed.

## TDD Evidence

### RED

Added `tests/Feature/Api/V2/AuthSecuritySchemaTest.php`, then ran:

```text
vendor/bin/phpunit tests/Feature/Api/V2/AuthSecuritySchemaTest.php tests/Feature/PersonalAccessTokensMigrationTest.php
```

The new schema suite failed as intended: `api_sessions` was missing, its unique
index assertion failed, and the foreign-key fixture could not insert into the
absent table. The existing personal-access-token rollback regression remained
passing.

### GREEN

Implemented `ApiAuthSchema`, complete final base create migrations, models with
hidden secret hashes/casts/scopes, and model-key-resolved `User` relations.
Regenerated the prepared SQLite database with:

```text
composer run test:prepare-db
```

Fresh migrations created all five owned tables. The schema suite's individual
cases passed in a single PHPUnit process:

```text
test_fresh_schema_contains_owned_auth_tables_without_altering_pat: 7 assertions
test_owned_credential_schema_has_unique_secret_material_and_lifecycle_indexes: 28 assertions
test_owned_credential_foreign_keys_cascade_for_users_and_sessions: 3 assertions
test_owned_auth_migration_rollbacks_drop_only_wncms_tables: 7 assertions
```

The combined focused command was also rerun after the database preparation.
An earlier overlapping, residual PHPUnit process temporarily locked the shared
SQLite file; it was identified with `ps`, terminated, and no second test runner
was started while another runner existed. This was an environment contention,
not a schema compatibility failure.

## Verification

- `composer run test:prepare-db` completed and applied migrations 41–45.
- `php -l` passed for all Task 3 PHP files and the existing PAT migration test.
- `git diff --check` passed.
- Focused schema/PAT tests were run against the regenerated database.

## Self-review

- All five tables are WNCMS-owned, created only from complete base migrations.
- `ApiAuthSchema` is the single definition used by every fresh migration and is
  ready for the separately authorized existing-install updater.
- Public IDs and credential hashes are unique; lifecycle, owner, session, and
  refresh-family lookup fields are indexed.
- Access/service abilities and website scopes use JSON columns.
- Access and refresh tokens cascade with sessions; sessions and service tokens
  cascade with their owning user. Security events deliberately retain actor and
  target identifiers without deletion-coupled foreign keys.
- `down()` methods only drop their own WNCMS tables; the PAT table is preserved.
- No access, execution, modification, staging, or commit was made to
  `updates/update_core_7.0.0.php`.

## Documentation defer

AGENTS.md normally requires model documentation synchronization. The v7 plan
assigns the public authentication manual as a centralized Task 15 deliverable,
so no public manual pages were changed in Task 3 to avoid conflicting or
premature documentation scope. The existing model documentation was read before
implementation as required.

## Fix Round 1/5 — Schema Ownership Review

### RED

Added focused regression coverage for permanent refresh tokens, malformed
same-name owned tables, `csrf_hash` uniqueness, JSON column declarations, and a
direct-user-delete cascade fixture. Before the fix, the permanent refresh
fixture could not insert `expires_at = null`; `ApiRefreshToken::active()` also
excluded such a token. The compatibility fixtures exposed that the previous
preflight accepted complete-looking tables with a missing unique index or a
missing cascading foreign key.

### GREEN

- `api_refresh_tokens.expires_at` is now nullable and indexed; the active scope
  accepts a null expiry or a future expiry while still excluding consumed and
  revoked records.
- `ApiAuthSchema::assertCompatibleExistingTables()` now uses Laravel Schema
  Builder metadata (`getColumns`, `getIndexes`, `getForeignKeys`) rather than
  raw SQL. It validates all canonical fields' portable type families and
  nullability, every declared unique/composite index, and every required foreign
  key target with `ON DELETE CASCADE`.
- Every `createApi*()` method now rejects an incompatible same-name owned table
  instead of silently returning.
- The direct owner deletion fixture independently proves user-to-session,
  access-token, refresh-token, and service-token cascades. JSON checks are
  explicitly driver-aware: SQLite uses Laravel's native `TEXT` storage while
  MySQL/MariaDB use `JSON`, PostgreSQL allows `json/jsonb`, and SQL Server uses
  `nvarchar`.

### Verification

`composer run test:prepare-db` completed with migrations 41–45, and SQLite
metadata confirmed `api_refresh_tokens.expires_at` has `notnull=0`. The focused
schema/PAT PHPUnit command was run as one process after confirming no other
PHPUnit/testbench/composer process was active. `php -l` passed for the modified
schema class, refresh model, and test; `git diff --check` passed.

## Fix Round 2/5 — Primary-Key And Scope Re-review

### RED

Added a consumed-token assertion to the permanent refresh fixture, a same-name
`api_sessions` fixture that is complete except for `$table->id()`, and a unique
helper fixture containing only the composite unique index
`(csrf_hash, user_id)`. Before this round, the scope test did not cover a
consumed permanent token, canonical definitions omitted `id`, and the helper
would accept a composite unique index for a single-column requirement.

### GREEN

- The permanent refresh fixture now marks the token consumed, proves it is
  inactive, resets consumption, and proves revocation still excludes it.
- All five canonical definitions include `id` as a non-null integer-family
  field and a primary-key/autoincrement contract. The metadata preflight now
  rejects a missing or non-autoincrement primary key before a same-name table
  can be accepted by `createApi*()`.
- Self-review compared every `Blueprint` create column with its corresponding
  canonical definition; each is represented, including `id`, timestamps, JSON
  fields, and lifecycle fields.
- `assertUniqueIndex()` now accepts only an exact one-column unique index.

### Verification

Ran `composer run test:prepare-db`, then ran the three new focused regression
cases as one PHPUnit process after confirming no competing PHPUnit/testbench/
composer process. `php -l` and `git diff --check` were run before commit.
