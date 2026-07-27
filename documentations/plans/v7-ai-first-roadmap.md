# WNCMS v7 AI-First Roadmap

Date: 2026-06-24
Status: Proposed

## Vision

WNCMS v7 should treat AI agents as first-class operators of the CMS, not as a separate integration layer. Every meaningful feature should be reachable through human UI, CLI automation, and machine-readable tool interfaces such as MCP.

The goal is to make WNCMS practical in an AI-led development and operations workflow: agents can inspect, configure, scaffold, test, and maintain a site through stable commands and contracts instead of brittle browser-only flows.

## Core Principles

1. Every feature has an automation surface.
2. UI actions should map to reusable service methods that can also power CLI and MCP tools.
3. CLI commands should be scriptable, documented, idempotent where possible, and safe to run in CI.
4. MCP tools should expose structured inputs/outputs and avoid requiring agents to parse Blade, HTML, or logs for routine operations.
5. Permissions, audit logging, validation, and multisite scoping must be consistent across UI, CLI, API, and MCP entry points.
6. Documentation should describe the human workflow and the agent workflow together.

## Candidate v7 Workstreams

### Command Coverage

- Define a command coverage matrix for core domains such as websites, users, posts, pages, tags, menus, links, settings, plugins, themes, updates, and diagnostics.
- Ensure every backend CRUD-style feature has a CLI equivalent for list, inspect, create, update, delete, bulk operation, and dry-run where relevant.
- Standardize command output modes, including human-readable tables and JSON for automation.
- Add confirmation, `--force`, `--dry-run`, and scoped website options consistently.

### MCP Surface

- Design a WNCMS MCP server for safe agent operations.
- Start with read-only discovery tools: list websites, inspect settings, list routes, list enabled models, inspect plugins, inspect themes, and summarize system health.
- Add guarded mutation tools only after permission, audit, and validation behavior is unified.
- Keep MCP schemas close to existing manager/service contracts so feature code does not fork.

### Agent-Aware Architecture

- Move feature behavior behind services/managers that can be called from controllers, commands, API controllers, and MCP tools.
- Add structured result objects for common operations so each entry point can render the same success/failure state.
- Prefer stable model keys, route names, setting keys, and permission names over UI text as automation identifiers.
- Add parity checks that verify UI/backend route actions have corresponding CLI and MCP coverage when required.

### Documentation And Skills

- Add docs that describe each feature through UI, CLI, API, and MCP usage.
- Extend published agent skills so host projects can safely add CLI and MCP surfaces for custom WNCMS features.
- Maintain examples for AI-assisted workflows such as creating a model, publishing a theme, diagnosing permissions, and preparing an update.

### Security And Governance

- Treat AI-facing tools as privileged surfaces with explicit permissions.
- Add audit logs for CLI and MCP mutations, including actor, website scope, input summary, and result.
- Provide read-only modes for diagnostics and production support.
- Ensure destructive operations require explicit confirmation or force flags outside trusted automation contexts.

## Suggested Phase 1

1. Create an inventory of current backend features and their existing CLI/API coverage.
2. Define the v7 automation contract: command naming, JSON output shape, exit codes, dry-run semantics, permissions, and audit fields.
3. Build a read-only MCP proof of concept for site/system discovery.
4. Pick one complete domain, such as Links or Pages, and implement UI/service/CLI/MCP parity as the reference pattern.
5. Add tests and docs that make the reference domain reusable for all later domains.

## Open Questions

- Should MCP ship inside `wncms-core`, as an optional companion package, or as a plugin?
- Which features must have MCP mutation support in v7.0, and which can remain CLI/API-only until later minor releases?
- How should production sites enable, disable, or scope AI-agent access by environment?
- Should command and MCP parity be enforced by tests, a dedicated artisan command, or both?
