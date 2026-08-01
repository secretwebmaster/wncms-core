# WNCMS v7 Composer Security Remediation Design

- Date: 2026-08-02
- Status: Approved
- Target: `v7.0.0-alpha1` development baseline

## Objective

Remove the eight advisories reported by the current ignored development lock file
before continuing Links UI audit work.

## Scope

- Add a Composer conflict for `phpseclib/phpseclib <3.0.54`; Socialite remains
  the owning production dependency while WNCMS prevents vulnerable resolution.
- Upgrade direct production dependency `spatie/laravel-medialibrary` from `11.21.0`
  by raising its root constraint from `^11.21` to `^11.23`.
- Upgrade development-only dependency `symfony/yaml` from `8.0.8` to a compatible
  safe release at or above `8.0.12` in the ignored local lock file; do not add a
  runtime constraint for this dev-only transitive package.
- Update all four v7 changelogs with one concise security entry.

## Delivery

1. Apply the two production safety floors in `composer.json`, then resolve only
   the affected packages and dependencies required by Composer.
2. Keep `composer.lock` ignored and uncommitted; it remains local verification
   state for this package repository.
3. Run `composer audit --locked` and require zero advisories.
4. Run Composer validation, focused dependency checks, and the full test suite.
5. Review the staged scope and commit the remediation independently.

## Non-goals

- No broad dependency modernization or root package architecture change.
- No Links UI audit implementation in this commit.
- No official `7.0.0` updater, tag, push, or release operation.
- Do not read, edit, stage, or commit the untracked official updater file.

## Failure Policy

If a safe version cannot resolve under current Laravel 13 and PHP 8.4 constraints,
stop and redesign the affected dependency instead of suppressing its advisory.
