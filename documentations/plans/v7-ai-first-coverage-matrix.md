# WNCMS v7 AI-First Coverage Matrix

Date: 2026-06-25
Status: Proposed
Related roadmap: [v7 AI-first roadmap](./v7-ai-first-roadmap.md)

## Purpose

This matrix audits current WNCMS core feature domains against the v7 direction: every meaningful feature should have human UI, CLI automation, machine-readable API/MCP surfaces, tests, documentation, and consistent permission, audit, and multisite safety behavior.

The audit is based on static inspection of:

- Backend routes and controllers: `routes/backend.php`, `src/Http/Controllers/Backend/*`, `src/Http/Controllers/ThemeController.php`
- API routes, controllers, and config: `routes/api.php`, `routes/api/v2/backend.php`, `config/wncms-backend-api-v2.php`, `src/Http/Controllers/Api/*`
- CLI commands: `src/Console/Commands/*`
- Models, managers, and multisite helpers: `src/Models/*`, `src/Services/Managers/*`, `src/Traits/HasMultisite.php`
- Permissions and install defaults: `database/seeders/RolesSeeder.php`, `config/permission.php`
- Tests: `tests/Feature/*`, `tests/Unit/*`
- Manual and planning docs under `documentations/manual` and `documentations/plans`

No MCP server or MCP tool implementation was found in the current codebase. MCP statuses are therefore `Missing` or `Needs design`.

## Status Legend

- `Complete`: Current code appears to cover the main expected surface for that domain.
- `Partial`: A surface exists, but it is incomplete, not domain-specific, undocumented, not tested, or lacks parity with UI behavior.
- `Missing`: No current implementation or documentation surface was found.
- `Not applicable`: The surface is not expected for this row.
- `Needs design`: The desired v7 surface is conceptually required but needs contract, security, and ownership decisions before implementation.

## Executive Findings

- Backend UI coverage is strong for classic admin CRUD domains.
- Backend API v2 already provides a broad foundation through config-driven resources and bridge actions.
- CLI coverage is mostly installer, scaffolding, update, plugin, theme, setting, website, and diagnostics oriented. Domain CRUD CLI coverage is largely missing.
- MCP coverage is absent.
- Tests are concentrated around posts, comments, menu source behavior, link hooks, plugin lifecycle, API auth settings, and a small API v2 authorization path. Most backend API v2 resources do not yet have direct contract tests.
- Documentation has good developer coverage for commands, managers, hooks, themes, plugins, and selected v1 API endpoints. API v2 resource/action coverage and many admin domains still need explicit docs.
- Permissions exist broadly through route middleware, API v2 config, and `RolesSeeder`. Audit logging is not consistently implemented for UI, CLI, API, or future MCP mutations.
- Multisite support exists through `HasMultisite`, `model_has_websites`, `user_website`, `has_website`, and API v2 website-context middleware, but v7 should standardize how every automation surface accepts and enforces website scope.

## Coverage Matrix

| Domain | Backend UI | CLI commands | API endpoints | MCP surface | Tests | Documentation | Permissions / audit / multisite safety |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Websites | Complete - CRUD, domain aliases, theme options, and owner binding in `WebsiteController`. | Partial - `wncms:update-website` updates one current website field; install flow creates initial site. | Complete - v1 websites CRUD/domain endpoints; v2 `websites` resource plus theme option bridge actions. | Missing | Partial - API auth setting test touches website index; dashboard and model authorization tests touch website context indirectly. | Partial - v1 website endpoint docs and multisite trait docs exist; v2 backend resource docs are thin. | Partial - `can:website_*`, `user_website`, `has_website`, v2 website middleware exist; no consistent mutation audit trail. |
| Users | Complete - admin CRUD plus account profile, security, API token, record pages. | Missing - no user CRUD/account CLI found. | Partial - v2 `users` resource plus account update bridge actions; v1 user endpoint spec is reserved. | Missing | Partial - Google login, API auth, model authorization, and dashboard tests cover selected user paths; no full user CRUD/API v2 contract suite. | Partial - user dashboard and event docs exist; API user endpoint page says no finalized spec. | Partial - `can:user_*` and account permissions exist; admin-only deletion safeguards exist; multisite relation used in UI; audit is missing. |
| Posts | Complete - CRUD, restore, comments tab, bulk sync tags, bulk clone, demo generation. | Partial - `wncms:import-demo` creates demo posts and `wncms:test` exercises PostManager, but no operator-grade post CRUD CLI. | Complete - v1 posts CRUD and v2 posts controller/resource/bridge actions. | Missing | Partial - strong backend/model tests and API auth tests exist; direct v2 post contract tests are still limited. | Complete for v1/API and developer manager docs; v2 backend API docs still need expansion. | Partial - `can:post_*`, website binding, user restrictions, cache flushes exist; no consistent audit record for mutations. |
| Pages | Complete - CRUD, templates, theme page creation, builder load/save/editor, widgets. | Missing | Partial - v1 page controller is placeholder-like; v2 resource and page builder actions exist. | Missing | Missing - no dedicated page controller/API tests found. | Partial - v1 docs explicitly call pages API placeholder; theme/page docs exist. | Partial - `can:page_*` and website/theme context exist; builder mutation audit and automation safety need design. |
| Tags | Complete - CRUD, type creation, bulk create/store, CSV import, keywords, parent updates. | Missing | Partial - v1 list/exist/store and v2 resource plus tag bridge actions exist. | Missing | Partial - tag behavior is covered indirectly through posts/links; no dedicated tag controller/API tests found. | Partial - v1 tag API docs and tag type docs exist; v2/backend docs are missing. | Partial - `can:tag_*`, tag keyword permissions, cache flushes, and multisite binding support exist; audit missing. |
| Menus | Complete - CRUD plus menu item editing, source search, source resolution. | Missing | Partial - v1 menu list/store/sync/show; v2 resource and menu bridge actions exist. | Missing | Partial - menu source controller and manager tests cover source resolution/editor flows. | Partial - v1 menu API, theme menu, and event docs exist; backend API v2 docs are limited. | Partial - `can:menu_*`, cache flushes, and source permissions exist; destructive sync audit and CLI/MCP safeguards missing. |
| Links | Complete - CRUD, clone, bulk update, bulk sync tags, hookable form/index flow. | Complete - `wncms:links:list`, `wncms:links:inspect`, guarded `wncms:links:create`, guarded patch `wncms:links:update`, guarded `wncms:links:delete`, atomic guarded `wncms:links:bulk-update`, and atomic guarded `wncms:links:bulk-sync-tags` exist with JSON output. | Partial - v2 `links` resource and bridge actions exist; v1 links route/spec is not finalized. | Missing | Partial - link hook integration tests plus guarded create/update/delete/bulk-update/bulk-sync-tags dry-run, actor, scope, atomicity, stale-state, rollback, and audit tests exist; API v2 contract tests are still missing. | Partial - manager, event, user dashboard, and command docs include list/inspect/create/update/delete/bulk update/bulk tag synchronization; API links doc says no dedicated spec finalized. | Partial - `can:link_*`, cache flushes, manager website scoping support, hooks, and guarded create/update/delete/bulk-update/bulk-sync-tags CLI actor/permission/target-scope/audit paths exist; UI/API/MCP mutations still need consistent audit and scope enforcement. |
| Settings | Complete - backend tabs, API settings, model website modes, SMTP/Google tests, quick links. | Partial - `wncms:setting-update` updates one key/value without a broader settings contract. | Partial - v2 bridge actions for update/tests/quick links; no generic settings resource. | Missing | Partial - API auth settings and session lifetime tests cover selected settings behavior. | Partial - settings event/system setting docs exist; v2 settings API docs are missing. | Partial - `can:setting_*`, settings cache flow, and model website mode UI exist; CLI has no permission/audit layer. |
| Plugins | Complete - index, upload, activate raw/record, upgrade, deactivate, delete. | Partial - `wncms:activate-plugin` and `wncms:verify-plugin-hooks`; no upload/deactivate/delete CLI. | Partial - v2 plugin index and bridge actions for upload/upgrade/activate/deactivate/delete. | Missing | Partial - plugin lifecycle, compatibility, diagnostics tests exist; controller/API tests are limited. | Complete for plugin development and lifecycle docs; backend API v2 operation docs still needed. | Partial - `can:plugin_*`, dependency/deactivation safeguards, lifecycle remarks exist; no structured audit record. |
| Themes | Complete - index, upload, delete; default theme install via Tools. | Partial - create, pack, remove, install-default-theme commands exist, but no list/inspect/update CLI contract. | Partial - v2 theme index and bridge upload/delete; no broader theme management API contract. | Missing | Missing - no dedicated theme controller/manager tests found. | Partial - theme development docs exist; automation/API docs incomplete. | Partial - `can:theme_*`, delete is blocked when websites use the theme; no audit or consistent website-scope contract for CLI/API. |
| Updates | Partial - update page and check action; rerun specific core update lives under Tools. | Partial - `wncms:update` supports core scripts and `--rerun-version`; no dry-run/status JSON contract. | Partial - v1 update/progress and v2 `updates.check`; no v2 mutation endpoint beyond tool bridge rerun. | Missing | Missing - no dedicated update command/controller tests found. | Partial - v1 update docs and command overview exist; docs mention `wncms:update-package`, which does not match current command names. | Partial - update locks/settings exist in API v1; backend update routes lack explicit `can:*`; no mutation audit trail. |
| Tools | Partial - Tools index with install default theme, rerun core update, cache/permission cards. | Partial - underlying install-default-theme/update/cache-adjacent commands exist, but no unified tools CLI. | Partial - v2 bridge actions for install default theme and rerun core update; cache actions also bridged. | Missing | Partial - tools hook integration tests cover extensibility only. | Partial - tools event docs and backend tools hook plan exist; operator API/CLI docs incomplete. | Partial - action permissions vary (`theme_upload`, `setting_edit`, cache permissions); tools index itself has no permission middleware; audit missing. |
| Comments | Partial - no standalone backend index; create/update/delete/search users are managed from post edit/comment surfaces. | Missing | Partial - v2 comments resource/custom controller actions; no v1 comments API. | Missing | Complete for backend comment create/update/search behaviors; no API v2 contract tests. | Missing - no dedicated comments endpoint/operator docs found. | Partial - `can:comment_*`, author/status validation, and cache flushes exist; audit and multisite ownership rules need v7 design. |
| Advertisements | Complete - CRUD with website selection and media/tags. | Missing | Partial - v2 advertisements resource plus manage update/delete bridge actions. | Missing | Missing - no dedicated advertisement tests found. | Missing - no dedicated operator/API docs found. | Partial - `can:advertisement_*`, manager website scoping support, media/tag cache flushes exist; audit missing. |
| Search keywords | Complete - CRUD and bulk delete. | Missing | Partial - v2 `search_keywords` resource exists. | Missing | Missing - no dedicated search keyword tests found. | Missing - no dedicated operator/API docs found. | Partial - `can:search_keyword_*` exists; no audit or explicit multisite behavior beyond BaseModel default mode. |
| Channels | Complete - CRUD and bulk delete. | Missing | Partial - v2 `channels` resource exists. | Missing | Missing - no dedicated channel tests found. | Missing - no dedicated operator/API docs found. | Partial - `can:channel_*` exists; no audit or explicit multisite automation contract. |
| Clicks | Partial - backend index, summary, delete, bulk delete; frontend record route/job exists. | Missing | Partial - v2 `clicks` index/destroy/bulk_delete plus summary action; no create/update API by design. | Missing | Missing - no dedicated click tests found. | Missing - no dedicated operator/API docs found. | Partial - `can:click_*` exists; click recording has cooldown setting; audit and retention policy need design. |
| Parameters | Complete - CRUD and bulk delete. | Missing | Partial - v2 `parameters` resource exists. | Missing | Missing - no dedicated parameter tests found. | Missing - no dedicated operator/API docs found. | Partial - `can:parameter_*` exists; no audit or explicit multisite automation contract. |
| API v2 backend resources | Not applicable - this is an API foundation rather than a Blade domain. | Partial - `wncms:check-backend-api-v2-parity` checks backend route names against v2 equivalents and now reports configured v7 coverage with `--coverage` / `--json`. | Partial - broad resources/actions are configured; plan still lists remaining business route mapping, validation, and docs hardening. | Needs design - likely a reusable contract source for MCP schemas, but no MCP exists. | Partial - model authorization test covers one v2 mutation path; most resources/actions lack contract tests. | Partial - API overview, maintainer planning notes, and v2 route plans exist; public resource/action reference docs are incomplete. | Partial - token auth, whitelist, website-context middleware, and per-action permissions exist; no unified audit or parity policy for CLI/MCP. |

## Recommended Reference Domain

Use `Links` as the first v7 parity reference implementation.

Reasons:

- It is a meaningful backend CRUD domain without the full complexity of posts/pages builders.
- It has a `LinkManager`, backend controller, backend views, route permissions, cache flushing, tag support, and website scoping hooks through BaseModel/Manager behavior.
- It already has dedicated hook tests and documented hook points.
- It is already present in backend API v2 resources and has extra v2 bridge actions for `bulk_update` and `bulk_sync_tags`.
- Its remaining gaps match v7 goals cleanly: update/delete/bulk CLI parity, no MCP, no API v2 contract tests, no finalized links API doc, and no consistent audit/multisite automation contract across UI/API/MCP.

Pages are a possible second reference, but the builder/template surface makes them less suitable as the first parity pattern. Posts have better tests and v1 API coverage, but their media, comments, bulk clone, demo generation, and translation behavior make them a heavier starting point.

## Cross-Cutting Gaps

### Automation Contract

WNCMS needs one v7 automation contract shared by CLI, API v2, and MCP. The proposed mutation contract now lives in [v7-ai-first-mutation-contract.md](v7-ai-first-mutation-contract.md):

- Command naming: `wncms:{domain}:{action}` or another stable convention.
- JSON output mode for every automation command.
- Exit code semantics for validation, authorization, not found, conflict, and unexpected errors.
- `--website=`, `--user=`, `--dry-run`, `--force`, and confirmation behavior.
- Structured result envelope aligned with API v2: `code`, `status`, `message`, `data`, `meta`, `errors`.
- Shared validation and mutation services so controllers, commands, API, and MCP do not fork business rules.

### MCP Contract

No MCP surface exists today. v7 should decide:

- Whether MCP ships inside `wncms-core`, an optional companion package, or a plugin.
- Whether v7.0 MCP is read-only first, mutation-capable for reference domains only, or broader.
- How MCP tools authenticate and map to WNCMS permissions.
- How production environments enable/disable MCP and scope website access.
- How MCP schemas are generated or reviewed from managers/API resource contracts.

### Audit And Governance

Current code has route/API permissions and some safety checks, but no consistent audit trail for privileged mutations. v7 should define a shared audit record for UI, CLI, API, and MCP:

- Actor type and actor id.
- Surface: `ui`, `cli`, `api_v1`, `api_v2`, `mcp`.
- Domain, action, model key, model id.
- Website scope and requested website ids.
- Input summary with sensitive fields redacted.
- Result status and error code.
- Request id or command run id.

Audit storage should use a dedicated v7 mutation audit table rather than the existing `records` table.

### Documentation Parity

Manual docs should describe human and agent workflows together:

- Backend UI route and permission.
- CLI command examples, including JSON and dry-run.
- API v2 endpoint and response schema.
- MCP tool name, input schema, and output schema.
- Permission, audit, multisite, and destructive-operation notes.
- Test examples for host projects extending the same domain.

## Proposed Phase Plan For v7 CLI/MCP Expansion

### Phase 0 - Contract And Inventory

1. Freeze the v7 automation contract: names, JSON envelopes, exit codes, dry-run/force behavior, website scope, and audit fields.
2. Keep the runtime coverage registry in `config/wncms-backend-api-v2.php` aligned with this matrix and inspect it through `wncms:check-backend-api-v2-parity --coverage --json`.
3. Decide MCP packaging and enablement model.
4. Keep maintainer-only SOP in root ignored files and publish only stable user-facing automation behavior in `documentations/manual`.

### Phase 1 - Read-Only Discovery

1. Add read-only CLI commands for discovery: websites, settings, enabled models, routes, plugins, themes, updates, and system health.
2. Build a read-only MCP proof of concept for the same discovery operations.
3. Add tests for JSON schema stability, permission failures, and website-context failures.
4. Document agent workflows for diagnostics and site inspection.

### Phase 2 - Links Reference Parity

1. Continue hardening `LinkAutomationService` as the reusable mutation service returning structured result objects.
2. Build on the existing Link CLI commands (`list`, `inspect`, guarded `create`, guarded `update`, guarded `delete`) and add bulk update and bulk sync tags.
3. Add Link MCP tools matching the CLI/API contracts.
4. Add API v2 contract tests for Links resource and bridge actions.
5. Update Links docs with backend UI, CLI, API v2, MCP, permissions, audit, and multisite notes.
6. Use this implementation as the reference for future CRUD domains.

### Phase 3 - Content Domains

Apply the Links pattern to:

- Tags
- Menus
- Posts
- Pages
- Comments
- Advertisements
- Search keywords
- Channels
- Parameters
- Clicks where read/delete/summary semantics apply

Prioritize domains with existing managers and tests first, then add manager/service layers where missing.

### Phase 4 - Administrative And Operational Domains

Apply stricter governance to:

- Settings
- Plugins
- Themes
- Updates
- Tools
- Users
- Websites

These domains should require stronger confirmation, audit, environment gating, and rollback/error-reporting behavior before MCP mutations are allowed.

### Phase 5 - Enforcement

1. Add CI coverage for API v2 route parity.
2. Add CLI/MCP/docs parity checks for domains marked v7-required.
3. Add generated coverage reports that can update this matrix or fail when required surfaces regress.
4. Require tests for every new automation surface.

## Concrete Next Tasks

1. Add guarded Link bulk update and bulk tag-sync CLI commands with atomic target-list validation, `--dry-run`, `--force`, actor, permission, website scope, cache flush, and `mutation_audits`.
2. Add API v2 tests for `links` resource actions and bridge actions.
3. Draft MCP packaging/design doc and implement read-only discovery tools before Link mutation tools.
4. Update Links API docs so the current "no dedicated links resource endpoint spec is finalized" gap is closed for v7.
5. Repeat the Links pattern for Tags and Menus, then Posts and Pages.

## Open Questions

- Should production CLI mutations require an explicit `--actor-user=` every time, allow a configured system actor, or vary by environment?
- Should MCP expose only API-backed tools, or can it call manager/service contracts directly?
- Which domains are v7.0 required for mutation parity, and which can ship read-only first?
- Should the coverage matrix become generated from config/tests, maintained manually, or both?
