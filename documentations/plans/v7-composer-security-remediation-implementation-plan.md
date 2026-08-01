# WNCMS v7 Composer Security Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove all eight current Composer advisories and prevent vulnerable production dependency resolution for WNCMS v7 consumers.

**Architecture:** Keep Socialite as the owner of phpseclib and block its vulnerable releases with a root conflict; raise the direct Media Library floor; update the ignored local lock only for dev-only YAML verification. Record the user-visible security hardening in all four v7 changelogs.

**Tech Stack:** Composer 2, PHP 8.4, Laravel 13, PHPUnit 12.

## Global Constraints

- Conflict with `phpseclib/phpseclib <3.0.54`.
- Require `spatie/laravel-medialibrary ^11.23`.
- Resolve local dev-only `symfony/yaml` to at least `8.0.12` without adding it as a root dependency.
- Keep `composer.lock` ignored and uncommitted.
- Do not read, edit, stage, or commit `updates/update_core_7.0.0.php`.
- Do not create an official release, tag, push, or broad dependency upgrade.

---

### Task 1: Enforce secure dependency floors

**Files:**
- Modify: `composer.json`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`
- Local verification only: `composer.lock` (ignored; never stage)

**Interfaces:**
- Consumes: Composer root dependency constraints and the existing `v7.0.0-alpha1` changelog sections.
- Produces: Composer constraints that reject vulnerable phpseclib and Media Library releases, plus a locally audited dependency graph with zero advisories.

- [ ] **Step 1: Reproduce the failing security gate**

Run:

```bash
composer audit --locked
```

Expected: exit code `3`, with eight advisories across `phpseclib/phpseclib`, `spatie/laravel-medialibrary`, and `symfony/yaml`.

- [ ] **Step 2: Add the minimal production safety constraints**

Change the Media Library requirement in `composer.json`:

```json
"spatie/laravel-medialibrary": "^11.23"
```

Add this top-level Composer conflict after `require-dev`:

```json
"conflict": {
    "phpseclib/phpseclib": "<3.0.54"
}
```

Do not add `symfony/yaml` to `require` or `require-dev`; it remains a transitive development dependency.

- [ ] **Step 3: Resolve only the affected dependency graph**

Run:

```bash
composer update phpseclib/phpseclib spatie/laravel-medialibrary symfony/yaml --with-all-dependencies --no-interaction
```

Expected: successful resolution with phpseclib `>=3.0.54`, Media Library `>=11.23.0,<12.0.0`, and YAML `>=8.0.12,<9.0.0`. Confirm `composer.lock` remains ignored.

- [ ] **Step 4: Prove vulnerable versions are rejected**

Run:

```bash
composer prohibits phpseclib/phpseclib 3.0.53 --locked
composer prohibits spatie/laravel-medialibrary 11.22.1 --locked
```

Expected: both commands identify `secretwebmaster/wncms-core` as the blocking root package.

- [ ] **Step 5: Re-run the security and Composer gates**

Run:

```bash
composer audit --locked
composer validate --no-check-publish
```

Expected: audit reports no advisories and validation exits successfully.

- [ ] **Step 6: Add one aligned security bullet to the four changelogs**

Append to the existing `v7.0.0-alpha1` list in `CHANGELOG_en.md`:

```markdown
- Raised production dependency safety floors to prevent installation of vulnerable phpseclib and Media Library releases.
```

Append the equivalent bullet to `CHANGELOG.md`:

```markdown
- 提高 production 相依套件的安全版本下限，避免安裝存在漏洞的 phpseclib 與 Media Library 版本。
```

Append the equivalent bullet to `CHANGELOG_zh_CN.md`:

```markdown
- 提高 production 依赖套件的安全版本下限，避免安装存在漏洞的 phpseclib 与 Media Library 版本。
```

Append the equivalent bullet to `CHANGELOG_ja.md`:

```markdown
- production 依存パッケージの安全な最低バージョンを引き上げ、脆弱な phpseclib と Media Library の導入を防止しました。
```

- [ ] **Step 7: Run the complete regression suite**

Run:

```bash
vendor/bin/phpunit
```

Expected: all tests pass with zero failures and zero errors.

- [ ] **Step 8: Verify and commit only the remediation**

Run:

```bash
git diff --check
git status --short
git add composer.json documentations/change/CHANGELOG.md documentations/change/CHANGELOG_en.md documentations/change/CHANGELOG_zh_CN.md documentations/change/CHANGELOG_ja.md
git diff --cached --check
git diff --cached --name-status
git commit -m "fix(v7): enforce secure dependency floors"
```

Expected staged scope: exactly `composer.json` and the four changelogs. The ignored lock file and untracked updater are absent from the commit.
