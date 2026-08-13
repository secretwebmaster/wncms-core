# Task 2 Report: Credential Primitives And Authentication Context

## Scope

Implemented only Task 2 credential primitives and their two specified unit-test
files. The protected v7 updater file was not read, modified, executed, staged,
or committed.

## TDD Evidence

### RED

Command:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php
```

Output summary:

```text
EEEEEEEE                                                            8 / 8 (100%)

There were 8 errors:
Error: Class "Wncms\\Auth\\Api\\V2\\CredentialParser" not found
Error: Class "Wncms\\Auth\\Api\\V2\\TokenHasher" not found

ERRORS!
Tests: 8, Assertions: 0, Errors: 8.
```

The failure was expected: the parser and hasher classes did not exist before
the production implementation was added.

### GREEN (Initial)

Command:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php
```

Output:

```text
........                                                            8 / 8 (100%)

OK (8 tests, 53 assertions)
```

### RED (Legacy Boundary Refinement)

Command:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php
```

Output summary:

```text
..F.....                                                            8 / 8 (100%)

There was 1 failure:
Failed asserting that false is true.

FAILURES!
Tests: 8, Assertions: 54, Failures: 1.
```

The focused test added the unprefixed bearer form containing a pipe. It exposed
an overly restrictive legacy-candidate branch, which was corrected without
changing new-prefix isolation.

### GREEN (Final)

Command:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php
```

Output:

```text
........                                                            8 / 8 (100%)

OK (8 tests, 55 assertions)
```

## Changes

- Added immutable `ApiCredential`, including safe array, JSON, and string
  representations that omit plaintext.
- Added strict `CredentialParser` classification. Every `wncms_at_`,
  `wncms_rt_`, and `wncms_st_` value retains its new credential type and is
  never legacy-eligible; only strict `id|secret` and unprefixed bearer values
  are legacy candidates.
- Added `TokenHasher` with a ULID-grade opaque public ID, `random_bytes(32)`,
  unpadded URL-safe Base64 secrets, SHA-256 storage hashes, and
  `hash_equals` comparison.
- Added immutable `AuthenticationContext` getters for actor, credential/session
  identifiers, ability ceiling, and stable website scope.
- Added the requested parser/isolation, redaction, context, hash-only, secret
  format, uniqueness, comparison, and non-disclosing exception tests.

## Final Verification

Command:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php && php -l src/Auth/Api/V2/ApiCredential.php && php -l src/Auth/Api/V2/CredentialParser.php && php -l src/Auth/Api/V2/TokenHasher.php && php -l src/Auth/Api/V2/AuthenticationContext.php && php -l tests/Unit/Api/V2/CredentialParserTest.php && php -l tests/Unit/Api/V2/TokenHasherTest.php
```

Output summary:

```text
OK (8 tests, 55 assertions)
No syntax errors detected in src/Auth/Api/V2/ApiCredential.php
No syntax errors detected in src/Auth/Api/V2/CredentialParser.php
No syntax errors detected in src/Auth/Api/V2/TokenHasher.php
No syntax errors detected in src/Auth/Api/V2/AuthenticationContext.php
No syntax errors detected in tests/Unit/Api/V2/CredentialParserTest.php
No syntax errors detected in tests/Unit/Api/V2/TokenHasherTest.php
```

## Self-Review

- Verified all three supported new prefixes remain isolated on both complete and
  unsuccessful-token paths.
- Verified serialized credential forms and unsupported-prefix exceptions do not
  disclose plaintext caller material.
- Verified the 32-byte secret encoding is URL-safe and unpadded, and that the
  stored value is only the SHA-256 hash.
- Verified context abilities and website IDs cannot be changed through the
  source arrays passed to its readonly constructor.
- Skipped reviewer-subagent dispatch because the task explicitly prohibits
  dispatching any subagent.
