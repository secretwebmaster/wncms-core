# WNCMS v7 Contract Kernel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the API v2 contract registry, stable response/error layer, schema/query primitives, runtime capabilities, OpenAPI 3.1 generation, idempotency and revision guards, asynchronous-operation contract, and CI validation needed before domain-by-domain API parity work.

**Architecture:** Add a typed contract kernel under `Wncms\Api\V2` and register it as container singletons from `WncmsServiceProvider`. Adapt the existing `wncms-backend-api-v2` configuration into the registry without changing current URLs or controllers, then expose authenticated capabilities and public OpenAPI endpoints from the registry. Keep durable operation storage and domain migrations out of this phase; production uses a cache repository behind an interface so the Operations phase can replace it without changing consumers.

**Tech Stack:** PHP 8.4, Laravel 13, Laravel Sanctum 4.1, PHPUnit 12.5, Orchestra Testbench 11, native Laravel cache/locks, JSON Schema 2020-12, OpenAPI 3.1.

## Global Constraints

- Keep `/api/v2/*` URLs and the existing Links response contract backward compatible.
- REST JSON is the only formal v7 protocol; do not add GraphQL.
- OpenAPI and runtime capabilities must read from the same `ApiContractRegistry`.
- Do not add Composer dependencies for OpenAPI generation.
- Do not modify `updates/update_core_7.0.0.php`, `config/installer.php`, or start any release/tag/publish action.
- Do not add or alter database tables in this phase.
- Formal contracts must not serialize arbitrary Eloquent attributes or accept arbitrary mass-assignment payloads.
- All new methods use full PHPDoc according to `wncms-function-docblocks`; function bodies use only necessary `//` comments.
- Use exact WNCMS PHP formatting: one space around `=>`, no heredoc, and no emojis.
- Public manual changes must keep English, `zh-CN`, and `zh-TW` section structure and code snippets synchronized.
- Apply `wncms-changelog-sync` before editing the four `v7.0.0-alpha1` changelogs.
- Run Laravel runtime checks from `/www/wwwroot/package.wncms.cc`, not from this package directory.
- Preserve the unrelated untracked `updates/update_core_7.0.0.php`; never read, edit, stage, or commit it.

---

## File Structure

New production files are grouped by responsibility:

```text
config/wncms-api-v2.php
src/Api/V2/
├── Contracts/
│   ├── ApiContractProvider.php
│   ├── IdempotencyStore.php
│   └── OperationRepository.php
├── Data/
│   ├── ApiDomainContract.php
│   ├── ApiOperationContract.php
│   ├── ApiSchema.php
│   ├── AsyncOperation.php
│   └── QueryOptions.php
├── Enums/AsyncOperationStatus.php
├── Exceptions/
│   ├── ApiConflictException.php
│   └── ApiContractException.php
├── Providers/
│   ├── CoreFrontendContractProvider.php
│   └── LegacyBackendContractProvider.php
├── Repositories/
│   ├── CacheIdempotencyStore.php
│   └── CacheOperationRepository.php
├── ApiContractRegistry.php
├── ApiContractValidator.php
├── ApiResponseFactory.php
├── CapabilityResolver.php
├── ConcurrencyGuard.php
├── IdempotencyService.php
├── OpenApiDocumentBuilder.php
├── OperationService.php
└── QueryOptionsResolver.php
```

HTTP adapters remain under existing WNCMS locations:

```text
src/Http/Controllers/Api/V2/ContractController.php
src/Http/Controllers/Api/V2/Backend/OperationController.php
src/Http/Middleware/AssignApiV2RequestId.php
src/Http/Middleware/EnforceApiV2Idempotency.php
routes/api/v2/contracts.php
```

The registry owns declarations; controllers only translate HTTP input/output;
domain services added in later plans own business behavior.

---

### Task 1: Add Typed Contract Data And Registry

**Files:**
- Create: `config/wncms-api-v2.php`
- Create: `src/Api/V2/Contracts/ApiContractProvider.php`
- Create: `src/Api/V2/Data/ApiSchema.php`
- Create: `src/Api/V2/Data/ApiDomainContract.php`
- Create: `src/Api/V2/Data/ApiOperationContract.php`
- Create: `src/Api/V2/Exceptions/ApiContractException.php`
- Create: `src/Api/V2/ApiContractRegistry.php`
- Test: `tests/Unit/Api/V2/ApiContractRegistryTest.php`

**Interfaces:**
- Consumes: Laravel container and `config('wncms-api-v2')`.
- Produces: `ApiContractProvider::register(ApiContractRegistry $registry): void`, `ApiContractRegistry::registerDomain(ApiDomainContract $domain): void`, `registerOperation(ApiOperationContract $operation): void`, `domains(): array`, `operations(): array`, `operation(string $id): ?ApiOperationContract`, and `toArray(): array`.

- [ ] **Step 1: Write failing registry tests**

Cover deterministic ordering, duplicate domain/operation rejection, immutable
array export, valid surfaces (`frontend`, `backend`, `system`), methods, risk
levels, and implementation markers:

```php
$registry = new ApiContractRegistry;
$registry->registerDomain(new ApiDomainContract('links', 'Links'));
$registry->registerOperation(new ApiOperationContract(
    id: 'backend.links.index',
    domain: 'links',
    surface: 'backend',
    method: 'GET',
    path: '/api/v2/backend/links',
    routeName: 'api.v2.backend.links.index',
    permission: 'link_index',
    ability: 'links:read',
    websiteScoped: true,
    risk: 'read',
    implementation: 'domain',
    request: ApiSchema::object(),
    response: ApiSchema::object(),
    filters: ['status'],
    sorts: ['id', 'created_at'],
    includes: ['tags', 'websites'],
    fields: ['id', 'name', 'url', 'status'],
    idempotent: false,
));

$this->assertSame('backend.links.index', $registry->operation('backend.links.index')?->id);
$this->assertSame(['links'], array_keys($registry->domains()));
$this->assertSame(['backend.links.index'], array_keys($registry->operations()));
```

- [ ] **Step 2: Run the focused test and verify failure**

Run:

```bash
vendor/bin/phpunit tests/Unit/Api/V2/ApiContractRegistryTest.php --testdox
```

Expected: failure because the `Wncms\Api\V2` classes do not exist.

- [ ] **Step 3: Implement immutable contract objects and registry**

Use exact constructor fields shown in Step 1. `ApiSchema` wraps one JSON Schema
array and exposes these factories:

```php
public static function object(array $properties = [], array $required = []): self;
public static function arrayOf(self $items): self;
public static function string(?array $enum = null): self;
public static function integer(): self;
public static function boolean(): self;
public function toArray(): array;
```

`ApiOperationContract` uses the required constructor fields shown in Step 1,
followed by these optional fields with the exact defaults:

```php
public readonly array $filters = [];
public readonly array $sorts = [];
public readonly array $includes = [];
public readonly array $fields = [];
public readonly bool $idempotent = false;
```

`ApiContractRegistry` keeps associative arrays keyed by stable IDs, sorts them
with `ksort()` before returning, and throws `ApiContractException` for duplicate
IDs or operations whose domain has not been registered.

Add this initial config:

```php
return [
    'schema_version' => '2.0.0',
    'openapi' => [
        'title' => 'WNCMS API',
        'version' => '2.0.0',
    ],
    'idempotency' => [
        'header' => 'Idempotency-Key',
        'ttl_seconds' => 86400,
        'lock_seconds' => 15,
    ],
    'operations' => [
        'ttl_seconds' => 86400,
    ],
    'providers' => [
        \Wncms\Api\V2\Providers\CoreFrontendContractProvider::class,
        \Wncms\Api\V2\Providers\LegacyBackendContractProvider::class,
    ],
];
```

- [ ] **Step 4: Run the focused test**

Expected: all registry tests pass.

- [ ] **Step 5: Commit the typed registry**

```bash
git add config/wncms-api-v2.php src/Api/V2 tests/Unit/Api/V2/ApiContractRegistryTest.php
git commit -m "feat(api-v2): add typed contract registry"
```

---

### Task 2: Register Core And Legacy API Contracts

**Files:**
- Create: `src/Api/V2/Providers/CoreFrontendContractProvider.php`
- Create: `src/Api/V2/Providers/LegacyBackendContractProvider.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Test: `tests/Unit/Api/V2/LegacyBackendContractProviderTest.php`
- Test: `tests/Unit/Api/V2/ApiContractServiceProviderTest.php`

**Interfaces:**
- Consumes: `config('wncms-backend-api-v2.resources')`, `config('wncms-backend-api-v2.actions')`, and Task 1 registry types.
- Produces: one lazily constructed `ApiContractRegistry` singleton containing frontend health/translations and every configured backend resource/action.

- [ ] **Step 1: Write failing provider and container tests**

Assert that:

```php
$registry = app(ApiContractRegistry::class);

$this->assertSame($registry, app(ApiContractRegistry::class));
$this->assertNotNull($registry->operation('frontend.health'));
$this->assertNotNull($registry->operation('backend.links.index'));
$this->assertNotNull($registry->operation('backend.links.bulk_update'));
$this->assertSame(
    'legacy_bridge',
    $registry->operation('backend.links.bulk_update')?->implementation
);
$this->assertSame(
    'domain',
    $registry->operation('backend.links.index')?->implementation
);
```

Also iterate every enabled resource route and configured action and assert it
has exactly one registry operation.

- [ ] **Step 2: Run the two tests and verify failure**

Expected: providers and service-container binding are missing.

- [ ] **Step 3: Implement config adapters without changing routes**

`CoreFrontendContractProvider` registers `frontend.health` and
`system.translations`. `LegacyBackendContractProvider` converts resource action
names with this exact mapping:

```php
$resourceMethods = [
    'index' => ['GET', "/api/v2/backend/{$resource}"],
    'show' => ['GET', "/api/v2/backend/{$resource}/{id}"],
    'store' => ['POST', "/api/v2/backend/{$resource}"],
    'update' => ['PATCH', "/api/v2/backend/{$resource}/{id}"],
    'destroy' => ['DELETE', "/api/v2/backend/{$resource}/{id}"],
    'bulk_delete' => ['POST', "/api/v2/backend/{$resource}/bulk_delete"],
];
```

Use `domain` only when a dedicated API v2 resource controller is configured;
use `legacy_resource` for the generic `ResourceController`; classify every
configured bridge action as `legacy_bridge`. Preserve its exact HTTP method,
URI, route name, permission, and website-scoped status.

In `WncmsServiceProvider::mergeConfigs()`, add `wncms-api-v2`. In `register()`,
call a new `registerApiV2ContractServices(): void` after configs are merged. The
singleton factory instantiates providers from config and calls `register()` in
listed order.

- [ ] **Step 4: Run provider tests and existing route-parity test**

```bash
vendor/bin/phpunit tests/Unit/Api/V2/LegacyBackendContractProviderTest.php tests/Unit/Api/V2/ApiContractServiceProviderTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php --testdox
```

Expected: all pass and existing route configuration is unchanged.

- [ ] **Step 5: Commit provider registration**

```bash
git add src/Api/V2/Providers src/Providers/WncmsServiceProvider.php tests/Unit/Api/V2
git commit -m "feat(api-v2): adapt existing routes into contract registry"
```

---

### Task 3: Centralize Envelopes, Request IDs, And Error Semantics

**Files:**
- Create: `src/Api/V2/ApiResponseFactory.php`
- Create: `src/Http/Middleware/AssignApiV2RequestId.php`
- Modify: `src/Http/Controllers/Api/V2/Backend/ApiV2Controller.php`
- Modify: `src/Http/Middleware/ApiV2TokenAuth.php`
- Modify: `src/Http/Middleware/ApiV2HasWebsite.php`
- Modify: `src/Http/Middleware/ApiV2Whitelist.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `routes/api/v2/backend.php`
- Modify: `routes/api/v2/frontend.php`
- Test: `tests/Feature/Api/V2/ApiEnvelopeContractTest.php`

**Interfaces:**
- Consumes: `AutomationResult`, incoming `X-Request-ID`, Laravel validation/auth/http exceptions.
- Produces: `ApiResponseFactory::success()`, `failure()`, and `fromThrowable()` returning `JsonResponse` with an `X-Request-ID` header and `meta.request_id`.

- [ ] **Step 1: Write failing envelope contract tests**

Test a successful health response, missing token, disabled API, missing website,
validation error, authorization error, not found, conflict, and unexpected
exception. For every response assert exactly these top-level keys:

```php
$response->assertJsonStructure([
    'code',
    'status',
    'message',
    'data',
    'meta' => ['request_id'],
    'errors',
]);

$this->assertSame(
    $response->headers->get('X-Request-ID'),
    $response->json('meta.request_id')
);
```

Accept a caller-provided UUID request ID; replace malformed IDs with a generated
UUID. Assert unexpected exception messages are hidden when `app.debug=false`.

- [ ] **Step 2: Run the focused feature test and verify failure**

Expected: current responses have empty `meta` and no response request-ID header.

- [ ] **Step 3: Implement request ID middleware and response factory**

Use request attribute key `wncms_api_v2_request_id`. The factory signatures are:

```php
public function success(
    mixed $data = null,
    string $message = 'success',
    int $code = 200,
    array $meta = []
): JsonResponse;

public function failure(
    string $errorCode,
    string $message,
    int $code,
    array $errors = [],
    mixed $data = null,
    array $meta = []
): JsonResponse;

public function fromThrowable(\Throwable $exception): JsonResponse;
```

`failure()` adds `meta.error_code`. Use these exact non-field error codes:
`authentication.missing_token`, `authentication.invalid_token`,
`authorization.denied`, `website.context_missing`, `resource.not_found`,
`request.conflict`, `validation.failed`, and `server.unexpected_error`.

Register middleware alias `api_v2_request_id` and place it before whitelist,
token, and website middleware in both v2 route groups. Refactor the three v2
middleware classes and `ApiV2Controller` to resolve `ApiResponseFactory`; keep
the existing `ok()`, `error()`, and `fromThrowable()` method signatures so
current controllers remain source compatible.

- [ ] **Step 4: Run envelope and Links API tests**

```bash
vendor/bin/phpunit tests/Feature/Api/V2/ApiEnvelopeContractTest.php tests/Feature/LinkApiV2ControllerTest.php --testdox
```

Expected: new request-ID/error assertions and existing Links assertions pass.

- [ ] **Step 5: Commit the HTTP response kernel**

```bash
git add src/Api/V2/ApiResponseFactory.php src/Http src/Providers/WncmsServiceProvider.php routes/api/v2 tests/Feature/Api/V2
git commit -m "feat(api-v2): standardize request and error envelopes"
```

---

### Task 4: Add Query, Schema, And Optimistic-Concurrency Primitives

**Files:**
- Create: `src/Api/V2/Data/QueryOptions.php`
- Create: `src/Api/V2/QueryOptionsResolver.php`
- Create: `src/Api/V2/Exceptions/ApiConflictException.php`
- Create: `src/Api/V2/ConcurrencyGuard.php`
- Test: `tests/Unit/Api/V2/QueryOptionsResolverTest.php`
- Test: `tests/Unit/Api/V2/ConcurrencyGuardTest.php`

**Interfaces:**
- Consumes: request query parameters, allowlists from `ApiOperationContract`, and an Eloquent model.
- Produces: normalized `QueryOptions` and stable revision tokens for later domain controllers.

- [ ] **Step 1: Write failing query and concurrency tests**

Test normalization and rejection for `page`, `per_page`, `keyword`, `filter`,
`sort`, `direction`, `include`, and `fields`. Use this expected object:

```php
new QueryOptions(
    page: 2,
    perPage: 25,
    keyword: 'demo',
    filters: ['status' => 'published'],
    sort: 'created_at',
    direction: 'desc',
    includes: ['tags', 'user'],
    fields: ['id', 'title'],
);
```

Concurrency tests assert:

```php
$revision = $guard->revision($model);
$guard->assertMatches($model, '"' . $revision . '"');

$this->expectException(ApiConflictException::class);
$guard->assertMatches($model, '"stale"');
```

- [ ] **Step 2: Run the focused tests and verify failure**

Expected: query resolver and concurrency guard do not exist.

- [ ] **Step 3: Implement strict normalization and revision hashing**

`QueryOptionsResolver::resolve(Request $request, ApiOperationContract $contract)`
must cap `per_page` at 100, accept comma-separated includes/fields, reject
undeclared filters/sorts/includes/fields with a `ValidationException`, and
normalize direction to `asc|desc`.

`ConcurrencyGuard::revision(Model $model): string` returns SHA-256 of the model
class, route key, and `updated_at` serialized in UTC. `assertMatches()` strips
weak/quoted ETag syntax and throws `ApiConflictException` when `If-Match` is
missing or stale. `responseEtag()` returns the quoted revision string.

- [ ] **Step 4: Run the focused tests**

Expected: all normalization, allowlist, and stale-write tests pass.

- [ ] **Step 5: Commit reusable request primitives**

```bash
git add src/Api/V2 tests/Unit/Api/V2
git commit -m "feat(api-v2): add query and revision primitives"
```

---

### Task 5: Expose Runtime Capabilities

**Files:**
- Create: `src/Api/V2/CapabilityResolver.php`
- Create: `src/Http/Controllers/Api/V2/ContractController.php`
- Create: `routes/api/v2/contracts.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V2/CapabilitiesEndpointTest.php`

**Interfaces:**
- Consumes: `ApiContractRegistry`, authenticated user permissions, and current website context.
- Produces: authenticated `GET /api/v2/capabilities` named `api.v2.capabilities`.

- [ ] **Step 1: Write failing capabilities tests**

Verify missing/invalid tokens return 401. For an authenticated operator verify:

```php
$response->assertOk()->assertJsonPath('data.schema_version', '2.0.0');
$response->assertJsonPath('data.domains.links.key', 'links');
$response->assertJsonPath(
    'data.domains.links.operations.backend.links.index.available',
    true
);
```

An operation whose permission the actor lacks must be absent. An authorized
website-scoped operation with no current website remains present with
`available=false` and `disabled_reasons=['website.context_missing']`. Confirm
plugin/test providers registered in the container appear without route-file
changes.

- [ ] **Step 2: Run the focused test and verify failure**

Expected: route/controller/resolver do not exist.

- [ ] **Step 3: Implement permission-filtered capabilities**

Return this stable shape inside the standard envelope:

```json
{
  "schema_version": "2.0.0",
  "domains": {
    "links": {
      "key": "links",
      "label": "Links",
      "operations": {
        "backend.links.index": {
          "method": "GET",
          "path": "/api/v2/backend/links",
          "permission": "link_index",
          "ability": "links:read",
          "website_scoped": true,
          "risk": "read",
          "implementation": "domain",
          "idempotent": false,
          "filters": ["status"],
          "sorts": ["id", "created_at"],
          "includes": ["tags", "websites"],
          "fields": ["id", "name", "url", "status"],
          "available": true,
          "disabled_reasons": [],
          "request_schema": {},
          "response_schema": {}
        }
      }
    }
  }
}
```

Create `routes/api/v2/contracts.php`, include it from `routes/api.php`, and place
the route under prefix `v2` with whitelist, request-ID, and token middleware but
outside `api_v2_has_website`, because discovery must explain a missing website
context. Its exact URI is `/api/v2/capabilities`, not a backend-prefixed alias.

- [ ] **Step 4: Run capabilities, auth, and Links tests**

```bash
vendor/bin/phpunit tests/Feature/Api/V2/CapabilitiesEndpointTest.php tests/Feature/LinkApiV2ControllerTest.php --testdox
```

Expected: capabilities filtering and existing authentication behavior pass.

- [ ] **Step 5: Commit runtime discovery**

```bash
git add src/Api/V2/CapabilityResolver.php src/Http/Controllers/Api/V2/ContractController.php routes/api.php routes/api/v2/contracts.php tests/Feature/Api/V2
git commit -m "feat(api-v2): expose runtime capabilities"
```

---

### Task 6: Generate And Validate OpenAPI 3.1

**Files:**
- Create: `src/Api/V2/OpenApiDocumentBuilder.php`
- Create: `src/Console/Commands/GenerateApiV2OpenApi.php`
- Modify: `src/Http/Controllers/Api/V2/ContractController.php`
- Modify: `routes/api/v2/contracts.php`
- Create: `resources/api/openapi-v2.json`
- Test: `tests/Unit/Api/V2/OpenApiDocumentBuilderTest.php`
- Test: `tests/Feature/Api/V2/OpenApiEndpointTest.php`
- Test: `tests/Unit/Api/V2/GenerateApiV2OpenApiCommandTest.php`

**Interfaces:**
- Consumes: the same `ApiContractRegistry` used by capabilities.
- Produces: public `GET /api/v2/openapi.json`, `OpenApiDocumentBuilder::build(): array`, and `wncms:api-v2-openapi {--write=} {--check=}`.

- [ ] **Step 1: Write failing builder, endpoint, and command tests**

Assert OpenAPI version `3.1.0`, JSON Schema dialect
`https://json-schema.org/draft/2020-12/schema`, bearer security scheme, standard
envelope schemas, operation IDs, permissions/extensions, and deterministic key
ordering. Assert every registry operation appears exactly once under `paths`.

Command behavior:

```bash
php artisan wncms:api-v2-openapi --write=resources/api/openapi-v2.json
php artisan wncms:api-v2-openapi --check=resources/api/openapi-v2.json
```

The first exits 0 and writes normalized pretty JSON with one trailing newline;
the second exits 0 only when the generated core document matches the file.

- [ ] **Step 2: Run tests and verify failure**

Expected: builder, endpoint, command, and snapshot are missing.

- [ ] **Step 3: Implement dependency-free OpenAPI generation**

Map each contract operation to its exact path/method. Include:

```php
'openapi' => '3.1.0',
'jsonSchemaDialect' => 'https://json-schema.org/draft/2020-12/schema',
'info' => [
    'title' => config('wncms-api-v2.openapi.title'),
    'version' => config('wncms-api-v2.openapi.version'),
],
'components' => [
    'securitySchemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'WNCMS personal access token',
        ],
    ],
],
```

Add `x-wncms-permission`, `x-wncms-ability`, `x-wncms-website-scoped`,
`x-wncms-risk`, and `x-wncms-implementation` per operation. Public frontend
operations have no bearer requirement; backend operations use `bearerAuth`.
The runtime endpoint remains behind `api_v2_whitelist` but does not require a
token or website context.

- [ ] **Step 4: Generate snapshot and run tests**

Run the command from the Laravel app root, then run all three package tests.
Expected: deterministic snapshot and endpoint output pass.

- [ ] **Step 5: Commit OpenAPI generation**

```bash
git add src/Api/V2/OpenApiDocumentBuilder.php src/Console/Commands/GenerateApiV2OpenApi.php src/Http/Controllers/Api/V2/ContractController.php routes/api/v2/contracts.php resources/api/openapi-v2.json tests
git commit -m "feat(api-v2): generate OpenAPI contract"
```

---

### Task 7: Add Cache-Backed Idempotency Enforcement

**Files:**
- Create: `src/Api/V2/Contracts/IdempotencyStore.php`
- Create: `src/Api/V2/Repositories/CacheIdempotencyStore.php`
- Create: `src/Api/V2/IdempotencyService.php`
- Create: `src/Http/Middleware/EnforceApiV2Idempotency.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Test: `tests/Feature/Api/V2/IdempotencyMiddlewareTest.php`

**Interfaces:**
- Consumes: authenticated actor/token ID, contract operation ID, normalized request fingerprint, Laravel cache lock.
- Produces: replay-safe mutation middleware for routes with `api_operation_id` defaults.

- [ ] **Step 1: Write failing middleware tests**

Register a test-only POST route with:

```php
Route::post('/_test/idempotent', $handler)
    ->defaults('api_operation_id', 'backend.test.create')
    ->middleware(['api_v2_request_id', 'api_v2_idempotency']);
```

Assert missing keys return `400` with `idempotency.key_missing`; the first
request executes once; an identical request replays the original status/body
with `Idempotency-Replayed: true`; a reused key with different normalized input
returns `409` with `idempotency.key_conflict`; concurrent lock failure returns
`409` with `idempotency.in_progress`.

- [ ] **Step 2: Run the focused test and verify failure**

Expected: store/service/middleware aliases do not exist.

- [ ] **Step 3: Implement actor-scoped cache records and locks**

Use cache keys derived from SHA-256 of actor ID, token ID, operation ID, and
idempotency key. Fingerprint method, route parameters, query, and normalized JSON
or form input after removing `api_token`. Store only:

```php
[
    'fingerprint' => $fingerprint,
    'status' => $response->getStatusCode(),
    'body' => $response->getData(true),
    'headers' => ['Content-Type' => 'application/json'],
]
```

Bind `IdempotencyStore` to `CacheIdempotencyStore`, alias middleware as
`api_v2_idempotency`, enforce key length 8-255, and use the configured 15-second
lock and 86400-second record TTL. Do not attach the middleware to legacy
mutations yet; each domain migration opts in after its contract test exists.
The store contract is exact:

```php
public function get(string $scope): ?array;
public function put(string $scope, array $record, int $ttlSeconds): void;
public function lock(string $scope, int $seconds): \Illuminate\Contracts\Cache\Lock;
```

- [ ] **Step 4: Run idempotency and envelope tests**

Expected: replay/conflict/concurrency cases pass without changing legacy routes.

- [ ] **Step 5: Commit idempotency kernel**

```bash
git add src/Api/V2 src/Http/Middleware src/Providers/WncmsServiceProvider.php tests/Feature/Api/V2
git commit -m "feat(api-v2): add idempotency enforcement"
```

---

### Task 8: Add Asynchronous Operation Contract And Cache Repository

**Files:**
- Create: `src/Api/V2/Enums/AsyncOperationStatus.php`
- Create: `src/Api/V2/Data/AsyncOperation.php`
- Create: `src/Api/V2/Contracts/OperationRepository.php`
- Create: `src/Api/V2/Repositories/CacheOperationRepository.php`
- Create: `src/Api/V2/OperationService.php`
- Create: `src/Http/Controllers/Api/V2/Backend/OperationController.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `routes/api/v2/backend.php`
- Test: `tests/Unit/Api/V2/OperationServiceTest.php`
- Test: `tests/Feature/Api/V2/OperationEndpointTest.php`

**Interfaces:**
- Consumes: cache, authenticated actor ID, website IDs, and domain services added in later stages.
- Produces: state machine, `GET /api/v2/backend/operations/{id}`, and `POST /api/v2/backend/operations/{id}/cancel`.

- [ ] **Step 1: Write failing state-machine and endpoint tests**

Use exact statuses:

```php
enum AsyncOperationStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
```

Test `queued -> running -> succeeded|failed`, cancellation only from queued or
running when `cancellable=true`, invalid transitions as `ApiConflictException`,
progress range 0-100, same-actor visibility, cross-actor 404, expired 404, and
idempotent cancellation.

- [ ] **Step 2: Run focused tests and verify failure**

Expected: operation types, repository, service, and endpoints are missing.

- [ ] **Step 3: Implement stable operation DTO and service**

`AsyncOperation` contains:

```php
public function __construct(
    public readonly string $id,
    public readonly string $type,
    public readonly AsyncOperationStatus $status,
    public readonly int $progress,
    public readonly bool $cancellable,
    public readonly int $actorId,
    public readonly array $websiteIds,
    public readonly mixed $result,
    public readonly ?array $error,
    public readonly string $createdAt,
    public readonly string $updatedAt,
    public readonly string $expiresAt,
) {}
```

`OperationService` exposes `queue()`, `start()`, `progress()`, `succeed()`,
`fail()`, `cancel()`, and `findForActor()`. Every transition returns a new DTO
and persists it through `OperationRepository`. Bind the interface to
`CacheOperationRepository` with the configured 86400-second TTL.

Use these exact public interfaces:

```php
public function queue(
    string $type,
    int $actorId,
    array $websiteIds = [],
    bool $cancellable = false
): AsyncOperation;
public function start(string $id): AsyncOperation;
public function progress(string $id, int $progress): AsyncOperation;
public function succeed(string $id, mixed $result = null): AsyncOperation;
public function fail(string $id, array $error): AsyncOperation;
public function cancel(string $id): AsyncOperation;
public function findForActor(string $id, int $actorId): ?AsyncOperation;

public function save(AsyncOperation $operation, int $ttlSeconds): void;
public function find(string $id): ?AsyncOperation;
public function forget(string $id): void;
```

Operation routes use request-ID, whitelist, token auth, and idempotency for
cancel, but not website middleware. No public create endpoint is added; future
domain services queue operations internally.

- [ ] **Step 4: Run operation and idempotency tests**

Expected: state, authorization, expiration, cancellation, and replay pass.

- [ ] **Step 5: Commit asynchronous operation contract**

```bash
git add src/Api/V2 src/Http/Controllers/Api/V2/Backend/OperationController.php src/Providers/WncmsServiceProvider.php routes/api/v2/backend.php tests
git commit -m "feat(api-v2): add asynchronous operation contract"
```

---

### Task 9: Enforce Registry, Route, And OpenAPI Consistency

**Files:**
- Create: `src/Api/V2/ApiContractValidator.php`
- Modify: `src/Console/Commands/CheckBackendApiV2Parity.php`
- Modify: `tests/Unit/CheckBackendApiV2ParityCommandTest.php`
- Create: `tests/Unit/Api/V2/ApiContractValidatorTest.php`

**Interfaces:**
- Consumes: registry, Laravel route collection, and generated OpenAPI document.
- Produces: `ApiContractValidator::validate(): array` and `wncms:check-backend-api-v2-parity --contract --json`.

- [ ] **Step 1: Write failing validator and command tests**

Inject fixtures for duplicate route bindings, missing routes, method/path
mismatches, missing backend permissions, invalid risk values, malformed schemas,
and OpenAPI operations absent from the registry. Assert the valid runtime with:

```php
$this->assertSame(0, $exitCode);
$this->assertSame('success', $decoded['status']);
$this->assertSame('api-v2-contract', $decoded['meta']['mode']);
$this->assertGreaterThan(0, $decoded['data']['operation_count']);
$this->assertSame([], $decoded['data']['errors']);
```

Invalid fixtures return CLI exit 1 and grouped machine-readable errors.

- [ ] **Step 2: Run focused tests and verify failure**

Expected: validator and `--contract` option do not exist.

- [ ] **Step 3: Implement deterministic validation**

Validate exact HTTP method, normalized path, route name, domain ownership,
permission presence for backend mutations, allowed risk/implementation values,
request/response schemas, unique operation IDs, and one-to-one OpenAPI coverage.
Add `--contract` without changing existing default or `--coverage` behavior.

- [ ] **Step 4: Run parity and OpenAPI checks**

```bash
vendor/bin/phpunit tests/Unit/Api/V2/ApiContractValidatorTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php tests/Unit/Api/V2/GenerateApiV2OpenApiCommandTest.php --testdox
```

Expected: all modes pass and JSON remains backward compatible.

- [ ] **Step 5: Commit contract enforcement**

```bash
git add src/Api/V2/ApiContractValidator.php src/Console/Commands/CheckBackendApiV2Parity.php tests/Unit
git commit -m "test(api-v2): enforce contract consistency"
```

---

### Task 10: Document The Kernel And Complete Verification

**Files:**
- Create: `documentations/manual/api/contracts.md`
- Create: `documentations/manual/api/capabilities.md`
- Create: `documentations/manual/api/openapi.md`
- Create: `documentations/manual/api/operations.md`
- Create: `documentations/manual/zh-CN/api/contracts.md`
- Create: `documentations/manual/zh-CN/api/capabilities.md`
- Create: `documentations/manual/zh-CN/api/openapi.md`
- Create: `documentations/manual/zh-CN/api/operations.md`
- Create: `documentations/manual/zh-TW/api/contracts.md`
- Create: `documentations/manual/zh-TW/api/capabilities.md`
- Create: `documentations/manual/zh-TW/api/openapi.md`
- Create: `documentations/manual/zh-TW/api/operations.md`
- Modify: `documentations/manual/api/overview.md`
- Modify: `documentations/manual/api/errors.md`
- Modify: `documentations/manual/zh-CN/api/overview.md`
- Modify: `documentations/manual/zh-CN/api/errors.md`
- Modify: `documentations/manual/zh-TW/api/overview.md`
- Modify: `documentations/manual/zh-TW/api/errors.md`
- Modify: `documentations/plans/v7-ai-first-coverage-matrix.md`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Consumes: completed runtime contract and verification commands.
- Produces: public developer documentation, synchronized changelogs, and evidence that the Contract Kernel is ready for the authentication/security stage.

- [ ] **Step 1: Write synchronized public documentation**

Document exact endpoint URLs, authentication requirements, envelope keys,
request/error IDs, capability filtering, all `x-wncms-*` OpenAPI extensions,
idempotency header behavior, `If-Match`, operation states/transitions, cache TTL,
and the provisional cache-backed operation limitation. State explicitly that
legacy operations marked `legacy_resource` or `legacy_bridge` are discoverable
but do not satisfy final v7 domain parity.

- [ ] **Step 2: Update the coverage matrix**

Mark the API v2 foundation as having a completed Contract Kernel while retaining
`Partial` overall status until all domain contracts migrate away from Bridge.
Replace stale concrete-next-task wording with Authentication/Security followed
by Links reference migration.

- [ ] **Step 3: Apply changelog skill and synchronize four changelogs**

Read `.github/skills/wncms-changelog-sync/SKILL.md`, then add one aligned
`v7.0.0-alpha1` bullet describing the registry, capabilities, OpenAPI, stable
errors/request IDs, idempotency/revision primitives, and operation contract.
Do not change version headings, installer metadata, or updater files.

- [ ] **Step 4: Run focused and full verification**

From the package directory:

```bash
vendor/bin/phpunit tests/Unit/Api/V2 tests/Feature/Api/V2 tests/Feature/LinkApiV2ControllerTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php --testdox
vendor/bin/phpunit
composer validate --strict
composer audit
git diff --check
```

From `/www/wwwroot/package.wncms.cc`:

```bash
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:check-backend-api-v2-parity --contract --json
php artisan route:list --path=api/v2
```

Expected: all tests pass, Composer metadata is valid, audit reports zero known
advisories, OpenAPI snapshot matches, contract validation exits 0, and routes
include capabilities, OpenAPI, and operations endpoints.

- [ ] **Step 5: Confirm protected and unrelated files remain untouched**

```bash
git status --short
git diff --name-only
git diff --cached --name-only
```

Expected: `updates/update_core_7.0.0.php` remains untracked and unstaged; no Farm,
Docker, Nginx, installer, or release files appear.

- [ ] **Step 6: Commit docs and verification metadata**

```bash
git add documentations/manual documentations/plans/v7-ai-first-coverage-matrix.md documentations/change
git commit -m "docs(v7): document API contract kernel"
```

---

## Completion Criteria

- `ApiContractRegistry` is the single source used by capabilities and OpenAPI.
- Existing API v2 URLs and Links behavior remain compatible.
- Every current configured v2 resource/action is represented and classified.
- Request IDs and stable error codes apply across all v2 middleware/controller responses.
- Query, JSON Schema, revision, and idempotency primitives have focused tests.
- Async operations have a stable state machine and replaceable cache repository.
- CI command detects route/registry/OpenAPI drift.
- English, Simplified Chinese, Traditional Chinese, and four changelogs are synchronized.
- Full PHPUnit, Composer validation/audit, runtime OpenAPI check, and contract check pass.
- No schema, installer, updater, Farm, Docker, Nginx, tag, push, or publication change occurs.
