# WNCMS v7 Alpha 1 Stabilization Design

- Date: 2026-08-01
- Status: Approved
- Target: `v7.0.0-alpha1`

## Objective

Stabilize the current v7 alpha without starting the official `v7.0.0` release.
Each implementation task receives its own tests, review, and commit.

## Task 1: Restore the Auth Website Default

Root cause: `WncmsServiceProvider` only shares `$website` after installation state
checks, so auth views can receive an undefined variable during early or isolated
application boot.

- Always share `website` with a `null` default before installation checks.
- When WNCMS is installed, resolve and share the active website as the override.
- Add a regression test before implementation, using the failing Google login flow.
- Verify the focused Google login tests and the complete test suite.

## Task 2: Stabilize Local Composer Path Repositories

Add an `options.versions` map to each development-only path repository:

- `secretwebmaster/laravel-localization`: `2.4.1`
- `secretwebmaster/laravel-optionable`: `2.1.1`
- `secretwebmaster/wncms-tags`: `1.8.1`
- `secretwebmaster/wncms-translatable`: `1.4.1`

These explicit local versions satisfy the root stable constraints without changing
`minimum-stability`. Composer ignores dependency package repository declarations,
so these root development mappings do not alter repository resolution in consumer
projects.

- Keep `composer.lock` ignored, unmodified, and uncommitted.
- Validate `composer.json` and run a dependency-resolution check without a broad
  dependency upgrade.

## Task 3: Synchronize Alpha Release Metadata

- Set `config/installer.php` to `7.0.0-alpha1`.
- Confirm the same version heading and release content in all four changelogs:
  - `documentations/change/CHANGELOG.md`
  - `documentations/change/CHANGELOG_en.md`
  - `documentations/change/CHANGELOG_zh_CN.md`
  - `documentations/change/CHANGELOG_ja.md`
- Do not create an alpha updater file; preview releases do not ship
  `updates/update_core_X.Y.Z.php`.
- Run PHP syntax checks and version-consistency checks on edited release files.

## Task 4: Defer the Official v7 Release Gate

The official `7.0.0` release gate remains last. It will cover the final updater,
installer and changelog synchronization, installation and WebUI upgrade checks,
and release tagging only after separate authorization.

Until that authorization is given, do not read, edit, stage, or commit the existing
untracked `updates/update_core_7.0.0.php`.

## Non-goals

- No official `7.0.0` release, updater, tag, push, or publication.
- No dependency upgrades beyond validating the four local path version mappings.
- No Composer lock-file policy change.
- No unrelated auth, installer, changelog, or release refactoring.

## Risks and Controls

- A global `null` website could mask incorrect installed-state resolution; focused
  tests must confirm installed applications still receive the resolved website.
- Incorrect path versions could hide incompatibilities; each mapped version must
  satisfy its matching root requirement and Composer resolution must pass.
- Release metadata can drift; compare the installer and all four changelogs using
  the exact `7.0.0-alpha1` string.
- The untracked official updater could be included accidentally; verify every staged
  file list before each commit.

## Delivery and Verification

1. Implement Task 1 with TDD, run focused and full tests, obtain review, and commit.
2. Implement Task 2, validate Composer resolution, obtain review, and commit.
3. Implement Task 3, verify release metadata and PHP syntax, obtain review, and
   commit.
4. Leave Task 4 pending until explicit official-release authorization.
5. Before every completion claim, run `git diff --check` and confirm the staged
   scope contains only that task's intended files.
