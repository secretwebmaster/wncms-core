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
