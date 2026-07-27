# WNCMS v7 AI-First Mutation Contract

Date: 2026-06-25
Status: Proposed

This contract extends the [v7 AI-first roadmap](v7-ai-first-roadmap.md) and the [v7 AI-first coverage matrix](v7-ai-first-coverage-matrix.md). It defines the shared safety and output rules for future mutation surfaces across CLI, API v2, and MCP.

## Scope

The contract applies to privileged write operations exposed outside the existing backend UI, including:

- CLI commands such as `wncms:{domain}:create`, `wncms:{domain}:update`, `wncms:{domain}:delete`, and bulk mutation commands.
- API v2 backend mutation endpoints and bridge actions.
- Future MCP mutation tools.
- Shared services that power those surfaces.

Read-only list and inspect operations should use the same result envelope but do not need force, audit-write, or destructive-operation confirmation.

## Result Envelope

Every automation surface must return or render a stable envelope:

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

- `code`: HTTP-like numeric code.
- `status`: `success` or `fail`.
- `message`: human-readable summary.
- `data`: domain payload.
- `meta`: surface, command/tool/route, domain, action, website scope, dry-run flag, request id or run id.
- `errors`: field or policy errors, keyed by machine-readable identifiers.

## Exit And Error Codes

CLI commands should map the envelope to process exit codes:

| Code | Meaning | CLI exit |
| --- | --- | --- |
| 200 | Success | 0 |
| 201 | Created | 0 |
| 202 | Accepted or dry-run passed | 0 |
| 400 | Validation failed | 1 |
| 401 | Unauthenticated actor | 1 |
| 403 | Permission denied or website scope denied | 1 |
| 404 | Target not found | 1 |
| 409 | Conflict, stale state, or blocked mutation policy | 1 |
| 422 | Valid request shape but domain rule failed | 1 |
| 500 | Unexpected error | 1 |

## Mutation Safety Rules

All mutation-capable surfaces must support these rules before real writes are enabled:

- Dry-run first: mutation commands must support `--dry-run` and return the exact planned attribute, relationship, cache, hook, and audit effects without writing.
- Force for writes: destructive or privileged commands must require `--force` or an explicit confirmation prompt outside trusted automation contexts.
- Actor required: writes must identify an actor. CLI must define whether the actor is `--actor-user=`, a configured system actor, or a trusted shell context before write mode ships.
- Permission required: each operation must map to the same permission names already used by backend/API routes, for example `link_create`, `link_edit`, and `link_delete`.
- Website scope required: commands and tools must accept explicit website scope where the model supports multisite behavior, and must fail when the actor cannot access the requested website ids.
- Validation shared: controllers, CLI, API, and MCP must call shared services or managers rather than duplicating business rules.
- Cache effects declared: mutation plans must list cache tags that would be flushed.
- Hook effects declared: mutation plans must list existing hook names that would run once real writes are enabled.

## Audit Storage Decision

v7 mutation audit should use a dedicated audit table rather than the existing `records` table.

Reasons:

- `records` is currently a loose operational record table with only `type`, `sub_type`, `status`, `message`, and `detail`.
- Mutation audit needs structured fields for actor, surface, domain, action, model key, model id, website ids, input summary, result code, request/run id, and timestamps.
- A dedicated table can enforce indexes and retention policy without changing the semantics of existing records pages.

The dedicated audit schema now exists as the `mutation_audits` table. Write-mode automation should only ship after the target domain reuses this audit storage with actor, permission, and website-scope guard checks.

## Audit Fields

The v7 audit record is represented by `mutation_audits` and `Wncms\Models\MutationAudit`. It should include:

- `id`
- `run_id`
- `surface`
- `actor_type`
- `actor_id`
- `domain`
- `action`
- `model_key`
- `model_id`
- `website_ids`
- `permission`
- `input_summary`
- `result_code`
- `result_status`
- `message`
- `created_at`

Dry-run services may return an audit preview under `audit.attributes` without writing to this table. Write-mode services must persist a `mutation_audits` row after the domain mutation succeeds.

## Actor Resolution Policy

Write mode must not infer a privileged actor from the shell user alone.

- Dry-run mode may omit actor information, but should include `actor_type` and `actor_id` when the caller provides them.
- CLI write mode must require either `--actor-user=` or a configured system actor before any database write is allowed.
- The default configured system actor source is `config('wncms.automation.system_actor_user_id')`, backed by `WNCMS_AUTOMATION_SYSTEM_ACTOR_USER_ID`.
- MCP write mode must use the authenticated MCP session or token-mapped WNCMS user as the actor.
- API v2 write mode must use the authenticated request user or token owner.
- If no actor can be resolved for write mode, the operation must fail with `401`.
- If the actor lacks the mapped permission or requested website scope, the operation must fail with `403`.
- Dry-run mutation plans may include a `guard` preview that reports actor resolution, permission status, and website scope status without writing.

Sensitive values must be redacted before writing `input_summary`.

## Link Reference Domain Rules

Links remains the reference implementation domain for v7 mutation parity.

The first Links mutation service milestone has two layers:

- `create` supports dry-run planning and guarded CLI write mode through `wncms:links:create`.
- `create` plans generate `slug` and `tracking_code` defaults when omitted.
- `update` plans patch-style attribute changes only for fields provided by the caller.
- `delete` plans target identity, website scope, permission, audit, cache, and hook effects without deleting.
- File/media changes are declared as unsupported in dry-run v1 unless a later media mutation contract is added.
- Tag changes can be declared in the relationship plan; guarded create can write link categories and tags, while update/delete tag writes wait for their dedicated commands.

## Next Implementation Steps

1. Add guarded Link update/delete CLI commands using the existing actor resolver, permission/scope checker, and `mutation_audits` storage.
2. Add shared output rendering for Link CLI commands before expanding to more domains.
3. Add API v2 tests for Links resource mutations and bridge actions.
4. Draft the MCP packaging and enablement design before exposing mutation tools.
