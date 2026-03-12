---
name: wncms-adding-a-hook
description: Add new WNCMS hooks with standard naming, lifecycle placement, and required event documentation updates.
---

## Goal
Create hooks that are predictable, extensible, and documented in the official developer event docs.

## Scope
Apply this skill when:
- creating new hooks in wncms-core
- naming plugin/theme extension hooks
- reviewing hook naming consistency
- migrating legacy hook names

## Naming Policy
Use readable names that match route/controller mental model.

## Format A (Controller / Manager)
Suggested pattern:
- `{package}.{layer}.{target}.{action}.{timing}`

Segments:
- `package`: owner namespace (default `wncms`)
- `layer`: `frontend`, `backend`, `api`, `common`
- `target`: plural business target (`users`, `posts`, `orders`)
- `action`: snake_case verb (`show`, `store`, `update`, `delete`, `login`)
- `timing`: `before`, `after`, or another explicit lifecycle/state suffix when the hook is not a mutation boundary

Use `resolve` for hooks whose purpose is to resolve view names, params, redirect targets, or similar render/composition inputs before the main action continues.

Examples of `resolve` usage:
- `wncms.backend.users.create.resolve`
- `wncms.backend.users.edit.resolve`
- `wncms.frontend.users.login.resolve`
- `wncms.frontend.users.profile.show.resolve`

Use `before` / `after` for action lifecycle hooks around validation, persistence, side effects, or final response preparation.

Examples:
- `wncms.frontend.posts.show.before`
- `wncms.frontend.posts.show.after`
- `wncms.backend.users.update.before`
- `wncms.backend.users.update.after`

## Format B (View / Blade Slot)
Suggested pattern:
- `{package}.view.{path}.{slot}`

Segments:
- `package`: owner namespace (default `wncms`)
- fixed `view`
- `path`: blade-like path (`backend.posts.edit`)
- `slot`: injection region (`sidebar`, `fields`, `actions`, `scripts`)

Examples:
- `wncms.view.backend.posts.edit.sidebar`
- `wncms.view.backend.users.edit.fields`

## Required Workflow When Adding Hooks In wncms-core
1. Add dispatch point in controller/manager/view at the correct lifecycle timing.
2. Use one of the naming formats above (prefer plural `target`).
3. Update event docs in `documentations/manual/developer/event` in the same task.
`overview.md` should link to grouped pages and the hook must be documented in the relevant group page (for example `posts.md`, `users.md`, `settings.md`).
4. If locale mirrors exist, sync structure in:
- `documentations/manual/zh-CN/developer/event`
- `documentations/manual/zh-TW/developer/event`

## Review Checklist
- Hook name matches Format A or B.
- Format A hooks end with a clear lifecycle/state suffix (`before`, `after`, `resolve`, etc.) where applicable.
- Target is plural and readable.
- New hook is documented under `documentations/manual/developer/event`.
- zh-CN and zh-TW event docs are synced when present.
