# WNCMS v7 Authentication And Security Design

Date: 2026-08-13
Status: Approved conversational design pending written-spec review

## Objective And Scope

This stage supplies the authentication and security foundation required for a
developer to build a complete Next.js administration dashboard and public blog
using only WNCMS REST JSON APIs and OpenAPI 3.1. WNCMS does not build or ship the
Next.js application.

The stage covers interactive authentication, rotating refresh credentials,
service tokens, explicit website scope, risk policy, API-only Blade mode,
legacy credential compatibility, security events, contract discovery, tests,
and public documentation. Blade remains enabled by default. Farm, Docker, DNS,
live-site deployment, release/tagging, and Next.js implementation are out of
scope.

The Contract Kernel at `28857ada3dadeaa8ff4f56c1e847420f19a7edc1` is the
foundation for this work. Authentication and security operations must become
formal registry operations rather than permanent contract exclusions.

## Locked Product Decisions

- WNCMS owns `api_sessions`, `api_access_tokens`, `api_refresh_tokens`,
  `api_service_tokens`, and `api_security_events`.
- WNCMS does not force Sanctum or extend a host-owned
  `personal_access_tokens` schema.
- Interactive clients support JSON and Secure HttpOnly Cookie refresh delivery;
  JSON is the default.
- Access tokens default to 15 minutes and refresh tokens to 30 days; both are
  configurable.
- Policy may permit permanent `remember_me`; permanent refresh credentials
  remain revocable and rotate on every refresh.
- Reuse of a rotated refresh token revokes its entire family/session.
- Devices have independent sessions and can be revoked individually or all at
  once.
- Password change or reset revokes every interactive, service, v1 user token,
  and precisely attributable legacy PAT for that user.
- Cookie domain is host-only by default, with an optional validated shared
  parent domain.
- Identity is installation-wide. Domain changes never create another account or
  data set. Token scope binds to stable website IDs/keys, never domains.
- Service tokens require explicit website scope, safe ability templates, an
  explicit expiry, and additional permissions for cross-site or permanent use.
- Supported service-token templates are `read_only`, `content_editor`,
  `site_manager`, and `full_admin`.
- Service-token expiry choices are 30, 90, or 365 days, or permanent. Permanent
  creation requires `api_token_create_permanent`.
- Login throttling combines account and IP limits with configurable progressive
  delay.
- Blade is enabled by default. When disabled, WNCMS-owned frontend, backend,
  web-auth, and other Blade routes return 404; CLI can restore them.
- Legacy credentials never bypass current WNCMS permissions or website access.

## Stable Settings And Permissions

The following system-setting keys and defaults are part of the stable package
contract. Invalid values are rejected on write. Security-critical runtime reads
fail closed unless a more specific recovery rule in this design applies.

| Key | Default | Allowed values |
| --- | --- | --- |
| `api_access_token_lifetime_minutes` | `15` | integer 1-60 |
| `api_refresh_token_lifetime_days` | `30` | integer 1-365 |
| `api_refresh_transport` | `json` | `json`, `cookie` |
| `api_permanent_remember_enabled` | `false` | boolean |
| `api_refresh_cookie_domain` | empty | empty host-only value or validated parent domain |
| `api_refresh_cookie_same_site` | `lax` | `strict`, `lax`, `none` |
| `api_refresh_cookie_allowed_origins` | empty | exact newline-separated origins |
| `api_refresh_cookie_referer_fallback` | `false` | boolean |
| `api_login_account_attempts` | `5` | integer 1-100 |
| `api_login_ip_attempts` | `30` | integer 1-1000 |
| `api_login_window_minutes` | `15` | integer 1-1440 |
| `api_login_progressive_delay_seconds` | `1,2,4,8,16,30` | ascending comma-separated integers, each 0-300 |
| `api_high_risk_action_mode` | `direct` | `direct`, `planned` |
| `api_action_plan_lifetime_seconds` | `300` | integer 60-900 |
| `api_step_up_lifetime_seconds` | `300` | integer 60-900 |
| `blade_enabled` | `true` | boolean |
| `api_legacy_personal_tokens_enabled` | fresh install `false`; upgraded install `true` | boolean |
| `api_legacy_personal_tokens_cutoff_at` | fresh install empty; upgraded install upgrade time + 90 days | nullable UTC datetime |
| `api_security_event_retention_days` | `90` | integer 30-365 |

Cookie transport cannot be enabled until at least one exact allowed Origin is
configured. `SameSite=None` additionally requires a valid HTTPS deployment.
Changing refresh transport invalidates interactive sessions. Lifetime changes
apply to newly issued credentials and do not extend existing ones. Reducing a
lifetime does not rewrite existing expiry, but administrators may revoke
sessions explicitly. Changes to Cookie domain, SameSite, or Origin policy revoke
Cookie-mode sessions because their original browser boundary is no longer the
configured boundary.

Progressive delay uses the listed delay for consecutive failures, capped at the
last value; the account/IP attempt limits independently block further attempts
for the configured window. Successful login clears the account failure state
for that account but does not erase the shared IP abuse state.

Security-event correlation HMAC keys are deployment secrets supplied through
versioned configuration/environment, not database system settings or backend
forms. Missing or invalid HMAC key configuration fails closed for credential
issuance and security mutations.

The stage introduces these exact permissions:

```text
api_token_create
api_token_create_cross_site
api_token_create_permanent
api_token_index
api_token_show
api_token_rotate
api_token_revoke
security_event_index
security_event_show
blade_mode_manage
```

Self-service profile, email, password, and session actions operate only on the
authenticated user and require their declared interactive token ability rather
than an administrative user-management permission. Action-plan creation inherits
the target operation's permission and never grants access by itself.

Templates are ceilings over actor-grantable operation abilities:

- `read_only` contains safe read abilities only and excludes credential/security
  event access unless separately grantable and deliberately selected.
- `content_editor` adds content/media create, edit, publish, and relationship
  abilities within explicit websites, but excludes site/security/credential and
  extension operations.
- `site_manager` adds website-scoped configuration and user/content management
  abilities but excludes credential management, core update, and executable
  plugin/tool operations.
- `full_admin` contains all abilities the actor may delegate for non-credential
  operations within the selected websites. It still cannot bypass permission,
  website scope, step-up, or risk policy and never enables credential management
  by a service token.

Fine-grained additions/removals are validated against the actor's delegable
operation abilities and the selected template. The registry is the canonical
ability catalog; unknown abilities fail validation.

## Credential And Session Model

### `api_sessions`

One row represents one interactive login/device and token family. It stores an
opaque public ID, user, device display name, refresh transport, permanent-login
state, last activity, security state, and revocation metadata. It enables
current-device identification, individual revocation, and logout-all.

### `api_access_tokens`

Access tokens are short-lived, hash-only, and related to a session and user.
They carry abilities and website scope. Validation enforces type, hash, expiry,
revocation, active user/session, required ability, WNCMS permission, and stable
website scope.

### `api_refresh_tokens`

Refresh tokens are hash-only, one-time credentials related to a session/family.
Every successful refresh atomically consumes the old token and creates a new
one. Reuse of a consumed token revokes the whole family. The lifecycle is the
same for JSON and Cookie delivery.

### `api_service_tokens`

Service tokens are separate from interactive sessions. They contain an opaque
public ID, hash, owner, name, template/effective abilities, explicit website
scope, expiry, revocation, and last-used metadata. Plaintext is returned only
on creation or rotation.

### Credential format

New credentials use unambiguous prefixes:

```text
wncms_at_<opaque_id>.<secret>
wncms_rt_<opaque_id>.<secret>
wncms_st_<opaque_id>.<secret>
```

The opaque ID is non-sequential and the secret is stored only as a hash. A
failed lookup for one prefix never falls back to another credential store.

## Authentication And Security API

### Interactive authentication

```text
POST /api/v2/backend/auth/login
POST /api/v2/backend/auth/refresh
POST /api/v2/backend/auth/password/forgot
POST /api/v2/backend/auth/password/reset
POST /api/v2/backend/auth/email-verification/send
POST /api/v2/backend/auth/email-verification/verify

GET    /api/v2/backend/auth/me
POST   /api/v2/backend/auth/logout
POST   /api/v2/backend/auth/logout-all
GET    /api/v2/backend/auth/sessions
DELETE /api/v2/backend/auth/sessions/{session_id}
PATCH  /api/v2/backend/auth/profile
POST   /api/v2/backend/auth/email/change
POST   /api/v2/backend/auth/email/change/confirm
PATCH  /api/v2/backend/auth/password
POST   /api/v2/backend/auth/reauthenticate
```

`me` returns installation-wide identity, roles, effective permissions,
accessible websites, current-session metadata, email-verification state, and
safe authentication capabilities. Public session IDs are opaque.

Forgot-password always returns the same accepted response whether the account
exists. Reset and verification credentials are opaque, expiring, single-use,
and hash-only where persisted. Email links target a configured client callback;
the client submits the credential to the API, so Blade is unnecessary. An email
change keeps the old address active until the new address is verified and
notifies the old address.

Authentication/security owns the stable current-user routes and all credential
effects. A later identity stage may extend profile fields but may not rename or
reinterpret these routes. Administrative user CRUD, role/permission assignment,
suspension, and website membership belong to the later identity API.

Password change/reset revokes all credential types, including the credential
performing the action, and returns `reauthentication_required: true`.
`logout-all` revokes interactive sessions only; it does not revoke service
tokens.

### Service-token management

```text
GET    /api/v2/backend/auth/service-token-options
GET    /api/v2/backend/auth/service-tokens
POST   /api/v2/backend/auth/service-tokens
GET    /api/v2/backend/auth/service-tokens/{token_id}
POST   /api/v2/backend/auth/service-tokens/{token_id}/rotate
DELETE /api/v2/backend/auth/service-tokens/{token_id}
```

The options endpoint returns only templates, abilities, websites, and expiry
choices the current actor may grant. Requested abilities cannot exceed the
actor's grantable abilities. Website selection is mandatory; multiple websites
require a cross-site permission. Permanent expiry requires
`api_token_create_permanent`.

Create and rotate expose plaintext once. List and inspect never expose
plaintext, hashes, or useful secret fragments. Rotation atomically invalidates
the old secret while preserving metadata. An unauthorized lookup of another
user's token returns the same 404 as an unknown token.

## Refresh Transport And Browser Security

The access token is always returned in JSON and used as a Bearer token. WNCMS
never puts it in a cookie.

### JSON mode

JSON mode is the default and recommended Next.js BFF mode. Login and refresh
return `access_token`, `token_type`, `access_expires_at`, `refresh_token`,
`refresh_expires_at`, `session`, and `user`. The refresh token is submitted in
the JSON body of refresh/logout. WNCMS neither sets nor reads refresh/CSRF
cookies in this mode.

### Cookie mode

Cookie mode returns access-token metadata in JSON but never refresh plaintext.
It sets:

```text
Name:     __Secure-wncms_refresh
Path:     /api/v2/backend/auth
Domain:   omitted by default
Secure:   true
HttpOnly: true
SameSite: Lax by default
```

It also sets a non-secret, non-HttpOnly double-submit cookie:

```text
Name:     wncms_refresh_csrf
Path:     /api/v2/backend/auth
Domain:   same policy as the refresh cookie
Secure:   true
SameSite: same as the refresh cookie
```

`__Secure-` is used because the approved optional parent-domain mode is
incompatible with `__Host-`. Configuration may explicitly choose a validated
shared parent domain, `Strict`/`Lax`/`None`, and exact allowed Origins.
`SameSite=None` requires HTTPS, `Secure`, credentialed CORS, and a nonempty exact
Origin allowlist. Wildcard Origin is forbidden with credentials.

JSON mode never accepts cookies. Cookie mode never accepts a body refresh token.
A mismatch returns `authentication.refresh_transport_mismatch`. Changing the
configured mode invalidates outstanding interactive sessions.

Cookie-mode login, refresh, and logout require exact Origin validation to
prevent login CSRF. Refresh/logout also require `X-WNCMS-CSRF` matching the
CSRF cookie and server-bound session value. Origin comparison includes scheme,
host, and effective port. Missing, malformed, wildcard, `null`, or unapproved
Origins fail closed. Referer is an optional compatibility fallback only when
explicitly enabled.

The API IP/domain whitelist remains a separate deployment gate, not CORS or
CSRF protection. Logout clears both cookies. A syntactically valid logout has a
stable success envelope even if already revoked. Refresh replay is a security
event that revokes the family, not ordinary invalid input.

## Request Guard Order

Normal backend operations run in this order:

1. request ID and response-envelope boundary;
2. global API availability and network/IP whitelist;
3. credential extraction and type resolution;
4. hash, expiry, revocation, user, and session validity;
5. token ability;
6. current WNCMS permission;
7. website resolution and user/token website scope;
8. validation and optimistic concurrency;
9. risk policy and idempotency;
10. domain transaction;
11. redacted audit persistence;
12. unified response rendering.

Abilities are credential ceilings, never replacements for permissions. Website
scope resolves only from stable website ID/key; a request domain never expands
scope. Authentication-specific pipelines retain this ordering as applicable,
with login throttle before credential validation and Cookie Origin/CSRF before
refresh rotation.

## Stable Failures

The standard v2 envelope and request ID apply to all failures. Stable codes
include:

```text
authentication.missing_token
authentication.invalid_credentials
authentication.invalid_token
authentication.access_token_expired
authentication.token_revoked
authentication.refresh_invalid
authentication.refresh_expired
authentication.refresh_reuse_detected
authentication.refresh_transport_mismatch
authentication.session_revoked
authentication.email_verification_required
authentication.csrf_failed
authentication.origin_denied
authentication.rate_limited
authentication.legacy_token_expired
authentication.legacy_revocation_failed
authorization.ability_denied
authorization.permission_denied
website.scope_missing
website.scope_denied
validation.failed
resource.not_found
risk.plan_required
risk.plan_invalid
risk.plan_expired
risk.plan_stale
risk.confirmation_reused
risk.step_up_required
risk.step_up_invalid
risk.step_up_expired
risk.credential_type_denied
risk.policy_unavailable
security.audit_unavailable
```

Use 400 for malformed exchanges/transport mismatch; 401 for absent, invalid,
expired, revoked, or replayed credentials and invalid step-up; 403 for ability,
permission, Origin, CSRF, website scope, or credential-type denial; 404 for
unknown/undisclosable resources; 409 for stale/used plans and concurrency;
422 for validation; 428 for missing required plan/step-up; 429 for throttling;
and 503 when security state cannot be loaded safely. Login, forgot-password,
and undisclosable lookups use generic messages.

## Risk Policy

### Security risk classification

Every formal operation declares:

```text
security_risk = normal | sensitive | high | critical
```

This differs from the existing Contract Kernel `risk` field, which continues to
describe data effects such as `read`, `write`, or `destructive`. Effective
security risk is the maximum of the contract baseline, input escalation, and
environment escalation; requests can never lower it.

Permanent tokens are at least critical. Cross-site or broad/full-admin grants
are at least high. Permanent, cross-site, broad grants together are critical.
Credential/security changes are at least sensitive. Bulk deletion, updates,
plugins, and tools declare high/critical as appropriate. State change between
plan and execution makes the plan stale.

### Direct and planned modes

`api_high_risk_action_mode` is `direct` by default or `planned`. Direct mode
executes after every normal guard, step-up, idempotency, and audit check. Planned
mode requires high/critical operations to create:

```text
POST /api/v2/backend/action-plans
```

The plan returns an opaque ID, operation, effective risk, impact, warnings,
expiry, and confirmation token. The execution supplies
`X-WNCMS-Confirmation`. The confirmation is single-use, defaults to five
minutes, and binds actor, credential/session, operation, normalized input hash,
target fingerprint, website scope, permission/ability requirements, effective
risk, and expiry. Any change returns `risk.plan_stale` and requires a new plan.

### Recent reauthentication

Credential/security operations require a purpose-bound step-up proof even in
direct mode. `POST /api/v2/backend/auth/reauthenticate` accepts the current
password from an interactive session and returns a short-lived proof. The proof
defaults to five minutes, binds user/session/purpose, is consumed by a
successful sensitive action, and is invalidated by password change, revocation,
or security events. Failures are account+IP throttled. Service tokens cannot
reauthenticate.

Service tokens may perform non-credential high/critical operations when their
ability, permission, website scope, and direct/planned policy allow it. They may
not manage sessions, passwords, email, or other credentials. Legacy tokens may
not manage credentials or execute critical operations.

### Idempotency and races

Service-token create/rotate, session revoke, logout-all, and all high-risk
mutations require `Idempotency-Key`. Confirmation consumption uses an atomic
lock. Async enqueue consumes the confirmation and subsequent retries use the
operation/idempotency result.

Plaintext-once responses may be replayed only for the same actor, session, and
idempotency key within a maximum five-minute encrypted replay window. After the
window, the secret must be rotated rather than displayed again.

## Blade API-Only Mode And Recovery

### Setting and middleware

`blade_enabled` is a stable boolean core setting, true by default. Missing on an
existing installation means true. A strict reader distinguishes `found`,
`missing`, `invalid`, and `unavailable`; invalid/unavailable means false for an
installed system and records a security event.

All WNCMS-owned frontend, backend, web-auth, fallback, localized, custom
frontend/backend extension, and plugin Blade routes use one availability
middleware. It runs before session, auth, website, locale redirect, controller,
and hook execution. Disabled routes return an ordinary 404 for every method,
without redirects or explanatory HTML.

Route registration is not conditional, so route cache and long-lived processes
retain correct behavior. The middleware reads authoritative database state per
request (with request-local memoization), not the ordinary one-hour setting
cache.

API, CLI, queues, scheduler, MCP/non-HTML automation, webhook, payment/provider
callbacks, OAuth callbacks, `custom_api.php`, and host-owned routes remain
available. Plugin web routes are Blade-controlled unless explicitly declared
and validated as required non-HTML callbacks. Static public assets are not
Laravel routes and are not deleted.

The installer remains available only while installation is demonstrably
incomplete. Blade disable never reopens it.

### Management API and CLI

```text
GET   /api/v2/backend/security/blade
PATCH /api/v2/backend/security/blade

php artisan wncms:blade:status [--json]
php artisan wncms:blade:disable --force [--json]
php artisan wncms:blade:enable [--json]
```

The API requires interactive access, `blade_mode_manage`, step-up, idempotency,
and the risk policy. Disable is critical and enable is high. The successful
disable response completes before later HTML requests become 404.

CLI status reports installation state, authoritative/effective values, policy
health, cache consistency, and last change when available. Disable requires
`--force`; enable is the unauthenticated CLI recovery path and needs no force.
All commands are idempotent and verify the authoritative value.

Writes use `uss()`/SettingManager, not direct model queries. If cache flush
fails after a verified database write, the command warns but the authoritative
middleware uses the new state. A failed write/verification exits nonzero.
Emergency `wncms:blade:enable` may succeed when security-audit persistence is
unavailable, with a structured fallback log and explicit warning.

## Legacy Compatibility And Upgrade

### Legacy PAT window

New v7 installations default legacy PAT acceptance to disabled. Existing
installations receive:

```text
api_legacy_personal_tokens_enabled = true
api_legacy_personal_tokens_cutoff_at = upgrade time + 90 days
```

The ordinary maximum is 365 days. A longer period requires an explicit CLI
override with force and high-risk audit. After cutoff WNCMS returns
`authentication.legacy_token_expired` but does not delete host records.

Legacy PAT validation requires a valid hash/user, optional host expiry,
unexpired global compatibility, explicit `legacy_token_allowed=true`, current
WNCMS permission, current user website access, and a non-prohibited operation.
`abilities=["*"]` or null means only all explicitly legacy-compatible operations;
it never bypasses permission/scope. One request selects one website and cannot
perform cross-site changes. Unspecified legacy compatibility is deny-by-default.

The adapter requires `id`, `tokenable_type`, `tokenable_id`, and `token`.
`abilities`, `expires_at`, `last_used_at`, `created_at`, and `name` are optional
and handled conservatively. Missing required columns fail closed. WNCMS never
adds, removes, or changes PAT columns/indexes, assumes a Sanctum version,
auto-copies hashes, or issues new v7 credentials into that table.

Successful legacy responses include `Deprecation`, `Sunset`, a migration
`Link`, and safe credential-type metadata.

### Legacy CLI

```text
php artisan wncms:auth:legacy-status [--json]
php artisan wncms:auth:legacy-cutoff {datetime} [--json]
php artisan wncms:auth:legacy-revoke-all --force [--json]
```

Status reports policy/schema compatibility and aggregate usage without secrets.
Cutoff accepts timezone-aware input stored in UTC; past means immediate disable.
Extending beyond 365 days requires explicit override/force. Revoke-all disables
WNCMS acceptance and sets cutoff to now without deleting host rows.

No automatic plaintext migration exists because old hashes cannot be reversed.
Administrators create a scoped new service token, deploy and validate it, then
disable legacy acceptance. Host record cleanup remains the host application's
responsibility.

### Password revocation exception

Password change/reset revokes all WNCMS credentials, sets `users.api_token` to
null, and deletes only PAT rows whose tokenable class and ID precisely match the
user. This is the only default core security event that deletes host PAT rows.
It is transactional and documented as affecting host Sanctum/PAT sessions. If
the schema cannot identify rows safely, the action rolls back with
`authentication.legacy_revocation_failed`.

API v1 `users.api_token` remains a separate deprecated mechanism. It is never
accepted by v2, is nulled on password change/reset, and follows the later v1
deprecation/removal policy.

### Database delivery

Fresh installations use complete base create migrations for the four credential
tables and `api_security_events`; no additive core migrations are used.
Existing installations use an explicitly authorized, guarded, idempotent v7
update script. It creates missing WNCMS-owned structures/settings/permissions,
does not migrate PAT hashes, aborts on incompatible same-name tables, and only
updates `core_version` after complete validation.

The protected `updates/update_core_7.0.0.php` is not authorized for access or
modification by this design. Implementation of the existing-install update path
requires separate explicit authorization.

## Security Audit And Observability

### `api_security_events`

Security events are mandatory and independent of optional mutation auditing.
The append-only store records opaque event ID, UTC time, stable type, severity,
outcome, surface, request/run IDs, actor/target IDs, credential/session public
IDs, website IDs, error/status, HMAC correlations, allowlisted context, and an
optional mutation-audit reference.

It never stores passwords, credential plaintext/hash, Authorization/Cookie
headers, CSRF, proofs, confirmations, reset/verification credentials, provider
secrets, or raw request bodies. Builders accept allowlisted fields only, then a
shared recursive redactor rejects password/token/secret/proof/confirmation/
authorization/cookie/CSRF/API-key keys.

IP, normalized login identifier, and User-Agent use separate versioned
server-side HMAC keys. Raw IP is not stored in this database. Device display
names are safe and truncated.

Required event families cover login/throttle, refresh/replay, logout/session,
password/email/verification, step-up, service-token lifecycle, legacy policy,
risk plans, security denials, and Blade policy/change events. Critical events
are always individual. High-volume low-value failures may be aggregated with
first/last time, count, correlation hashes, and latest request ID.

The stable event catalog includes at least:

```text
auth.login.succeeded
auth.login.failed
auth.login.throttled
auth.refresh.succeeded
auth.refresh.failed
auth.refresh.reuse_detected
auth.logout.succeeded
auth.logout_all.succeeded
auth.session.revoked
auth.password.changed
auth.password.reset_requested
auth.password.reset_succeeded
auth.email_change.requested
auth.email_change.confirmed
auth.email_verified
auth.step_up.succeeded
auth.step_up.failed
auth.service_token.created
auth.service_token.rotated
auth.service_token.revoked
auth.legacy.accepted
auth.legacy.rejected
auth.legacy.cutoff_changed
auth.legacy.disabled
risk.plan.created
risk.plan.confirmed
risk.plan.stale
risk.confirmation.reused
security.csrf.denied
security.origin.denied
security.ability.denied
security.permission.denied
security.website_scope.denied
security.blade.enabled
security.blade.disabled
security.blade.policy_unavailable
security.retention.completed
```

Forgot-password has the same external response for existing and unknown
accounts. Event-query authorization and returned fields must not turn internal
event differences into an account-existence oracle.

### Transaction and failure policy

Credential issue/rotation/revocation, refresh replay, session actions,
password-wide revocation, email confirmation, plan consumption, Blade disable,
and legacy policy mutations commit with their security event in one database
transaction. Audit failure rolls back the mutation and returns
`security.audit_unavailable`.

Authentication failures with no state change still return their generic result
and use a fallback structured log if event persistence fails. Emergency CLI
Blade enable is the only mutation exception and prioritizes recovery.

Session/service last-used metadata updates at most once per five minutes and
never extends expiry. Failure does not break an ordinary authorized read but
emits a fallback warning. Critical mutations retain transactional audit.

### Query, retention, and signals

```text
GET /api/v2/backend/security/events
GET /api/v2/backend/security/events/{event_id}
```

The read-only API uses explicit DTOs, allowlisted filters, 404 for undisclosable
events, `security_event_index`/`security_event_show`, and actor website scope.
There is no update/delete API or arbitrary JSON search.

`api_security_event_retention_days` defaults to 90 and permits 30-365 days.
Scheduled batched cleanup emits an aggregate completion event/log. WNCMS emits
redacted structured Laravel events/logs and non-personal counters, but does not
bind v7.0 to a particular email, Slack, or monitoring provider.

Append-only means ordinary API/domain code cannot update or delete individual
events; scheduled retention cleanup is the sole normal deletion path.

## Capabilities And OpenAPI 3.1

All authentication/security routes are formal registry operations with unique
operation IDs, schemas, permission, ability, credential types, website scope,
data `risk`, `security_risk`, idempotency, step-up, action-plan, and legacy
metadata. Auth routes are removed from the permanent contract exclusion list.

Operation metadata adds:

```text
security_risk
accepted_credential_types
requires_step_up
step_up_purposes
action_plan_eligible
legacy_token_allowed
website_scope_mode
idempotency_required
```

Credential types are `interactive_access`, `service_token`, and
`legacy_personal_access_token`. Website scope is `none`, `single`, or
`multiple_explicit`. Missing/invalid declarations are contract errors.
Credential-management operations accept only interactive access. Critical
operations cannot accept legacy credentials.

OpenAPI retains `bearerAuth` for access/service bearer tokens and adds
`refreshCookie` plus `refreshCsrf`. Cookie refresh/logout require both. JSON
refresh uses a `writeOnly` request property. Login/refresh success uses `oneOf`
for JSON and Cookie modes; Cookie responses contain no refresh field.

Vendor extensions add:

```text
x-wncms-security-risk
x-wncms-credential-types
x-wncms-requires-step-up
x-wncms-step-up-purposes
x-wncms-action-plan-eligible
x-wncms-legacy-token-allowed
x-wncms-website-scope-mode
x-wncms-idempotency-required
x-wncms-refresh-transports
```

Capabilities include safe runtime authentication policy and actor-aware
operation availability. Permission/ability-invisible operations are omitted.
Credential-type or website-context incompatibility produces safe disabled
reasons. Step-up and planned requirements remain discoverable. Dynamic input
risk appears only after input is supplied to options/plan execution.

`service-token-options` provides actor-specific templates, abilities, websites,
expiry, and grant permissions. Installation-specific option values are not
hardcoded into OpenAPI.

Contract validation rejects route/registry/OpenAPI drift, bad risks/types,
critical+legacy combinations, credential-management service tokens, missing
step-up purposes, middleware/idempotency drift, missing Cookie CSRF/Origin
metadata, nonexistent permissions/abilities, scope schema errors, refresh leaks,
non-`writeOnly` secret inputs, and invalid ability templates.

This is additive under `/api/v2`; OpenAPI stays 3.1.0 and contract
`schema_version` becomes `2.1.0`. The exact snapshot is updated. OpenAPI may use
a deterministic ETag. Capabilities and credential/security responses are
`private, no-store`; capabilities also vary by Authorization and must not be
shared across user, credential, or website context.

## Testing And Acceptance

Implementation follows strict TDD in small slices: primitives, schema/repos,
guards, JSON flow, Cookie flow, sessions, service tokens, risk, Blade mode,
legacy, security events, contracts, and cross-cutting regression.

Required coverage includes:

- hash-only storage, prefix isolation, constant-time comparison, fixed-clock
  expiry, and secret-free responses/logs/errors;
- generic login failure behavior, dummy-hash execution, account+IP progressive
  throttling, trusted-proxy and storage-failure policy;
- JSON/Cookie refresh, permanent policy, atomic concurrent rotation, family
  replay revocation, strict transport separation, and Cookie/Origin/CSRF matrix;
- independent sessions, revoke/logout-all, password-wide revocation, email
  change, last-activity debounce, and opaque cross-user 404s;
- all templates, grant ceilings, explicit website scope, cross-site/permanent
  permissions, rotation, idempotent plaintext replay window, and domain-change
  scope stability;
- exact guard ordering and proof that later layers do not execute after denial;
- security-risk escalation, direct/planned, stale/single-use confirmation,
  step-up purpose/session binding, and async consumption;
- complete WNCMS HTML route inventory returning 404 while API/CLI/queue/
  scheduler/callback/host routes remain available, including route cache and
  long-lived application behavior;
- host PAT schema variants, cutoff/headers/CLI, explicit operation opt-in, v1
  separation, and precise password revocation;
- mandatory/aggregate security events, transaction rollback, retention,
  read-only scope, fallback logs, and last-used failure behavior;
- recursive secret canaries across security/mutation tables, logs, responses,
  exceptions, idempotency, and queued payloads;
- route/registry/OpenAPI one-to-one mapping, every metadata validator negative
  fixture, actor-aware capability behavior, and exact deterministic snapshot;
- fresh-install and existing-install schema equivalence, idempotent upgrade,
  failure/version behavior, permission/setting seeds, and host PAT preservation.

Existing-install tests involving the protected v7 updater remain blocked until
separate authorization is provided. No substitute updater may evade that rule.

The pure API acceptance flow runs both transports and covers login, me, refresh,
sessions, scoped service-token use/denial/rotation/revocation, password-wide
revocation, relogin, Blade disable/HTML 404/API continuity, CLI enable, and HTML
restoration. It uses no Next.js app, Farm, Docker, DNS, or live site.

Completion evidence includes targeted and full PHPUnit suites, prepared test DB,
OpenAPI snapshot, contract/route parity, Composer validation/audit, syntax and
format checks, `git diff --check`, independent review, and security-invariant
checks. Runtime Artisan/API checks run from `/www/wwwroot/package.wncms.cc`.

## Documentation And Upgrade Guidance

Public stable behavior is documented under `documentations/manual`; maintainer
reasoning and local handoff remain outside it. Update existing API overview,
getting-started, authentication, capabilities, OpenAPI, contracts, errors,
troubleshooting, and Next.js integration pages. Add focused pages for sessions,
service tokens, security policy, API-only mode, and legacy authentication.

English, zh-CN, and zh-TW API trees keep identical structure, routes, machine
keys, examples, and links. Only prose is translated. UI translations remain in
sync across `en`, `zh_CN`, `zh_TW`, and `ja`. Error codes remain untranslated;
clients depend on `meta.error_code` rather than localized messages.

Next.js documentation supplies server-side BFF JSON-mode and direct Cookie-mode
examples without shipping an application. It forbids client bundles/localStorage
for refresh tokens and uses nonworking example domains/credentials. Service-token
guidance recommends one website, the narrowest template, explicit expiry,
secret-manager storage, rotate-before-revoke, and no unrestricted permanent
examples.

The API-only runbook documents preflight, disable verification, API/queue/
callback continuity, and emergency CLI recovery. The legacy guide documents the
90-day migration timeline, irreversible hashes, distinct v1/PAT/v2 credential
types, Sunset/Deprecation headers, CLI policy, and host-owned cleanup.

A settings/permissions reference lists stable keys, types, defaults, ranges,
runtime invalidation, and fail-open/closed behavior. Final permission names are
identical in seeder, registry, OpenAPI, capabilities, and docs, including token
create/cross-site/permanent/list/show/rotate/revoke, security-event index/show,
and `blade_mode_manage`.

Upgrade documentation describes WNCMS-owned tables, unchanged host PAT schema,
fresh/existing paths, the legacy window, backup/staging guidance, rollback
credential effects, shared-cache/clock/proxy/HTTPS requirements, and excludes
private updater contents or deployment credentials.

Documentation tests verify three-language machine-token parity, links, examples,
secret canaries, deprecation labels, and the absence of unsafe browser storage
advice.

## Implementation Boundary And Approval Gates

This document is the approved design. After written-spec review, a separate TDD
implementation plan will enumerate exact files, test-first steps, checkpoints,
and authorization boundaries. No implementation begins until that plan is
approved and an execution method is selected.

The implementation plan must apply the repository's coding, PHPDoc, API,
testing, settings, migration, documentation, TDD, review, and verification
skills. It must preserve unrelated changes and never access the protected v7
updater without fresh explicit authorization.
