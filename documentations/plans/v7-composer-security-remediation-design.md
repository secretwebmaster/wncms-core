# WNCMS v7 Composer Security Remediation Design

- Date: 2026-08-02
- Updated: 2026-08-11
- Status: Approved
- Target: `v7.0.0-alpha1` development baseline

## Objective

Keep the v7 production dependency graph from resolving known-vulnerable releases.
The initial remediation removed eight advisories; the 2026-08-11 follow-up also
blocks the newly disclosed vulnerable `league/commonmark` range before continuing
Links UI audit work.

## Scope

- Add a Composer conflict for `phpseclib/phpseclib <3.0.54`; Socialite remains
  the owning production dependency while WNCMS prevents vulnerable resolution.
- Upgrade direct production dependency `spatie/laravel-medialibrary` from `11.21.0`
  by raising its root constraint from `^11.21` to `^11.23`.
- Upgrade development-only dependency `symfony/yaml` from `8.0.8` to a compatible
  safe release at or above `8.0.12` in the ignored local lock file; do not add a
  runtime constraint for this dev-only transitive package.
- Add a Composer conflict for `league/commonmark <2.9.0`. Laravel 13 owns this
  transitive production dependency through `^2.8.1`; the root conflict prevents
  WNCMS consumers from resolving the six advisories affecting the older range.
- Update all four v7 changelogs with one concise security entry.

## Delivery

1. Apply the production safety floors in `composer.json`, then resolve only the
   affected packages and dependencies required by Composer.
2. Keep `composer.lock` ignored and uncommitted; it remains local verification
   state for this package repository.
3. Resolve `league/commonmark` to `>=2.9.0` without broad Laravel or Composer
   dependency modernization.
4. Prove `league/commonmark 2.8.3` is rejected by the root package.
5. Run `composer audit --locked` and require zero advisories.
6. Run Composer validation, focused dependency checks, and the full test suite.
7. Review the staged scope and commit the remediation independently.

## Non-goals

- No broad dependency modernization or root package architecture change.
- No Links UI audit implementation in this commit.
- No official `7.0.0` updater, tag, push, or release operation.
- Do not read, edit, stage, or commit the untracked official updater file.
- Do not add `league/commonmark` as a direct requirement; Laravel remains the
  owning dependency.

## Failure Policy

If a safe version cannot resolve under current Laravel 13 and PHP 8.4 constraints,
stop and redesign the affected dependency instead of suppressing its advisory.
