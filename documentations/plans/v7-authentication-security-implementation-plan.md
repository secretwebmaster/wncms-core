# WNCMS v7 Authentication And Security Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the WNCMS-owned v7 interactive authentication, rotating refresh, scoped service-token, risk-policy, API-only Blade, legacy compatibility, security-event, OpenAPI, testing, and documentation foundation.

**Architecture:** Add focused credential repositories and services behind a single API v2 authentication context, then compose thin controllers and ordered middleware around them. Persist new credentials and mandatory security events only in WNCMS-owned tables; treat host `personal_access_tokens` and v1 `users.api_token` as isolated legacy adapters. Extend the existing Contract Kernel as the sole route/schema/capability/OpenAPI source.

**Tech Stack:** PHP 8.4, Laravel 13, Eloquent, Spatie Permission 6, PHPUnit 12/Testbench 11, OpenAPI 3.1, JSON Schema 2020-12.

## Global Constraints

- Read and follow repository `AGENTS.md` before every execution session.
- Apply repository skills for API creation/testing, settings, migrations, coding style, PHPDoc, documentation sync, TDD, review, and verification.
- Use strict TDD: observe every new test fail for the intended missing behavior before adding implementation.
- New models live in `src/Models`, extend `Wncms\Models\BaseModel`, and define the exact singular snake-case `$modelKey`.
- WNCMS owns `api_sessions`, `api_access_tokens`, `api_refresh_tokens`, `api_service_tokens`, and `api_security_events`.
- Never alter the host-owned `personal_access_tokens` schema.
- Never read, edit, stage, execute, delete, or commit `updates/update_core_7.0.0.php` without fresh explicit user authorization naming that file.
- Never push, release, tag, deploy Farm/Docker/live sites, or implement Next.js without explicit authorization.
- Preserve unrelated changes; stage only files named by the current task.
- API namespace remains `/api/v2`; OpenAPI remains 3.1.0; contract schema version becomes `2.1.0`.
- JSON refresh is the default; access lifetime is 15 minutes; refresh lifetime is 30 days.
- New credentials are hash-only and never default to permanent unrestricted `abilities: ["*"]`.
- Website scope binds to stable website IDs/keys, never domain names.
- Public manual structure/snippets remain synchronized in English, zh-CN, and zh-TW; UI translations remain synchronized in `en`, `zh_CN`, `zh_TW`, and `ja`.
- Runtime Artisan/API checks run from `/www/wwwroot/package.wncms.cc`, not the package-only directory.

## File And Responsibility Map

### Credential core

- `src/Auth/Api/V2/ApiCredential.php`: immutable parsed credential value.
- `src/Auth/Api/V2/CredentialParser.php`: strict prefix/type parser.
- `src/Auth/Api/V2/TokenHasher.php`: secret generation, SHA-256 hashing, constant-time comparison.
- `src/Auth/Api/V2/AuthenticationContext.php`: request-scoped actor, credential type/public ID, session, abilities, and website IDs.
- `src/Auth/Api/V2/IssuedRefreshToken.php`: immutable issued refresh plaintext/expiry/model result used only at the response boundary.
- `src/Auth/Api/V2/RotatedCredentialPair.php`: immutable access/refresh rotation result.
- `src/Auth/Api/V2/AccessTokenService.php`: issue and validate short-lived access tokens.
- `src/Auth/Api/V2/RefreshTokenService.php`: issue, rotate, and detect family replay.
- `src/Auth/Api/V2/SessionService.php`: interactive session list/revoke/logout-all.
- `src/Auth/Api/V2/ServiceTokenService.php`: options, create, rotate, revoke, grant ceilings.
- `src/Auth/Api/V2/LegacyPersonalTokenAuthenticator.php`: read-only compatibility adapter for host PAT records.
- `src/Auth/Api/V2/WebsiteScopeGuard.php`: stable website selection and actor/token intersection.

### Risk and browser security

- `src/Auth/Api/V2/OriginPolicy.php`: exact Origin/Referer and Cookie configuration validation.
- `src/Auth/Api/V2/CsrfTokenService.php`: session-bound double-submit values.
- `src/Auth/Api/V2/StepUpService.php`: purpose/session-bound recent-auth proofs.
- `src/Api/V2/Risk/RiskPolicy.php`: security-risk escalation and direct/planned decision.
- `src/Api/V2/Risk/ActionPlanService.php`: create/validate/consume single-use confirmations.

### Audit and Blade policy

- `src/Services/Security/SecurityEventService.php`: mandatory allowlisted/redacted event persistence.
- `src/Services/Security/SecurityCorrelationHasher.php`: versioned HMAC correlations.
- `src/Services/Security/SecurityEventRetentionService.php`: batched retention cleanup.
- `src/Services/Security/BladeAvailabilityService.php`: strict authoritative setting read/write.
- `src/Services/Security/BladeAvailabilityState.php`: immutable found/missing/invalid/unavailable and effective-state result.
- `src/Http/Middleware/EnsureWncmsBladeEnabled.php`: early uniform 404 gate.

### HTTP and contract

- Split `src/Http/Controllers/Api/V2/Backend/AuthController.php` into thin auth endpoints plus focused session/service-token/profile controllers under the same namespace.
- `routes/api/v2/backend.php`: register formal authentication/security operations and exact middleware.
- `src/Api/V2/Providers/CoreAuthSecurityContractProvider.php`: register all auth/security schemas and operations.
- Extend `ApiOperationContract`, validator, resolver, and OpenAPI builder with security metadata.

### Persistence

- `src/Database/Schema/ApiAuthSchema.php`: shared, exact table definitions used by fresh base migrations and the separately authorized updater.
- Create five base migrations and five models; tests create records through explicit helpers so secret defaults remain visible in test code.
- Do not modify the personal-access-token base migration.
- Existing-install updater work is a separately authorized checkpoint in Task 18.

---

### Task 1: Stable Settings, Permissions, And Configuration Validation

**Files:**
- Modify: `config/wncms-api-v2.php`
- Modify: `config/wncms-system-settings.php`
- Modify: `database/seeders/RolesSeeder.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Create: `src/Auth/Api/V2/AuthSecurityConfig.php`
- Test: `tests/Feature/Api/V2/AuthSecuritySettingsTest.php`
- Test: `tests/Unit/Api/V2/AuthSecurityConfigTest.php`

**Interfaces:**
- Produces: `AuthSecurityConfig::fromRuntime(): self`, typed getters for every stable setting, and `AuthSecurityConfig::validate(): array` keyed by setting name with a stable validation message.
- Produces exact permissions: `api_token_create`, `api_token_create_cross_site`, `api_token_create_permanent`, `api_token_index`, `api_token_show`, `api_token_rotate`, `api_token_revoke`, `security_event_index`, `security_event_show`, `blade_mode_manage`.

- [ ] **Step 1: Write failing configuration and permission tests**

```php
public function test_auth_security_defaults_are_stable(): void
{
    $config = AuthSecurityConfig::fromRuntime();
    $this->assertSame('json', $config->refreshTransport());
    $this->assertSame(15, $config->accessLifetimeMinutes());
    $this->assertSame(30, $config->refreshLifetimeDays());
    $this->assertSame('direct', $config->highRiskMode());
    $this->assertSame(300, $config->stepUpLifetimeSeconds());
}

public function test_roles_seeder_registers_exact_security_permissions(): void
{
    $expected = ['api_token_create', 'api_token_create_cross_site', 'api_token_create_permanent', 'api_token_index', 'api_token_show', 'api_token_rotate', 'api_token_revoke', 'security_event_index', 'security_event_show', 'blade_mode_manage'];
    $this->assertEmpty(array_diff($expected, (new RolesSeeder())->special_permissions()));
}
```

- [ ] **Step 2: Run tests and confirm intended failure**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AuthSecuritySettingsTest.php tests/Unit/Api/V2/AuthSecurityConfigTest.php`

Expected: FAIL because `AuthSecurityConfig` and stable settings/permissions do not exist.

- [ ] **Step 3: Implement validated settings and boot mapping**

```php
final class AuthSecurityConfig
{
    public static function fromRuntime(): self;
    public function validate(): array;
    public function refreshTransport(): string;
    public function accessLifetimeMinutes(): int;
    public function refreshLifetimeDays(): int;
    public function actionPlanLifetimeSeconds(): int;
    public function stepUpLifetimeSeconds(): int;
}
```

Register every key/default/range from the approved design. Reject Cookie mode without exact Origins and reject `SameSite=None` without HTTPS-compatible settings. Map validated values under `wncms.auth_security.*` during provider boot without calling `gss()` from `SettingManager::getList()`.

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AuthSecuritySettingsTest.php tests/Unit/Api/V2/AuthSecurityConfigTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add config/wncms-api-v2.php config/wncms-system-settings.php database/seeders/RolesSeeder.php src/Providers/WncmsServiceProvider.php src/Auth/Api/V2/AuthSecurityConfig.php tests/Feature/Api/V2/AuthSecuritySettingsTest.php tests/Unit/Api/V2/AuthSecurityConfigTest.php
git commit -m "feat(auth): register security settings and permissions"
```

### Task 2: Credential Primitives And Authentication Context

**Files:**
- Create: `src/Auth/Api/V2/ApiCredential.php`
- Create: `src/Auth/Api/V2/CredentialParser.php`
- Create: `src/Auth/Api/V2/TokenHasher.php`
- Create: `src/Auth/Api/V2/AuthenticationContext.php`
- Test: `tests/Unit/Api/V2/CredentialParserTest.php`
- Test: `tests/Unit/Api/V2/TokenHasherTest.php`

**Interfaces:**
- Produces: `CredentialParser::parse(string $token): ApiCredential` with type constants `interactive_access`, `refresh`, `service_token`, `legacy_personal_access_token`.
- Produces: `TokenHasher::issue(string $prefix): array{plain_text:string,public_id:string,hash:string}` and `matches(string $plainText, string $hash): bool`.
- Produces: immutable `AuthenticationContext` getters used by middleware, capabilities, idempotency, and audit.

- [ ] **Step 1: Write failing parser/isolation/hash tests**

```php
public function test_failed_new_prefix_never_falls_back_to_legacy(): void
{
    $credential = $this->parser->parse('wncms_st_public.invalid');
    $this->assertSame('service_token', $credential->type());
    $this->assertFalse($credential->isLegacyCandidate());
}

public function test_issued_secret_is_hash_only_storage_material(): void
{
    $issued = $this->hasher->issue('wncms_at');
    $this->assertStringStartsWith('wncms_at_', $issued['plain_text']);
    $this->assertSame(hash('sha256', $issued['plain_text']), $issued['hash']);
    $this->assertNotSame($issued['plain_text'], $issued['hash']);
}
```

- [ ] **Step 2: Run and observe missing-class failure**

Run: `vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php`

Expected: FAIL because credential primitives are absent.

- [ ] **Step 3: Implement strict primitives**

Use `random_bytes(32)`, URL-safe Base64 without padding, UUID/ULID-grade opaque public IDs, SHA-256 storage hashes, and `hash_equals`. Accept `id|secret` or unprefixed bearer strings only as legacy candidates. Never include plaintext in `toArray()`, exceptions, or string conversion.

```php
final readonly class ApiCredential
{
    public function __construct(private string $type, private ?string $publicId, private string $plainText, private bool $legacyCandidate = false) {}
    public function type(): string;
    public function publicId(): ?string;
    public function plainText(): string;
    public function isLegacyCandidate(): bool;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2 tests/Unit/Api/V2/CredentialParserTest.php tests/Unit/Api/V2/TokenHasherTest.php
git commit -m "feat(auth): add credential primitives"
```

### Task 3: WNCMS-Owned Credential Schema And Models

**Files:**
- Create: `database/migrations/0001_01_01_000041_create_api_sessions_table.php`
- Create: `database/migrations/0001_01_01_000042_create_api_access_tokens_table.php`
- Create: `database/migrations/0001_01_01_000043_create_api_refresh_tokens_table.php`
- Create: `database/migrations/0001_01_01_000044_create_api_service_tokens_table.php`
- Create: `database/migrations/0001_01_01_000045_create_api_security_events_table.php`
- Create: `src/Database/Schema/ApiAuthSchema.php`
- Create: `src/Models/ApiSession.php`
- Create: `src/Models/ApiAccessToken.php`
- Create: `src/Models/ApiRefreshToken.php`
- Create: `src/Models/ApiServiceToken.php`
- Create: `src/Models/ApiSecurityEvent.php`
- Modify: `src/Models/User.php`
- Test: `tests/Feature/Api/V2/AuthSecuritySchemaTest.php`
- Test: `tests/Feature/PersonalAccessTokensMigrationTest.php`

**Interfaces:**
- Produces relations `User::apiSessions()`, `apiAccessTokens()`, `apiRefreshTokens()`, `apiServiceTokens()` through `wncms()->getModelClass()` where model-key resolution applies.
- Models expose casts/scopes only; secret hashes remain hidden.
- Produces `ApiAuthSchema::createApiSessions()`, `createApiAccessTokens()`, `createApiRefreshTokens()`, `createApiServiceTokens()`, `createApiSecurityEvents()`, and `assertCompatibleExistingTables()` so fresh and upgraded schema cannot drift.

- [ ] **Step 1: Write failing schema ownership tests**

```php
public function test_fresh_schema_contains_owned_auth_tables_without_altering_pat(): void
{
    foreach (['api_sessions', 'api_access_tokens', 'api_refresh_tokens', 'api_service_tokens', 'api_security_events'] as $table) {
        $this->assertTrue(Schema::hasTable($table), $table);
    }
    $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'website_ids'));
    $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'family_id'));
}
```

Assert unique public IDs/hashes, indexed expiry/revocation/owner/family fields, JSON ability/website fields, and foreign-key delete behavior. Assert down() drops only WNCMS-owned tables.

- [ ] **Step 2: Run and confirm missing-table failure**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AuthSecuritySchemaTest.php tests/Feature/PersonalAccessTokensMigrationTest.php`

Expected: FAIL because five owned tables are absent while PAT preservation continues passing.

- [ ] **Step 3: Add complete base migrations and models**

Each model extends `BaseModel` and defines:

```php
public static $modelKey = 'api_session'; // corresponding exact key per model
protected $hidden = ['token_hash', 'csrf_hash'];
protected $casts = ['abilities' => 'array', 'website_ids' => 'array', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
```

Use complete final create migrations, not additive migrations. Keep security events append-only at the service/API layer.

```php
final class ApiAuthSchema
{
    public static function createApiSessions(): void;
    public static function createApiAccessTokens(): void;
    public static function createApiRefreshTokens(): void;
    public static function createApiServiceTokens(): void;
    public static function createApiSecurityEvents(): void;
    public static function assertCompatibleExistingTables(): void;
}
```

- [ ] **Step 4: Prepare test DB and rerun schema tests**

Run: `composer run test:prepare-db && vendor/bin/phpunit tests/Feature/Api/V2/AuthSecuritySchemaTest.php tests/Feature/PersonalAccessTokensMigrationTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/0001_01_01_00004*_create_api_* src/Database/Schema/ApiAuthSchema.php src/Models/Api*.php src/Models/User.php tests/Feature/Api/V2/AuthSecuritySchemaTest.php tests/Feature/PersonalAccessTokensMigrationTest.php database/testing.sqlite database/testing.schema.sql
git commit -m "feat(auth): add owned credential schema"
```

### Task 4: Mandatory Security Events And Secret Redaction

**Files:**
- Create: `src/Services/Security/SecurityCorrelationHasher.php`
- Create: `src/Services/Security/SecurityEventService.php`
- Create: `src/Services/Security/SecurityEventRetentionService.php`
- Create: `src/Events/ApiSecurityEventRecorded.php`
- Modify: `src/Services/Automation/MutationAuditService.php`
- Test: `tests/Feature/Api/V2/SecurityEventServiceTest.php`
- Test: `tests/Feature/Api/V2/SecuritySecretCanaryTest.php`

**Interfaces:**
- Produces: `SecurityEventService::record(string $type, string $severity, string $outcome, array $context = []): ApiSecurityEvent`.
- Produces: `SecurityEventService::withinTransaction(callable $mutation, array $event): mixed`.
- Produces HMAC methods for IP, login identifier, and User-Agent using versioned configured keys.

- [ ] **Step 1: Write failing mandatory event and recursive canary tests**

```php
public function test_event_builder_discards_unknown_and_redacts_sensitive_fields(): void
{
    $event = $this->service->record('auth.login.failed', 'warning', 'denied', [
        'request_id' => (string) Str::uuid(),
        'password' => 'CANARY-PASSWORD',
        'nested' => ['confirmation_token' => 'CANARY-CONFIRMATION'],
        'unexpected' => 'not-allowlisted',
    ]);
    $serialized = json_encode($event->toArray());
    $this->assertStringNotContainsString('CANARY-', $serialized);
    $this->assertArrayNotHasKey('unexpected', $event->context ?? []);
}
```

Scan security/mutation rows, logs, response content, idempotency records, and queued payloads for all approved canaries.

- [ ] **Step 2: Run and verify failure**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventServiceTest.php tests/Feature/Api/V2/SecuritySecretCanaryTest.php`

Expected: FAIL because the event service and expanded redaction do not exist.

- [ ] **Step 3: Implement allowlist-first events and shared redaction**

Reject missing correlation-key configuration for credential issuance/security mutations. Record stable catalog values, dispatch a redacted Laravel event, and support aggregate counters. Permit deletion only through `SecurityEventRetentionService::prune(CarbonImmutable $cutoff, int $batchSize = 500): int`.

```php
final class SecurityEventService
{
    public function record(string $type, string $severity, string $outcome, array $context = []): ApiSecurityEvent;
    public function withinTransaction(callable $mutation, array $event): mixed;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventServiceTest.php tests/Feature/Api/V2/SecuritySecretCanaryTest.php tests/Feature/MutationAuditServiceTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Security src/Events/ApiSecurityEventRecorded.php src/Services/Automation/MutationAuditService.php tests/Feature/Api/V2/SecurityEventServiceTest.php tests/Feature/Api/V2/SecuritySecretCanaryTest.php
git commit -m "feat(auth): add mandatory security events"
```

### Task 5: Access Tokens, Website Scope, And Ordered Authentication Middleware

**Files:**
- Create: `src/Auth/Api/V2/AccessTokenService.php`
- Create: `src/Auth/Api/V2/WebsiteScopeGuard.php`
- Replace: `src/Http/Middleware/ApiV2TokenAuth.php`
- Create: `src/Http/Middleware/RequireApiV2Ability.php`
- Create: `src/Http/Middleware/RequireApiV2Permission.php`
- Create: `src/Http/Middleware/ResolveApiV2WebsiteScope.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `src/Api/V2/IdempotencyService.php`
- Test: `tests/Feature/Api/V2/AccessTokenAuthenticationTest.php`
- Test: `tests/Feature/Api/V2/ApiGuardOrderTest.php`

**Interfaces:**
- Produces `AccessTokenService::issue(User $user, ApiSession $session, array $abilities, array $websiteIds): array{token:string,expires_at:CarbonImmutable,model:ApiAccessToken}`.
- Produces `AccessTokenService::authenticate(ApiCredential $credential): AuthenticationContext`.
- Middleware stores `AuthenticationContext` on request attribute `wncms_api_v2_auth_context`.

- [ ] **Step 1: Write failing validity/scope/order tests**

```php
public function test_ability_denial_prevents_permission_scope_validation_and_domain_execution(): void
{
    $response = $this->withToken($this->tokenWithout('links.read'))->getJson('/api/v2/backend/links?website_id=1');
    $response->assertForbidden()->assertJsonPath('meta.error_code', 'authorization.ability_denied');
    $this->assertSame(['credential', 'ability'], GuardProbe::calls());
}
```

Test expiry, revocation, disabled user/session, ability+permission intersection, explicit stable website ID/key, domain-change stability, request-body `api_token` rejection for new v2 auth, and request ID preservation.

- [ ] **Step 2: Run and observe failure against legacy middleware**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AccessTokenAuthenticationTest.php tests/Feature/Api/V2/ApiGuardOrderTest.php`

Expected: FAIL because current middleware reads PAT and skips expiry/ability/scope.

- [ ] **Step 3: Implement context-based guard chain**

Authenticate new access/service/explicit legacy types, fail without type fallback, set Laravel actor and immutable context, enforce ability then permission then website scope. Update idempotency actor identity to credential public ID plus stable website identity; never fingerprint secrets.

```php
$request->attributes->set('wncms_api_v2_auth_context', $context);
auth()->setUser($context->user());

return $next($request); // only after credential validity; later middleware handles ability, permission, scope
```

- [ ] **Step 4: Run focused middleware/idempotency suites**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AccessTokenAuthenticationTest.php tests/Feature/Api/V2/ApiGuardOrderTest.php tests/Feature/Api/V2/IdempotencyMiddlewareTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/AccessTokenService.php src/Auth/Api/V2/WebsiteScopeGuard.php src/Http/Middleware/ApiV2TokenAuth.php src/Http/Middleware/RequireApiV2Ability.php src/Http/Middleware/RequireApiV2Permission.php src/Http/Middleware/ResolveApiV2WebsiteScope.php src/Providers/WncmsServiceProvider.php src/Api/V2/IdempotencyService.php tests/Feature/Api/V2/AccessTokenAuthenticationTest.php tests/Feature/Api/V2/ApiGuardOrderTest.php tests/Feature/Api/V2/IdempotencyMiddlewareTest.php
git commit -m "feat(auth): enforce access token guards"
```

### Task 6: Login, JSON Refresh Rotation, And Session Lifecycle

**Files:**
- Create: `src/Auth/Api/V2/RefreshTokenService.php`
- Create: `src/Auth/Api/V2/IssuedRefreshToken.php`
- Create: `src/Auth/Api/V2/RotatedCredentialPair.php`
- Create: `src/Auth/Api/V2/SessionService.php`
- Modify: `src/Http/Controllers/Api/V2/Backend/AuthController.php`
- Create: `src/Http/Controllers/Api/V2/Backend/SessionController.php`
- Modify: `routes/api/v2/backend.php`
- Modify: `src/Providers/RouteServiceProvider.php`
- Test: `tests/Feature/Api/V2/JsonAuthenticationFlowTest.php`
- Test: `tests/Feature/Api/V2/SessionLifecycleTest.php`
- Test: `tests/Feature/Api/V2/LoginThrottleTest.php`

**Interfaces:**
- Produces `RefreshTokenService::issue(ApiSession $session): IssuedRefreshToken`.
- Produces `RefreshTokenService::rotate(ApiCredential $credential): RotatedCredentialPair`; reuse throws a typed exception after revoking family.
- Produces `SessionService::revoke(ApiSession $session, string $reason): void` and `revokeAll(User $user, ?int $exceptSessionId = null): int`.

- [ ] **Step 1: Write failing JSON flow, race, session, and throttle tests**

```php
public function test_reusing_rotated_refresh_revokes_only_its_session_family(): void
{
    $first = $this->loginJson($this->user);
    $other = $this->loginJson($this->user);
    $this->refreshJson($first['refresh_token'])->assertOk();
    $this->refreshJson($first['refresh_token'])->assertUnauthorized()->assertJsonPath('meta.error_code', 'authentication.refresh_reuse_detected');
    $this->refreshJson($other['refresh_token'])->assertOk();
}
```

Use database barriers/transactions to prove two parallel rotations yield one success. Verify 15-minute/30-day defaults, permanent remember policy, logout, logout-all, individual revoke, generic invalid credentials, dummy-hash path, account+IP delay, and last-activity debounce.

- [ ] **Step 2: Run and confirm current login contract fails**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/JsonAuthenticationFlowTest.php tests/Feature/Api/V2/SessionLifecycleTest.php tests/Feature/Api/V2/LoginThrottleTest.php`

Expected: FAIL because current login issues an unlimited PAT and has no refresh/session lifecycle.

- [ ] **Step 3: Implement transactionally audited JSON/session flows**

Login validates the user with dummy-hash equivalence, applies both limiters, creates one session, and issues one access/refresh pair in a transaction with security event. Refresh atomically consumes the old row. Logout is idempotent; logout-all excludes service tokens. Sessions expose opaque IDs and no raw IP/token fragments.

```php
final class RefreshTokenService
{
    public function issue(ApiSession $session): IssuedRefreshToken;
    public function rotate(ApiCredential $credential): RotatedCredentialPair;
}

final class SessionService
{
    public function revoke(ApiSession $session, string $reason): void;
    public function revokeAll(User $user, ?int $exceptSessionId = null): int;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/JsonAuthenticationFlowTest.php tests/Feature/Api/V2/SessionLifecycleTest.php tests/Feature/Api/V2/LoginThrottleTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/RefreshTokenService.php src/Auth/Api/V2/IssuedRefreshToken.php src/Auth/Api/V2/RotatedCredentialPair.php src/Auth/Api/V2/SessionService.php src/Http/Controllers/Api/V2/Backend/AuthController.php src/Http/Controllers/Api/V2/Backend/SessionController.php routes/api/v2/backend.php src/Providers/RouteServiceProvider.php tests/Feature/Api/V2/JsonAuthenticationFlowTest.php tests/Feature/Api/V2/SessionLifecycleTest.php tests/Feature/Api/V2/LoginThrottleTest.php
git commit -m "feat(auth): add rotating interactive sessions"
```

### Task 7: Cookie Refresh, Exact Origin, And CSRF

**Files:**
- Create: `src/Auth/Api/V2/OriginPolicy.php`
- Create: `src/Auth/Api/V2/CsrfTokenService.php`
- Create: `src/Http/Middleware/EnforceApiV2RefreshTransport.php`
- Create: `src/Http/Middleware/ValidateApiV2RefreshOrigin.php`
- Create: `src/Http/Middleware/ValidateApiV2RefreshCsrf.php`
- Modify: `src/Http/Controllers/Api/V2/Backend/AuthController.php`
- Modify: `routes/api/v2/backend.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Test: `tests/Feature/Api/V2/CookieAuthenticationFlowTest.php`
- Test: `tests/Unit/Api/V2/OriginPolicyTest.php`

**Interfaces:**
- Produces `OriginPolicy::assertAllowed(Request $request): void` and `cookieOptions(): array`.
- Produces `CsrfTokenService::issue(ApiSession $session): string` and `assertValid(ApiSession $session, string $cookie, string $header): void`.

- [ ] **Step 1: Write failing Cookie/Origin/CSRF matrix**

```php
#[DataProvider('cookieMatrix')]
public function test_cookie_security_matrix(array $settings, array $headers, int $status, ?string $error): void
{
    $this->applySettings($settings);
    $response = $this->withHeaders($headers)->postJson('/api/v2/backend/auth/refresh');
    $response->assertStatus($status);
    if ($error !== null) $response->assertJsonPath('meta.error_code', $error);
}
```

Cover host-only/shared domain, Strict/Lax/None, scheme-host-port exact match, missing/null/denied Origin, explicit Referer fallback, valid/missing/mismatched CSRF, login CSRF, Cookie clearing, transport mismatch, and absence of refresh plaintext.

- [ ] **Step 2: Run and confirm missing Cookie behavior**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/CookieAuthenticationFlowTest.php tests/Unit/Api/V2/OriginPolicyTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement strict transport separation**

Use `__Secure-wncms_refresh` and `wncms_refresh_csrf`, path `/api/v2/backend/auth`, Secure always, HttpOnly only for refresh, Lax default, omitted Domain default. JSON mode sets/reads neither cookie. Cookie mode accepts no body refresh token and returns no refresh field.

```php
final class OriginPolicy
{
    public function assertAllowed(Request $request): void;
    public function cookieOptions(): array;
}

final class CsrfTokenService
{
    public function issue(ApiSession $session): string;
    public function assertValid(ApiSession $session, string $cookie, string $header): void;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/CookieAuthenticationFlowTest.php tests/Unit/Api/V2/OriginPolicyTest.php tests/Feature/Api/V2/JsonAuthenticationFlowTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/OriginPolicy.php src/Auth/Api/V2/CsrfTokenService.php src/Http/Middleware/EnforceApiV2RefreshTransport.php src/Http/Middleware/ValidateApiV2RefreshOrigin.php src/Http/Middleware/ValidateApiV2RefreshCsrf.php src/Http/Controllers/Api/V2/Backend/AuthController.php routes/api/v2/backend.php src/Providers/WncmsServiceProvider.php tests/Feature/Api/V2/CookieAuthenticationFlowTest.php tests/Unit/Api/V2/OriginPolicyTest.php
git commit -m "feat(auth): secure cookie refresh transport"
```

### Task 8: Step-Up Authentication And Risk Action Plans

**Files:**
- Create: `src/Auth/Api/V2/StepUpService.php`
- Create: `src/Api/V2/Risk/RiskPolicy.php`
- Create: `src/Api/V2/Risk/ActionPlanService.php`
- Create: `src/Http/Controllers/Api/V2/Backend/ActionPlanController.php`
- Create: `src/Http/Middleware/EnforceApiV2RiskPolicy.php`
- Modify: `routes/api/v2/backend.php`
- Test: `tests/Feature/Api/V2/StepUpAuthenticationTest.php`
- Test: `tests/Feature/Api/V2/ActionPlanPolicyTest.php`
- Test: `tests/Unit/Api/V2/RiskPolicyTest.php`

**Interfaces:**
- Produces `StepUpService::issue(ApiSession $session, array $purposes): string` and `consume(AuthenticationContext $context, string $proof, string $purpose): void`.
- Produces `RiskPolicy::effective(ApiOperationContract $operation, array $normalizedInput, array $environment): string`.
- Produces `ActionPlanService::create(AuthenticationContext $context, ApiOperationContract $operation, array $input, array $targetState): array` and `consume(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, array $input, array $targetState): void` with atomic single use.

- [ ] **Step 1: Write failing escalation, stale-plan, purpose, and race tests**

```php
public function test_permanent_cross_site_full_admin_escalates_to_critical(): void
{
    $this->assertSame('critical', $this->policy->effective($this->highOperation(), [
        'expiry' => 'permanent', 'website_ids' => [1, 2], 'template' => 'full_admin',
    ], []));
}
```

Test direct/planned modes, five-minute expiry, actor/session/input/target/scope/permission binding, stale state, single-use concurrency, 428/409 mappings, async enqueue consumption, and proof invalidation after password/session security events.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/StepUpAuthenticationTest.php tests/Feature/Api/V2/ActionPlanPolicyTest.php tests/Unit/Api/V2/RiskPolicyTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement hash-only short-lived proofs/plans**

Bind proofs to one interactive session and exact purpose. Bind plans to actor, credential, operation, normalized input, target fingerprint, website scope, permission/ability, effective risk, and expiry. Consume under transaction/lock and emit mandatory events.

```php
final class StepUpService
{
    public function issue(ApiSession $session, array $purposes): string;
    public function consume(AuthenticationContext $context, string $proof, string $purpose): void;
}

final class ActionPlanService
{
    public function create(AuthenticationContext $context, ApiOperationContract $operation, array $input, array $targetState): array;
    public function consume(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, array $input, array $targetState): void;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/StepUpAuthenticationTest.php tests/Feature/Api/V2/ActionPlanPolicyTest.php tests/Unit/Api/V2/RiskPolicyTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/StepUpService.php src/Api/V2/Risk src/Http/Controllers/Api/V2/Backend/ActionPlanController.php src/Http/Middleware/EnforceApiV2RiskPolicy.php routes/api/v2/backend.php tests/Feature/Api/V2/StepUpAuthenticationTest.php tests/Feature/Api/V2/ActionPlanPolicyTest.php tests/Unit/Api/V2/RiskPolicyTest.php
git commit -m "feat(auth): enforce step-up risk plans"
```

### Task 9: Scoped Service-Token Management

**Files:**
- Create: `src/Auth/Api/V2/AbilityTemplateRegistry.php`
- Create: `src/Auth/Api/V2/ServiceTokenService.php`
- Create: `src/Http/Controllers/Api/V2/Backend/ServiceTokenController.php`
- Create: `src/Http/Resources/Api/V2/ServiceTokenResource.php`
- Modify: `routes/api/v2/backend.php`
- Test: `tests/Feature/Api/V2/ServiceTokenManagementTest.php`
- Test: `tests/Unit/Api/V2/AbilityTemplateRegistryTest.php`

**Interfaces:**
- Produces `AbilityTemplateRegistry::optionsFor(User $actor): array` and `resolveGrant(User $actor, string $template, array $additions, array $removals): array`.
- Produces `ServiceTokenService::create(AuthenticationContext $context, array $input): array`, `rotate(AuthenticationContext $context, ApiServiceToken $token): array`, `revoke(AuthenticationContext $context, ApiServiceToken $token): void`, and actor-scoped list/show queries.

- [ ] **Step 1: Write failing template/grant/scope/plaintext tests**

```php
public function test_service_token_requires_explicit_website_and_cannot_exceed_actor(): void
{
    $this->actingAsApiSession($this->editor);
    $this->postJson('/api/v2/backend/auth/service-tokens', [
        'name' => 'deploy', 'template' => 'full_admin', 'website_ids' => [], 'expires_in_days' => 90,
    ])->assertUnprocessable();
}
```

Test all four templates, additions/removals, unknown abilities, cross-site/permanent permissions, expiry enumeration, step-up/risk/idempotency, plaintext-once encrypted replay window, atomic rotation, cross-user 404, service-token credential-management denial, last-used debounce, and password revocation.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/ServiceTokenManagementTest.php tests/Unit/Api/V2/AbilityTemplateRegistryTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement actor-bounded token management**

Build templates from formal registry abilities, exclude credential management from every service token, intersect grants with actor permission/delegable ability and website access, and store only hash. Resource output omits hashes/fragments and returns plaintext only in create/rotate result.

```php
final class ServiceTokenService
{
    public function optionsFor(User $actor): array;
    public function create(AuthenticationContext $context, array $input): array;
    public function rotate(AuthenticationContext $context, ApiServiceToken $token): array;
    public function revoke(AuthenticationContext $context, ApiServiceToken $token): void;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/ServiceTokenManagementTest.php tests/Unit/Api/V2/AbilityTemplateRegistryTest.php tests/Feature/Api/V2/AccessTokenAuthenticationTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/AbilityTemplateRegistry.php src/Auth/Api/V2/ServiceTokenService.php src/Http/Controllers/Api/V2/Backend/ServiceTokenController.php src/Http/Resources/Api/V2/ServiceTokenResource.php routes/api/v2/backend.php tests/Feature/Api/V2/ServiceTokenManagementTest.php tests/Unit/Api/V2/AbilityTemplateRegistryTest.php
git commit -m "feat(auth): add scoped service tokens"
```

### Task 10: Password, Email, Verification, And Full Credential Revocation

**Files:**
- Create: `src/Auth/Api/V2/UserSecurityService.php`
- Create: `src/Http/Controllers/Api/V2/Backend/ProfileSecurityController.php`
- Modify: `src/Models/User.php`
- Modify: `routes/api/v2/backend.php`
- Test: `tests/Feature/Api/V2/UserSecurityFlowTest.php`

**Interfaces:**
- Produces the exact `UserSecurityService` signatures shown in Step 3, including `revokeAllCredentials(User $user, string $reason): void`.

- [ ] **Step 1: Write failing forgot/reset/email/revocation tests**

```php
public function test_password_change_revokes_every_exactly_attributable_credential(): void
{
    $tokens = $this->issueEveryCredentialType($this->user);
    $this->changePassword($this->user, 'New-Strong-Password!')->assertOk()->assertJsonPath('data.reauthentication_required', true);
    foreach ($tokens as $token) $this->assertCredentialRejected($token);
}
```

Test generic forgot response, single-use expiry, client callback, old email retained until verification, old-address notification, exact PAT tokenable class/ID deletion, other users/types preserved, `users.api_token=null`, and rollback on unsafe PAT schema/audit failure.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/UserSecurityFlowTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement transactional self-service security flows**

Use Laravel password broker/verification primitives behind stable API envelopes. Require interactive access and purpose-bound step-up for changes. Revoke WNCMS rows and precisely matched legacy rows in the same transaction/event; never expose account existence.

```php
final class UserSecurityService
{
    public function changePassword(AuthenticationContext $context, string $currentPassword, string $newPassword): void;
    public function resetPassword(string $brokerToken, string $email, string $newPassword): void;
    public function requestEmailChange(AuthenticationContext $context, string $newEmail): void;
    public function confirmEmailChange(string $verificationToken): void;
    public function revokeAllCredentials(User $user, string $reason): void;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/UserSecurityFlowTest.php tests/Feature/Api/V2/SessionLifecycleTest.php tests/Feature/Api/V2/ServiceTokenManagementTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/UserSecurityService.php src/Http/Controllers/Api/V2/Backend/ProfileSecurityController.php src/Models/User.php routes/api/v2/backend.php tests/Feature/Api/V2/UserSecurityFlowTest.php
git commit -m "feat(auth): add user security lifecycle"
```

### Task 11: Legacy PAT Adapter, Cutoff, Headers, And CLI

**Files:**
- Create: `src/Auth/Api/V2/LegacyPersonalTokenAuthenticator.php`
- Create: `src/Auth/Api/V2/LegacyTokenPolicy.php`
- Create: `src/Http/Middleware/AttachApiV2LegacyDeprecationHeaders.php`
- Create: `src/Console/Commands/AuthLegacyStatus.php`
- Create: `src/Console/Commands/AuthLegacyCutoff.php`
- Create: `src/Console/Commands/AuthLegacyRevokeAll.php`
- Modify: `src/Http/Middleware/ApiV2TokenAuth.php`
- Test: `tests/Feature/Api/V2/LegacyPersonalTokenCompatibilityTest.php`
- Test: `tests/Feature/Api/V2/LegacyAuthCommandsTest.php`

**Interfaces:**
- Produces `LegacyTokenPolicy::allows(ApiOperationContract $operation, CarbonImmutable $now): bool`.
- Produces adapter schema introspection with required/optional columns and read-only authentication.

- [ ] **Step 1: Write failing host-schema fixture and cutoff tests**

```php
public function test_legacy_star_never_bypasses_permission_or_website_scope(): void
{
    $token = $this->legacyPat($this->member, ['*']);
    $this->withToken($token)->deleteJson('/api/v2/backend/links/1?website_id=2')
        ->assertForbidden();
}
```

Run against complete, missing-optional, missing-required, extra-column, absent-table fixtures. Test explicit operation opt-in, no critical/credential operations, one website, cutoff/default/max override, Deprecation/Sunset/Link headers, v1 token rejection by v2, no host schema mutation, and CLI JSON/idempotency.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/LegacyPersonalTokenCompatibilityTest.php tests/Feature/Api/V2/LegacyAuthCommandsTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement conservative adapter and commands**

Never write `last_used_at` unless the column exists. Never infer age without `created_at`. `legacy-revoke-all --force` changes only WNCMS acceptance settings. `legacy-cutoff` requires timezone-aware input; over 365 days requires `--override-max --force` and a security event.

```php
final class LegacyPersonalTokenAuthenticator
{
    public function authenticate(ApiCredential $credential, ApiOperationContract $operation): AuthenticationContext;
    public function schemaStatus(): array;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/LegacyPersonalTokenCompatibilityTest.php tests/Feature/Api/V2/LegacyAuthCommandsTest.php tests/Feature/Api/V2/UserSecurityFlowTest.php tests/Feature/ApiAuthSettingsTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/Api/V2/LegacyPersonalTokenAuthenticator.php src/Auth/Api/V2/LegacyTokenPolicy.php src/Http/Middleware/AttachApiV2LegacyDeprecationHeaders.php src/Console/Commands/AuthLegacyStatus.php src/Console/Commands/AuthLegacyCutoff.php src/Console/Commands/AuthLegacyRevokeAll.php src/Http/Middleware/ApiV2TokenAuth.php tests/Feature/Api/V2/LegacyPersonalTokenCompatibilityTest.php tests/Feature/Api/V2/LegacyAuthCommandsTest.php
git commit -m "feat(auth): add bounded legacy token compatibility"
```

### Task 12: Blade Global 404 Gate, Security API, And Recovery CLI

**Files:**
- Create: `src/Services/Security/BladeAvailabilityService.php`
- Create: `src/Services/Security/BladeAvailabilityState.php`
- Create: `src/Http/Middleware/EnsureWncmsBladeEnabled.php`
- Create: `src/Http/Controllers/Api/V2/Backend/BladeSecurityController.php`
- Create: `src/Console/Commands/BladeStatus.php`
- Create: `src/Console/Commands/BladeDisable.php`
- Create: `src/Console/Commands/BladeEnable.php`
- Modify: `routes/web.php`
- Modify: `src/Providers/PluginServiceProvider.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Test: `tests/Feature/BladeAvailabilityPolicyTest.php`
- Test: `tests/Feature/BladeAvailabilityCommandsTest.php`
- Test: `tests/Feature/Api/V2/BladeSecurityApiTest.php`

**Interfaces:**
- Produces `BladeAvailabilityService::state(): BladeAvailabilityState`, `enable(string $surface): BladeAvailabilityState`, and `disable(string $surface): BladeAvailabilityState`.
- Middleware returns plain 404 before session/auth/locale/website/controller hooks.

- [ ] **Step 1: Write failing route-inventory and recovery tests**

```php
public function test_disabled_mode_404s_every_wncms_html_surface_but_not_api_or_host_routes(): void
{
    uss('blade_enabled', 0);
    foreach ($this->wncmsHtmlInventory() as [$method, $uri]) $this->call($method, $uri)->assertNotFound();
    $this->getJson('/api/v2/openapi.json')->assertOk();
    $this->get('/host-owned-health')->assertOk();
}
```

Cover locales, fallback, custom frontend/backend, plugin Blade routes, every method, explicit non-HTML callbacks, installer state, route cache registration, request-local authoritative reads, invalid/unavailable fail-closed, status/disable force/enable, cache failure warning, and audit failure emergency enable.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/BladeAvailabilityPolicyTest.php tests/Feature/BladeAvailabilityCommandsTest.php tests/Feature/Api/V2/BladeSecurityApiTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement central middleware and authoritative service**

Differentiate found/missing/invalid/unavailable. Missing means enabled; invalid/unavailable on installed systems means disabled. Apply the middleware to WNCMS UI extension groups, not host routes or API/callback groups. API update requires interactive credential, `blade_mode_manage`, step-up, risk, and idempotency.

```php
final class BladeAvailabilityService
{
    public function state(): BladeAvailabilityState;
    public function enable(string $surface): BladeAvailabilityState;
    public function disable(string $surface): BladeAvailabilityState;
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/BladeAvailabilityPolicyTest.php tests/Feature/BladeAvailabilityCommandsTest.php tests/Feature/Api/V2/BladeSecurityApiTest.php tests/Unit/WncmsInstallationStateTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Security/BladeAvailabilityService.php src/Services/Security/BladeAvailabilityState.php src/Http/Middleware/EnsureWncmsBladeEnabled.php src/Http/Controllers/Api/V2/Backend/BladeSecurityController.php src/Console/Commands/BladeStatus.php src/Console/Commands/BladeDisable.php src/Console/Commands/BladeEnable.php routes/web.php src/Providers/PluginServiceProvider.php src/Providers/WncmsServiceProvider.php tests/Feature/BladeAvailabilityPolicyTest.php tests/Feature/BladeAvailabilityCommandsTest.php tests/Feature/Api/V2/BladeSecurityApiTest.php
git commit -m "feat(auth): add API-only Blade recovery mode"
```

### Task 13: Security Event Query API And Retention Command

**Files:**
- Create: `src/Http/Controllers/Api/V2/Backend/SecurityEventController.php`
- Create: `src/Http/Resources/Api/V2/SecurityEventResource.php`
- Create: `src/Console/Commands/PruneApiSecurityEvents.php`
- Modify: `routes/api/v2/backend.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Test: `tests/Feature/Api/V2/SecurityEventApiTest.php`
- Test: `tests/Feature/Api/V2/SecurityEventRetentionTest.php`

**Interfaces:**
- Produces allowlisted filters for type/severity/outcome/surface/actor/target/credential/website/request/run/date.
- Produces no update/delete HTTP operations; prune is the only ordinary deletion path.

- [ ] **Step 1: Write failing permission/scope/filter/retention tests**

```php
public function test_out_of_scope_security_event_is_indistinguishable_from_missing(): void
{
    $event = ApiSecurityEvent::factory()->forWebsite($this->otherWebsite)->create();
    $this->actingAsApiSession($this->admin)->getJson('/api/v2/backend/security/events/'.$event->event_id)->assertNotFound();
}
```

Assert explicit DTO fields, no raw context secrets/HMAC internals, no write routes, 30-365 day setting, 500-row batches, critical events following retention, aggregate completion event, and daily scheduler registration.

- [ ] **Step 2: Run and confirm failures**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventApiTest.php tests/Feature/Api/V2/SecurityEventRetentionTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement scoped read API and scheduled command**

Use `security_event_index/show`, actor website intersection, exact allowlisted query options, and resource projection. Register `wncms:auth:prune-security-events` and schedule it daily without overlapping.

```php
final class SecurityEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->only(['event_id', 'occurred_at', 'event_type', 'severity', 'outcome', 'surface', 'request_id', 'run_id', 'website_ids', 'error_code', 'http_status']);
    }
}
```

- [ ] **Step 4: Run focused tests**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/SecurityEventApiTest.php tests/Feature/Api/V2/SecurityEventRetentionTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Http/Controllers/Api/V2/Backend/SecurityEventController.php src/Http/Resources/Api/V2/SecurityEventResource.php src/Console/Commands/PruneApiSecurityEvents.php routes/api/v2/backend.php src/Providers/WncmsServiceProvider.php tests/Feature/Api/V2/SecurityEventApiTest.php tests/Feature/Api/V2/SecurityEventRetentionTest.php
git commit -m "feat(auth): expose scoped security events"
```

### Task 14: Contract Registry, Capabilities, And OpenAPI Security Metadata

**Files:**
- Modify: `src/Api/V2/Data/ApiOperationContract.php`
- Modify: `src/Api/V2/ApiContractValidator.php`
- Modify: `src/Api/V2/CapabilityResolver.php`
- Modify: `src/Api/V2/OpenApiDocumentBuilder.php`
- Create: `src/Api/V2/Providers/CoreAuthSecurityContractProvider.php`
- Modify: `config/wncms-api-v2.php`
- Modify: `resources/api/openapi-v2.json`
- Modify: `tests/Unit/Api/V2/ApiContractValidatorTest.php`
- Modify: `tests/Feature/Api/V2/CapabilitiesEndpointTest.php`
- Modify: `tests/Feature/Api/V2/OpenApiEndpointTest.php`
- Modify: `tests/Unit/Api/V2/ApiRouteRegistrationTest.php`
- Create: `tests/Unit/Api/V2/CoreAuthSecurityContractProviderTest.php`

**Interfaces:**
- Extends constructor with `securityRisk`, `acceptedCredentialTypes`, `requiresStepUp`, `stepUpPurposes`, `actionPlanEligible`, `legacyTokenAllowed`, `websiteScopeMode`, and `idempotencyRequired` using explicit defaults only where backward-compatible legacy provider data requires them.
- Produces runtime root `authentication` policy and per-operation security metadata.

- [ ] **Step 1: Write failing route/registry/schema/negative-validator tests**

```php
public function test_validator_rejects_critical_operation_that_accepts_legacy(): void
{
    $report = $this->validate($this->operation(securityRisk: 'critical', acceptedCredentialTypes: ['legacy_personal_access_token'], legacyTokenAllowed: true));
    $this->assertContains('contract.critical_legacy_forbidden', array_column($report['errors'], 'code'));
}
```

Add one negative fixture for every approved validation rule. Assert all auth/security routes are registered and removed from exclusions, schema version 2.1.0, Cookie schemes, JSON/Cookie `oneOf`, writeOnly secret, all vendor extensions, actor omission/disabled reasons, planned/step-up flags, private/no-store/Vary headers, and no Cookie refresh leak.

- [ ] **Step 2: Run and confirm contract failures**

Run: `vendor/bin/phpunit tests/Unit/Api/V2/ApiContractValidatorTest.php tests/Feature/Api/V2/CapabilitiesEndpointTest.php tests/Feature/Api/V2/OpenApiEndpointTest.php tests/Unit/Api/V2/ApiRouteRegistrationTest.php tests/Unit/Api/V2/CoreAuthSecurityContractProviderTest.php`

Expected: FAIL because metadata/provider/schemas are absent.

- [ ] **Step 3: Implement additive contract metadata and provider**

Keep existing `risk`; add `security_risk`. Generate exact security requirements for public, bearer, JSON refresh, and Cookie refresh operations. Capability visibility checks permission then ability; credential mismatch and missing website context become safe disabled reasons. Never cache across actor/context.

```php
public function __construct(
    public readonly string $id,
    public readonly string $domain,
    public readonly string $surface,
    public readonly string $method,
    public readonly string $path,
    public readonly string $routeName,
    public readonly ?string $permission,
    public readonly ?string $ability,
    public readonly bool $websiteScoped,
    public readonly string $risk,
    public readonly string $implementation,
    public readonly ApiSchema $request,
    public readonly ApiSchema $response,
    public readonly array $filters = [],
    public readonly array $sorts = [],
    public readonly array $includes = [],
    public readonly array $fields = [],
    public readonly bool $idempotent = false,
    public readonly string $securityRisk = 'normal',
    public readonly array $acceptedCredentialTypes = ['interactive_access', 'service_token'],
    public readonly bool $requiresStepUp = false,
    public readonly array $stepUpPurposes = [],
    public readonly bool $actionPlanEligible = false,
    public readonly bool $legacyTokenAllowed = false,
    public readonly string $websiteScopeMode = 'none',
    public readonly bool $idempotencyRequired = false,
    public readonly array $refreshTransports = [],
) {}
```

- [ ] **Step 4: Generate snapshot and run contract tests**

From host root run:

```bash
php artisan wncms:api-v2-openapi --write=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

Then run the five PHPUnit files from Step 2. Expected: PASS and exact snapshot match.

- [ ] **Step 5: Commit**

```bash
git add src/Api/V2 config/wncms-api-v2.php resources/api/openapi-v2.json tests/Unit/Api/V2 tests/Feature/Api/V2/CapabilitiesEndpointTest.php tests/Feature/Api/V2/OpenApiEndpointTest.php
git commit -m "feat(auth): publish security contracts"
```

### Task 15: Public Documentation And Locale Synchronization

**Files:**
- Modify: `documentations/manual/api/{overview,getting-started,authentication,capabilities,openapi,contracts,errors,troubleshooting}.md`
- Modify: `documentations/manual/api/examples/nextjs-integration.md`
- Create: `documentations/manual/api/{sessions,service-tokens,security-policy,api-only-mode,legacy-authentication}.md`
- Mirror same paths under: `documentations/manual/zh-CN/api`
- Mirror same paths under: `documentations/manual/zh-TW/api`
- Modify: `lang/{en,zh_CN,zh_TW,ja}/word.php`
- Create: `tests/Unit/Api/V2/AuthSecurityDocumentationTest.php`

**Interfaces:**
- Public docs depend on exact settings, permissions, routes, error codes, examples, and OpenAPI generated in earlier tasks.

- [ ] **Step 1: Write failing documentation parity/safety tests**

```php
public function test_three_language_auth_docs_share_machine_tokens(): void
{
    foreach (['sessions.md', 'service-tokens.md', 'security-policy.md', 'api-only-mode.md', 'legacy-authentication.md'] as $file) {
        $this->assertSame($this->machineTokens("documentations/manual/api/$file"), $this->machineTokens("documentations/manual/zh-CN/api/$file"));
        $this->assertSame($this->machineTokens("documentations/manual/api/$file"), $this->machineTokens("documentations/manual/zh-TW/api/$file"));
    }
}
```

Assert relative links, route/permission/setting/error tokens, no realistic credential pattern, no refresh token in localStorage, legacy deprecation labels, and four-locale UI keys.

- [ ] **Step 2: Run and confirm missing/outdated docs fail**

Run: `vendor/bin/phpunit tests/Unit/Api/V2/AuthSecurityDocumentationTest.php`

Expected: FAIL.

- [ ] **Step 3: Write synchronized public documentation**

Document both transports, session lifecycle, templates/scopes/expiry, step-up/plans, Blade runbook, legacy 90-day migration, settings/permissions, stable failures, security events, OpenAPI/capabilities, and server-side Next.js BFF examples. Use only `.example.test` and nonworking example tokens.

```bash
php artisan wncms:blade:status
php artisan wncms:blade:disable --force
php artisan wncms:blade:enable
```

- [ ] **Step 4: Run documentation and locale tests**

Run: `vendor/bin/phpunit tests/Unit/Api/V2/AuthSecurityDocumentationTest.php tests/Feature/Api/V2/AuthSecuritySettingsTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add documentations/manual lang tests/Unit/Api/V2/AuthSecurityDocumentationTest.php
git commit -m "docs(auth): document v7 security APIs"
```

### Task 16: Pure API Acceptance And Cross-Cutting Regression

**Files:**
- Create: `tests/Feature/Api/V2/AuthSecurityAcceptanceTest.php`
- Create: `tests/Feature/Api/V2/AuthSecurityInvariantTest.php`
- No planned production modifications; a demonstrated defect returns to the task that owns that production interface.

**Interfaces:**
- Consumes every earlier task and verifies the supported system boundary without Next.js/Farm/Docker.

- [ ] **Step 1: Write the end-to-end JSON and Cookie acceptance tests**

```php
#[DataProvider('refreshTransports')]
public function test_complete_api_only_authentication_flow(string $transport): void
{
    $this->configureTransport($transport);
    $session = $this->loginThroughSupportedTransport();
    $this->assertMeAndRefresh($session);
    $service = $this->createUseRotateAndRevokeScopedServiceToken($session);
    $this->changePasswordAndAssertEveryCredentialRevoked($session, $service);
    $this->loginDisableBladeAssertApiWorksEnableWithCliAndAssertHtmlRestored();
}
```

Invariant tests assert no unauthorized downstream execution, no domain-based scope expansion, no secret persistence, mandatory critical audit, and stable envelopes/error mappings.

- [ ] **Step 2: Run acceptance tests and record every failure**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AuthSecurityAcceptanceTest.php tests/Feature/Api/V2/AuthSecurityInvariantTest.php`

Expected: any failure identifies an integration gap; do not weaken assertions.

- [ ] **Step 3: Fix only demonstrated integration gaps**

For each failure, add the narrowest production correction and retain the reproducing assertion. Do not add unrelated refactors or new product behavior.

```bash
vendor/bin/phpunit tests/Feature/Api/V2/AuthSecurityAcceptanceTest.php --stop-on-failure
```

Repeat the single failing test after each correction, then run the complete two-file acceptance command from Step 2.

- [ ] **Step 4: Run auth/security and full regression suites**

```bash
vendor/bin/phpunit tests/Feature/Api/V2 tests/Unit/Api/V2 tests/Feature/BladeAvailabilityPolicyTest.php tests/Feature/BladeAvailabilityCommandsTest.php
vendor/bin/phpunit
```

Expected: all tests pass with zero failures/errors.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Api/V2/AuthSecurityAcceptanceTest.php tests/Feature/Api/V2/AuthSecurityInvariantTest.php
git commit -m "test(auth): verify complete security flow"
```

If an acceptance failure exposes a production defect, return to the owning
earlier task, add the reproducing test there, and commit that task's exact file
list before committing these two acceptance tests. Do not hide production fixes
inside the acceptance-test commit.

### Task 17: Fresh-Install Schema, Static Verification, And Independent Review

**Files:**
- Modify: `database/testing.sqlite`
- Modify: `database/testing.schema.sql`
- No planned production modifications; accepted findings return to their owning implementation task.

**Interfaces:**
- Produces fresh evidence for the complete implementation before updater work.

- [ ] **Step 1: Regenerate and verify the prepared database**

Run: `composer run test:prepare-db`

Expected: exit 0; generated schema contains all five WNCMS-owned tables and unchanged PAT structure.

- [ ] **Step 2: Run syntax, formatting, diff, Composer, and security checks**

```bash
find src database/migrations tests -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/pint --test
git diff --check
composer validate --strict
composer audit
```

Expected: every command exits 0 and Composer reports no advisories.

- [ ] **Step 3: Run contract and full tests from the correct roots**

From `/www/wwwroot/package.wncms.cc`:

```bash
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:check-backend-api-v2-parity --json
```

From the package root:

```bash
vendor/bin/phpunit
```

Expected: snapshot exact match, contract/parity report has `errors: []`, and PHPUnit has zero failures/errors.

- [ ] **Step 4: Request independent code and security review**

Review against `documentations/plans/v7-authentication-security-design.md`, emphasizing transaction boundaries, race safety, secret leakage, host PAT ownership, route inventory, contract drift, and permission/scope ordering. Record every finding with severity and exact file/line.

- [ ] **Step 5: Resolve accepted findings through their owning tasks**

For each accepted defect, return to the task that owns the affected interface,
add a reproducing failing test, confirm failure, implement the narrow fix, run
that task's stated focused tests, and use that task's explicit staging list.
After all accepted findings are closed, rerun Step 3. Do not create an empty
review commit when there are no findings.

### Task 18: Existing-Installation v7 Upgrade — Explicit Authorization Checkpoint

**Files after fresh explicit authorization only:**
- Modify: `updates/update_core_7.0.0.php`
- Create: `tests/Feature/Api/V2/AuthSecurityUpgradeTest.php`

**Interfaces:**
- Produces an idempotent existing-install upgrade whose final schema/settings/permissions match fresh installation.

- [ ] **Step 1: Stop and obtain fresh explicit authorization**

Ask exactly whether the user authorizes reading, editing, executing, staging, and committing `updates/update_core_7.0.0.php` for the v7 authentication/security upgrade. Do not inspect the file until the response explicitly authorizes it.

- [ ] **Step 2: After authorization, write failing upgrade fixtures**

Fixtures cover all five tables missing, partially present compatible WNCMS tables, incompatible same-name table, repeat execution, failure before version update, settings/permissions seeds, legacy 90-day cutoff, and preservation of user/content/PAT schema/data.

Run: `vendor/bin/phpunit tests/Feature/Api/V2/AuthSecurityUpgradeTest.php`

Expected: FAIL because the authorized updater lacks the new upgrade logic.

- [ ] **Step 3: Implement guarded updater logic**

Use explicit `Schema` and `Blueprint` imports, `Schema::hasTable`/`hasColumn`/index guards, transactions where supported, and preflight rejection for incompatible same-name tables. Create settings/permissions only after schema success; set legacy upgrade defaults; call `uss('core_version', '7.0.0')` only after every validation succeeds. Never alter/copy/delete PAT rows.

```php
ApiAuthSchema::assertCompatibleExistingTables();
ApiAuthSchema::createApiSessions();
ApiAuthSchema::createApiAccessTokens();
ApiAuthSchema::createApiRefreshTokens();
ApiAuthSchema::createApiServiceTokens();
ApiAuthSchema::createApiSecurityEvents();

uss('core_version', '7.0.0'); // final statement after every schema/seed validation succeeds
```

- [ ] **Step 4: Run upgrade, fresh-install, and full regression tests**

```bash
vendor/bin/phpunit tests/Feature/Api/V2/AuthSecurityUpgradeTest.php tests/Feature/Api/V2/AuthSecuritySchemaTest.php tests/Feature/PersonalAccessTokensMigrationTest.php
composer run test:prepare-db
vendor/bin/phpunit
```

Expected: all tests pass and repeated updater execution is a no-op success.

- [ ] **Step 5: Commit only authorized updater scope**

```bash
git add updates/update_core_7.0.0.php tests/Feature/Api/V2/AuthSecurityUpgradeTest.php
git commit -m "feat(auth): upgrade existing v7 installations"
```

### Task 19: Final Verification And Delivery Gate

**Files:**
- No planned source changes; any failure returns to the owning earlier task with a new failing test.

**Interfaces:**
- Produces final evidence and a reviewable branch. Does not authorize push/release/deploy.

- [ ] **Step 1: Verify exact scope and protected-file authorization state**

```bash
git status --short --untracked-files=normal
git diff --check
git log --oneline --decorate -25
```

Expected: only intentional changes/commits; protected updater appears in history only if Task 18 received explicit authorization.

- [ ] **Step 2: Run all completion commands fresh**

```bash
composer run test:prepare-db
vendor/bin/phpunit
vendor/bin/pint --test
composer validate --strict
composer audit
git diff --check
```

From host root also run OpenAPI snapshot and parity commands from Task 17. Expected: all exit 0, no PHPUnit failures/errors, no contract errors, no advisories.

- [ ] **Step 3: Re-run secret canary and route inventory suites**

Run: `vendor/bin/phpunit tests/Feature/Api/V2/SecuritySecretCanaryTest.php tests/Feature/BladeAvailabilityPolicyTest.php tests/Feature/Api/V2/AuthSecurityInvariantTest.php`

Expected: PASS.

- [ ] **Step 4: Compare implementation against every design heading**

Produce a checklist mapping Credential Model, Routes/Transport, Guard Order, Failures, Risk, Blade, Legacy, Audit, Capabilities/OpenAPI, Tests, and Documentation to concrete tests/files. Any missing mapping reopens the owning task; do not declare completion.

- [ ] **Step 5: Present integration choices without pushing**

Report commits, verification counts/output, any intentionally blocked updater work, and offer branch integration/push/PR choices. Do not push, release, tag, or deploy without separate authorization.

## Execution Notes

- Recommended execution is subagent-driven development with one fresh implementer per task and specification/compliance review followed by code-quality/security review before advancing.
- Inline execution is acceptable using executing-plans in small batches with checkpoints after Tasks 4, 8, 12, 15, 17, and 18.
- Task 18 is never implied by approval of this plan or selection of an execution mode; it requires a new explicit authorization naming the protected updater file.
- The implementation is not complete if Task 18 remains blocked; report fresh-install/authentication delivery separately from existing-install upgrade readiness.
