# WNCMS v7 Global Mutation Audit Toggle Design

- Date: 2026-08-05
- Status: Approved
- Target: `v7.0.0-alpha1`

## Objective

Add complete mutation audit coverage to the six Link backend UI write paths while
allowing performance-focused sites to disable all mutation audit writes globally.
The global switch defaults to disabled and governs backend UI, CLI, and API
mutations.

## Setting Contract

- Add the stable system setting key `enable_mutation_audit` as a switch under the
  Admin settings tab.
- The fallback is `false`; projects do not need a seeded Setting row to remain
  disabled.
- Resolve the value with `gss('enable_mutation_audit', false)` during system
  settings boot and expose the result through
  `config('wncms.mutation_audit.enabled', false)`.
- Mutation paths read the runtime config rather than querying settings repeatedly.
- Add labels and descriptions in the default `en`, `zh_CN`, `zh_TW`, and `ja`
  locales.

## Disabled Fast Path

When `enable_mutation_audit` is disabled:

- Backend UI uses its existing CRUD path without collecting before/after snapshots,
  loading extra audit relationships, opening audit-only transactions, or inserting
  `mutation_audits` rows.
- CLI and API mutations retain their existing validation, actor, permission,
  website-scope, stale-state, and transaction safeguards, but skip audit inserts.
- Automation envelopes retain an `audit` object with `enabled=false` and `id=null`
  so clients do not need a second response schema.
- Dry-runs never write audit rows and report the disabled state consistently.

## Audit Components

### MutationAuditService

`MutationAuditService` remains the single global audit gate.

- Expose an `enabled(): bool` check backed by runtime config.
- Keep redaction and audit payload normalization centralized.
- Return `null` from audit persistence when the feature is disabled.
- Report `enabled`, `will_write`, table, and normalized attributes in audit previews.
- Preserve an object-shaped automation audit reference with a nullable ID.

### BackendMutationAuditService

Add a reusable backend UI adapter that converts successful model changes into the
existing mutation audit plan format and delegates persistence to
`MutationAuditService`.

It receives the actor, surface, domain, action, model, website IDs, permission,
before/after state, optional relationship changes, and optional shared run ID. It
does not own Link validation, media processing, tags, website binding, cache
flushing, hooks, or HTTP responses.

## Link Backend UI Coverage

Cover all six mutation routes:

1. `store` records `action=create` with the created Link state.
2. `update` records `action=update` only when model, website, tag, or media state
   actually changes.
3. `destroy` records `action=delete` with the pre-delete target snapshot.
4. `bulk_delete` records `action=bulk_delete` once per deleted Link with one shared
   run ID.
5. `bulk_update` records `action=bulk_update` once per Link whose URL or sort value
   changes, with one shared run ID.
6. `bulk_sync_tags` records `action=bulk_sync_tags` once per Link whose category or
   tag relations change, with one shared run ID.

Every UI record uses `surface=ui`, the authenticated user actor, domain `links`,
model key `link`, the Link model ID, the actual website IDs, the route permission,
redacted input/change summaries, and a successful result.

## Transaction And Error Policy

- When auditing is enabled, each single mutation keeps its database change and
  audit insert in one transaction.
- A bulk mutation uses one transaction and one shared run ID for every changed
  Link; cache is flushed once after a changed commit.
- Audit only successful, committed, actual changes.
- No-op, validation failure, permission denial, missing target, caught exception,
  or rolled-back work does not create an audit row.
- An audit persistence failure rolls back the associated database mutation.
- Preserve existing Link routes, request formats, redirects, JSON envelopes,
  hooks, media behavior, and translated success/error messages.

## Performance Contract

- Disabled is the default and the normal low-overhead path.
- The disabled backend UI path performs no audit insert and no audit-only snapshot
  or relationship query.
- Runtime code checks an in-memory config boolean; the system setting is resolved
  once through the existing cached settings boot flow.
- Enabling audit accepts the additional snapshot, relationship, and insert queries
  required to produce useful before/after records.
- Documentation must warn that disabling the switch also removes CLI/API mutation
  accountability; enabling it is recommended on sites that use automation writes.

## Tests

- Verify the setting appears, saves, and defaults to disabled.
- Verify disabled UI create/update/delete/bulk delete/bulk update/bulk tag sync
  produce no audit inserts and retain existing behavior.
- Verify the disabled UI fast path does not issue audit-table or audit-only snapshot
  queries.
- Verify disabled CLI/API writes return `audit.enabled=false` and `audit.id=null`.
- Enable the setting and cover all six UI mutations, actor/surface/action/model/
  website fields, redaction, no-op handling, shared bulk run IDs, and rollback.
- Keep existing CLI/API audit tests by enabling the setting explicitly where audit
  persistence is part of the assertion.
- Run focused settings, Link UI, CLI, and API tests, then the complete PHPUnit
  suite.

## Documentation And Tracking

- Update the Link manager or automation manual in English, `zh-CN`, and `zh-TW`
  with the global toggle, performance behavior, response shape, and safety warning.
- Update the v7 coverage matrix to mark Link backend UI audit coverage complete
  while leaving guarded API bulk delete and mutation MCP out of scope.
- Add one aligned behavior-focused entry to all four v7 changelogs.

## Non-goals

- No mutation audit viewer, retention policy, export, alerting, or automatic rollback.
- No guarded API Link bulk delete and no mutation MCP tools.
- No audit rollout to backend UI domains other than Links in this task.
- No official `7.0.0` updater, tag, push, or release operation.
- Do not read, edit, stage, or commit the existing untracked official updater file.
