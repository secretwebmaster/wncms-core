# Task 4 Security Events Report

## TDD evidence

- RED: `vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventServiceTest.php tests/Feature/Api/V2/SecuritySecretCanaryTest.php` initially failed because `Wncms\\Services\\Security\\SecurityEventService` did not exist.
- GREEN: after implementation, the required focused command completed with `OK (5 tests, 23 assertions)`:

  ```text
  vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventServiceTest.php tests/Feature/Api/V2/SecuritySecretCanaryTest.php tests/Feature/MutationAuditServiceTest.php
  ```

  The command takes about 65 seconds in this SQLite Testbench environment. PHPUnit emits progress lazily, so an intermediate single dot is not a hang. A `strace` capture confirmed the one process completed, closed the SQLite descriptor, and printed the final result at 2026-08-13 16:40:50.

## Verification

- `php -l` passed for every new/changed Task 4 PHP file.
- `git diff --check` passed.
- No updater file was read, modified, staged, or committed.

## Design review

- `ApiSecurityEventRecorded` is a redacted Laravel observability event, not a WNCMS extension hook dispatch point. It does not introduce or rename a core hook, so the hook skill does not apply.
- `withinTransaction()` fails closed before a credential/security mutation if configured versioned HMAC keys are unavailable, and rolls back the mutation if mandatory event validation/persistence fails.
- Event fields and nested context are allowlist-first; the shared recursive redactor excludes password, token, secret, proof, confirmation, authorization, cookie, CSRF, and API-key data.
- The canary test checks persisted security/mutation rows, emitted Laravel event payload, log records, and an API-shaped response. No Task 5 idempotency or queue storage exists yet, so this task does not manufacture unimplemented surfaces to test.
- Security-event writes are append-only at the service layer. `SecurityEventRetentionService::prune()` is the only Task 4 deletion API and caps batches at 500.
