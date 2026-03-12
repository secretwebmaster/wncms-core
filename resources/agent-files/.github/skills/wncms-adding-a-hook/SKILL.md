---
name: wncms-adding-a-hook
description: Add custom WNCMS-style hooks in a host project or plugin using consistent naming, lifecycle placement, and documented event conventions.
---

## Goal
Create custom hooks in host-project code that feel native to WNCMS and stay compatible with existing event naming conventions.

## Scope
Use this skill when:
- adding new hooks in `app/`, `routes/custom_*.php`, theme code, or `public/plugins`
- naming custom plugin or theme extension hooks
- reviewing hook naming consistency in a host project

## Read First
- `documentations/manual/developer/event/overview.md`
- `documentations/manual/developer/event/users.md`
- `documentations/manual/developer/event/posts.md`
- `documentations/manual/developer/event/settings.md`
- `documentations/manual/developer/event/themes.md`

## Naming Policy
Use readable names that match the route/controller mental model and align with documented WNCMS hook families.

## Format A (Controller / Manager / Runtime Flow)
Suggested pattern:
- `{package}.{layer}.{target}.{action}.{timing}`

Segments:
- `package`: owner namespace such as `wncms` or your plugin/project namespace
- `layer`: `frontend`, `backend`, `api`, `common`
- `target`: plural business target (`users`, `links`, `orders`)
- `action`: snake_case verb or action phrase (`show`, `store`, `update`, `seo_analyze`)
- `timing`: `before`, `after`, or another explicit lifecycle/state suffix such as `resolve`

Use `resolve` when the hook determines view names, params, redirect targets, or similar composition inputs before the main action continues.

Examples:
- `wncms.backend.users.create.resolve`
- `wncms.frontend.users.login.resolve`
- `wncms.backend.links.store.before`
- `wncms.backend.links.store.after`

## Format B (View / Blade Slot)
Suggested pattern:
- `{package}.view.{path}.{slot}`

Segments:
- `package`: owner namespace such as `wncms` or your plugin/project namespace
- fixed `view`
- `path`: blade-like path (`backend.links.edit`)
- `slot`: injection region (`sidebar`, `fields`, `actions`, `scripts`)

Examples:
- `wncms.view.backend.users.edit.fields`
- `wncms.view.backend.posts.edit.sidebar`
- `myplugin.view.backend.links.edit.fields`

## Practical Guidance
- Use `*.resolve` for page/view composition.
- Use `*.before` / `*.after` for validation, persistence, side effects, or response preparation.
- Use `*.attributes.before` only when you need a separate pre-persistence payload mutation hook and the extra precision improves clarity.
- Use `wncms.view.*` style hooks for HTML fragment injection from Blade templates.
- Prefer plural targets so naming stays aligned with existing WNCMS docs.

## Hard Rules
- Prefer documented WNCMS naming style instead of inventing unrelated event families.
- If you consume an existing WNCMS hook, use the documented hook name and payload shape.
- If you add a new project-local hook, keep its name explicit enough that developers can infer when it fires.
- Respect pass-by-reference parameters when a hook is meant to mutate values.

## Review Checklist
- Hook name matches Format A or B.
- The suffix clearly communicates intent (`before`, `after`, `resolve`, etc.).
- The target is plural and readable.
- The hook placement matches its purpose instead of mixing rendering and persistence concerns.
