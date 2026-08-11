# WNCMS v7 API-First Full-Parity Design

Date: 2026-08-11
Status: Approved design pending written-spec review

## Objective

WNCMS v7 must allow a developer to build both a public blog and a complete
administration dashboard without calling or parsing any Blade route. WNCMS Core
delivers the Laravel API only. It does not ship a Next.js application, BFF, or
TypeScript SDK.

API parity is measured by business capability and outcome, not by creating a
one-to-one JSON copy of every HTML route. The existing Blade frontend and
backend remain supported and must share business services, validation,
permissions, website scoping, cache behavior, hooks, and audits with the API.

## Approved Product Boundaries

- All current Blade backend business capabilities are required for v7.0 API
  parity, including privileged plugin, theme, update, cache, and tools actions.
- The supported protocol is REST JSON. GraphQL is not a v7.0 requirement.
- A Next.js server/BFF is the recommended consumer architecture, but its
  implementation is outside this repository.
- WNCMS provides a versioned OpenAPI 3.1 contract and runtime capability/schema
  discovery. Markdown documentation alone is not sufficient.
- Blade is enabled by default and can be disabled with one global system
  setting. When disabled, all WNCMS Blade frontend, backend, and web
  authentication routes return 404. API, CLI, queues, scheduled work, webhooks,
  and required non-HTML callbacks remain operational.
- Blade can be restored without a web UI through an explicit Artisan command.
- High-risk API actions execute directly after authorization by default. A
  system setting can switch them to a two-stage planned-confirmation policy.

## Architecture

### Public API Surfaces

WNCMS v7 exposes four formal API surfaces:

1. `/api/v2/frontend/*` provides public and preview content needed to build a
   complete blog.
2. `/api/v2/backend/*` provides authenticated administration and operations.
3. `/api/v2/capabilities` describes resources, fields, relationships, actions,
   permissions, schemas, enumerations, and availability in the current runtime
   context.
4. `/api/v2/openapi.json` provides the OpenAPI 3.1 contract.

OpenAPI and runtime capabilities must originate from, or be continuously
validated against, one API contract registry. OpenAPI describes the full
installed-system contract. Runtime capabilities describe the subset available
to the current installation, authenticated operator, enabled extensions, and
website scope.

### Internal Layers

The required call path is:

```text
API contract registry
  -> thin API controller
  -> domain service/action
  -> manager/model
```

Blade controllers call the same domain services/actions. Business rules must
not be copied between Blade controllers, API controllers, CLI commands, or MCP
tools.

The existing `BridgeController` remains a temporary compatibility layer. A
domain does not satisfy v7 parity while its formal contract depends on
normalizing an HTML redirect, view response, or untyped legacy controller
result through the bridge. Links remains backward compatible and becomes the
first complete domain-contract reference.

Plugins may register resources, actions, schemas, and capabilities through the
same registry without modifying core route files. An enabled plugin's declared
contract participates in runtime discovery and compatibility validation.

## Resource And Action Contract

Each domain explicitly declares:

- resources for standard `list`, `show`, `create`, `update`, and `delete`
  behavior;
- actions for domain behavior that is not naturally CRUD, including publish,
  restore, clone, synchronize, import, export, activate, deactivate, install,
  upgrade, test, and cache operations;
- permissions, token abilities, website-scope requirements, risk level,
  idempotency behavior, validation schemas, response schemas, and audit rules.

Formal API resources must use explicit Resource/DTO output definitions and
explicit writable-field lists. Uncontrolled Eloquent serialization and generic
mass assignment are not stable API contracts.

### Standard Response

Every API response uses the existing automation envelope:

```json
{
  "code": 200,
  "status": "success",
  "message": "Operation completed.",
  "data": {},
  "meta": {},
  "errors": {}
}
```

Errors have stable machine-readable identifiers and consistent HTTP semantics
for validation, authentication, authorization, missing targets, conflicts,
domain-rule failures, rate limits, and unexpected failures.

### Standard Resource Features

Every applicable resource contract defines:

- pagination, keyword search, filter, and sort allowlists;
- sparse field selection where it materially reduces payload size;
- an `include` allowlist for relationships;
- locale, translation, and website-context behavior;
- relationship reads and writes for tags, websites, roles, permissions, media,
  and other domain relations;
- create and update schemas with types, requirements, defaults, enumerations,
  constraints, and option sources;
- stable model, action, and permission keys independent of translated UI text;
- optimistic concurrency through ETag/`If-Match` or an equivalent revision;
- request/run identifiers and idempotency keys for retryable mutations.

The contract must provide all metadata required to build create and edit
screens. For example, the Post contract exposes its fields, validation rules,
statuses, visibilities, locales, selectable users/websites/tags, media support,
translation support, relations, and permitted actions. A client never has to
infer those values from Blade HTML.

## Authentication And Authorization

### Credential Types

The backend API supports:

- interactive administrator authentication for a server-side BFF, using a
  short-lived access token and rotating refresh token;
- personal/service tokens for CI, automation, and AI agents, with explicit
  abilities, website scope, expiration, rotation, and revocation.

New tokens must not default to permanent unrestricted `abilities: ["*"]`
credentials. Existing credentials receive a documented compatibility and
migration path.

API authentication covers all flows required when Blade is disabled: login,
logout, refresh, current user, forgotten/reset password, email verification,
profile/email/password updates, and token listing, creation, rotation, and
revocation.

### Request Guard Order

Every backend request applies these checks in order:

1. token validity and ability;
2. WNCMS permission;
3. website scope;
4. input validation and concurrency state;
5. risk policy and idempotency;
6. domain service execution;
7. mutation audit and unified response rendering.

Permission and website-scope denial must fail closed. Runtime capabilities hide
unavailable operations while still returning structured disabled reasons where
that information is safe for the authenticated operator.

### High-Risk Actions

The global `api_high_risk_action_mode` system setting has two modes:

- `direct` is the default. A request executes after all normal authentication,
  permission, website-scope, validation, concurrency, and idempotency checks.
- `planned` requires a preflight/plan request. The server returns a short-lived
  confirmation token, impact summary, and target-state fingerprint. Execution
  is rejected if the actor, normalized input, target state, or token validity
  differs from the approved plan.

Permission checks, website scope, idempotency protection, and mutation audit
cannot be disabled in either mode.

### Mutation Audit

All successful and failed backend mutations carry a request/run ID. Sensitive
values are redacted before audit persistence. Audits record actor, token or
surface context, permission, website scope, domain/action, target, normalized
input summary, result, and timing. Transactional domain writes and their
success audits follow the established mutation-audit contract.

## API-Only Blade Mode

The default-enabled `blade_enabled` core system setting controls Blade
availability. It is stable, documented, cached safely, and included in default
installation settings.

When disabled:

- every WNCMS-owned HTML/Blade frontend, backend, and web-auth route returns
  404;
- APIs, Artisan commands, queue workers, schedulers, webhooks, and required
  non-HTML callbacks continue to work;
- installation and upgrade logic retain a CLI recovery path even if setting
  cache or normal web routing is unavailable.

The operator interface includes dedicated `status`, `disable --force`, and
`enable` Artisan commands. Re-enabling Blade never depends on an HTTP route.

## Required Domain Coverage

### Backend Foundation

- authentication and token lifecycle;
- current user and dashboard data;
- i18n and translations;
- capabilities and OpenAPI;
- uploads and media.

### Content And Editorial

- Posts, Pages, Page Builder, Tags, Menus, Links, Comments;
- Advertisements, Search Keywords, Channels, Parameters, Clicks;
- all existing bulk, clone, restore, import, export, relationship-sync, and
  test actions.

### Sites, Identity, And Configuration

- Websites, domain aliases, theme options, current-site switching, and
  multisite relationships;
- Users, Roles, Permissions, profile/security settings, and API tokens;
- all Settings groups, quick links, provider tests, and configuration actions.

### Extensions And Operations

- Packages, Plugins, Themes, Updates, Tools, Cache, and Records;
- upload, install, activate, deactivate, upgrade, delete, check, rerun, clear,
  and diagnostic operations exposed by the current backend.

### Public Blog API

The frontend API supports website resolution by domain, stable website key, or
explicit context. It exposes published posts, pages, tags, menus, links, public
comments, media, public website settings, locale/translation data, SEO data,
canonical information, pagination, filters, and declared relationships.

Drafts, private content, and private settings are excluded by default. Preview
uses a separate short-lived and scope-limited credential. Backend permissions,
secrets, internal settings, and undeclared model fields must not leak through
frontend resources.

## Long-Running Operations

Plugin upgrades, core updates, large imports, and other long operations return
an operation resource instead of reporting completion before work finishes.
The standard lifecycle is:

```text
queued -> running -> succeeded | failed | cancelled
```

Operation resources expose progress, structured logs, result data, failure
codes, timestamps, actor/scope context, and cancellation capability when the
underlying operation is safely cancellable.

## Parity And Compatibility Enforcement

API parity is enforced through a domain capability manifest rather than a
route-name clone. Every Blade business capability maps to an API
resource/action or is explicitly classified as presentation-only and not
applicable.

CI compares:

- backend capability inventory;
- API contract registry and runtime routes;
- OpenAPI operation and schema coverage;
- permissions and website-scope declarations;
- contract tests and public documentation.

Any required capability missing a contract, permission, test, or documentation
entry fails the v7 release gate. Enabled plugins are validated against the
capabilities they declare.

The stable URL namespace remains `/api/v2`. After v7 stable, v2 cannot remove a
field, change a field type, or reinterpret an existing action. Additive fields,
relations, and actions remain allowed. A necessary future breaking change
requires deprecation metadata, response headers, a migration guide, and a later
major API version. Versioned OpenAPI snapshots detect accidental breaks.

## Verification Strategy

Every resource and action has tests for success, validation failure,
unauthenticated access, missing permission, invalid website scope, and missing
targets. Applicable mutation tests also verify audit data, cache invalidation,
hooks, relationship synchronization, idempotency, concurrency, and transaction
rollback.

Cross-cutting suites verify:

- both `direct` and `planned` high-risk modes;
- public draft/private-data isolation, preview scope, locale behavior, and
  cross-website isolation;
- OpenAPI, runtime routes, schemas, permissions, and capabilities agree;
- disabling Blade makes all WNCMS HTML routes return 404 while API, CLI, queue,
  scheduling, and the recovery command remain usable;
- a pure-API acceptance flow can authenticate, create a website and authorized
  users, configure the site, upload media, build complete blog content and
  navigation, read the public API, and perform privileged operational actions
  without calling a Blade route.

No Next.js test application is required.

## Delivery Decomposition

This program spans independent security, contract, content, administration,
operations, and public-delivery subsystems. It must not be executed as one
unreviewable implementation plan. Each stage receives its own focused spec or
implementation plan and produces independently testable software:

1. Contract kernel: registry, DTO/resources, schemas, capabilities, OpenAPI,
   unified errors, idempotency, concurrency, and operation resources.
2. Authentication/security: access/refresh and service tokens, website scope,
   risk policy, and the Blade global switch/recovery commands.
3. Links reference migration: full domain contract with no formal Bridge
   dependency.
4. Content APIs: content/editorial domains and media.
5. Site/admin APIs: websites, identity, permissions, and settings.
6. Operations APIs: extensions, updates, tools, cache, and records.
7. Public API: full blog delivery, preview, SEO, locale, and relations.
8. Enforcement: parity CI, compatibility snapshots, complete pure-API
   acceptance flow, documentation, and upgrade guidance.

Later stages consume only stable interfaces produced by earlier stages. A stage
cannot be marked complete while its formal API still relies on Bridge response
normalization.

## v7.0 Release Gate

v7.0 is API-ready only when:

- every v7-required capability in the coverage manifest is complete;
- no formal domain contract depends on BridgeController;
- OpenAPI, runtime capabilities, routes, permissions, schemas, and tests agree;
- the pure-API acceptance flow passes;
- public documentation and the default locale set are synchronized for stable
  behavior;
- existing Blade behavior and tests remain compatible while Blade is enabled;
- API-only mode and CLI recovery are verified;
- security review, Composer audit, installation, and upgrade verification pass.

Release tagging, publishing, updater changes, and deployment remain separately
authorized release actions.
