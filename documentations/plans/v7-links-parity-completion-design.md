# WNCMS v7 Links Parity Completion Design

- Date: 2026-07-27
- Status: Draft for implementation review
- Scope: Link bulk tag synchronization, shared CLI rendering, Links API v2, and local MCP read tools

## Objective

Complete the next four Links AI-first slices without weakening the v7 mutation
contract:

1. Add guarded bulk tag synchronization to the CLI and shared automation service.
2. Remove repeated Link CLI output code through a shared renderer.
3. Make Links the first safe, documented API v2 reference resource.
4. Bundle an opt-in local MCP server with read-only Link tools.

Each slice is implemented, tested, reviewed, documented, and committed separately.
The deferred v7 release updater and release metadata remain out of scope.

## Locked Decisions

- WNCMS remains a Composer package for this work.
- Existing automation envelopes and machine-readable result codes remain stable.
- Mutations are dry-run by default and require explicit force plus an authorized
  actor.
- Link writes continue to pass through `LinkAutomationService`.
- Public behavior is documented in English, Simplified Chinese, and Traditional
  Chinese where locale mirrors exist.
- `laravel/mcp:^0.9` is a production dependency of the core package.
- MCP is disabled by default, enabled by configuration, and exposed locally only.
- The first MCP slice is read-only; mutation tools are intentionally excluded.
- `updates/update_core_7.0.0.php` is untouched until the formal release phase.

## Phase 1: Guarded Link Bulk Tag Synchronization

### CLI contract

Add:

```text
wncms:links:bulk-sync-tags
    --identifiers='[1,"example-slug"]'
    --action=sync|attach|detach
    [--categories='["Partners"]']
    [--tags='["Featured"]']
    [--website=1]
    [--actor-user=1]
    [--dry-run]
    [--force]
    [--json]
```

Rules:

- Accept 1–100 unique Link identifiers using the same ID/slug resolution rules as
  existing guarded commands.
- Require `action` to be `sync`, `attach`, or `detach`.
- Require at least one non-empty category or tag list.
- Normalize tag names, reject invalid values, and remove duplicates.
- An omitted or empty tag type is unchanged.
- `sync` synchronizes only the supplied non-empty tag types; this command does not
  provide a clear-all operation.
- A website scope is mandatory through `--website` or the current WNCMS website.
- Execution is a dry run unless `--force` is supplied.
- Forced execution requires `--actor-user` and `link_edit` permission.

### Service behavior

Add planning and execution methods to `LinkAutomationService`:

```php
planBulkSyncTags(
    array $identifiers,
    string $action,
    array $tags,
    array $options = []
): array

bulkSyncTags(
    array $identifiers,
    string $action,
    array $tags,
    array $options = []
): array
```

Execution is atomic:

1. Resolve and validate the complete target set.
2. Begin one database transaction.
3. Re-resolve targets and repeat authorization, website scope, and stale
   relationship checks inside the transaction.
4. Apply all tag mutations or roll back all of them.
5. Write one audit record for each changed Link with one shared run ID.
6. Do not write audits for no-op targets.
7. Flush Link cache once after a successful commit when at least one Link changed.

No new hooks are introduced because the existing backend bulk tag operation does
not dispatch hooks.

### Required tests

- Dry-run result and absence of writes.
- `sync`, `attach`, and `detach` behavior for categories and tags.
- Identifier limit, duplicate, malformed input, invalid action, and empty tag
  validation.
- Missing actor, permission denial, and cross-website rejection.
- Atomic rollback on validation failure and runtime failure.
- Revalidation inside the transaction.
- Audit count, shared run ID, no-op behavior, and single cache flush.

## Phase 2: Shared Link CLI Output Renderer

Create an abstract command:

```text
Wncms\Console\Commands\AutomationCommand
```

It extends Laravel's console command and owns:

- JSON serialization of the existing automation result envelope.
- Human-readable result messages.
- Validation/error table rendering.
- Shared success and failure exit-code mapping.
- An optional callback for command-specific tables or summaries.

All Link automation commands, including bulk tag synchronization, extend this
class. Business inputs, service calls, and domain-specific table columns remain in
their concrete commands.

Compatibility rules:

- JSON keys, result codes, and exit behavior remain unchanged.
- Human output becomes consistent while preserving useful command-specific tables.
- The renderer must not infer business state or alter service results.

Representative human and JSON tests cover success, validation failure, permission
failure, not-found results, and custom list/inspect tables.

## Phase 3: Links API v2 Reference Contract

### Dedicated controller

Create a Links-specific API v2 backend controller instead of routing Link writes
through the generic resource controller. It reuses `LinkAutomationService` for:

- `index` through the Link list operation.
- `show` through the Link inspect operation.
- `store`, `update`, and `destroy` through guarded mutations.
- `bulk-update` and `bulk-sync-tags` through guarded bulk mutations.

The authenticated API token user is the mutation actor. Website scope comes from
an explicit request `website_id` or the current WNCMS website.

### Mutation request contract

- Mutation requests preview by default.
- `force=true` explicitly requests execution.
- `dry_run=true` always forces preview behavior.
- Service result envelopes are returned as JSON using the envelope's HTTP code.
- Authentication, permission, website scope, stale-state checks, transactions,
  audit records, hooks, and cache behavior match the CLI contract.

The generic Links `bulk_delete` route is disabled until a guarded bulk-delete
service exists. This prevents an unguarded raw-model delete path from being
advertised as supported.

### Required contract coverage

- Unauthenticated request returns `401`.
- Permission denial returns `403`.
- Missing website context returns `409`.
- Index/show filtering, identifier resolution, and website isolation.
- Default preview produces no write.
- Forced create, update, delete, bulk update, and bulk tag synchronization.
- Audit and cache behavior for successful and no-op mutations.
- Stable automation envelope, result codes, and HTTP status mapping.
- Links bulk delete is unavailable.

### Documentation

Replace the placeholder Links endpoint page with the finalized routes, request
examples, response envelopes, preview/force rules, permissions, website scope,
and bulk-delete limitation. Keep the `en`, `zh-CN`, and `zh-TW` structures and
examples synchronized.

## Phase 4: Opt-in Local MCP Read Tools

### Packaging and registration

- Require `laravel/mcp:^0.9` in `composer.json`.
- Add `wncms.mcp.enabled`, backed by `WNCMS_MCP_ENABLED`, defaulting to `false`.
- Register a local MCP server only when enabled.
- Do not add a web MCP route, OAuth flow, or remotely exposed transport in this
  slice.

The enabled local process is the trust boundary. MCP read tools do not introduce
a separate WNCMS actor or permission model.

### Server and tools

Add:

```text
Wncms\Mcp\Servers\WncmsServer
wncms-links-list
wncms-links-inspect
```

Both tools:

- Reuse `LinkAutomationService::list()` or `inspect()`.
- Mirror the corresponding CLI filters and identifier inputs.
- Require the same website context and isolation.
- Return the standard automation envelope as a structured MCP response.
- Include MCP surface/tool metadata without changing core result semantics.
- Declare read-only, idempotent, and closed-world annotations.

Mutation MCP tools are excluded until a separate approval and actor design is
accepted.

### Required tests and documentation

- Server registration is absent while disabled and present while enabled.
- List and inspect tool schemas, validation, website isolation, structured
  responses, not-found behavior, and successful reads.
- Public MCP overview pages in `en`, `zh-CN`, and `zh-TW` covering enablement,
  local client configuration, available tools, response envelopes, and the local
  security boundary.

References:

- [Laravel 13 MCP documentation](https://laravel.com/docs/13.x/mcp)
- [Official Laravel MCP package](https://github.com/laravel/mcp)

## Records and Coverage

For each phase:

- Add an `Unreleased` changelog entry after public behavior changes.
- Update the AI-first coverage matrix with exact source, test, and documentation
  paths.
- Mark only implemented scope complete; explicitly retain gaps such as MCP
  mutations and guarded API bulk delete.
- Keep internal plans under `documentations/plans` and public usage guidance under
  `documentations/manual`.

## Delivery and Review

Each phase follows this cycle:

1. Write failing tests for the accepted contract.
2. Implement the smallest complete behavior.
3. Run focused tests, lint, documentation parity checks, and relevant regression
   tests.
4. Request an independent code review and address confirmed findings.
5. Update documentation, coverage records, and changelog.
6. Verify the exact staged diff and create one logical commit.

After all four phase commits, run the full test suite and an independent
cross-phase review. Do not push or modify release updater files without explicit
authorization.
