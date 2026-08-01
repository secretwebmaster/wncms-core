# WNCMS v7 Alpha 1 Stabilization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the `v7.0.0-alpha1` branch test-clean, reproducible with local Composer path repositories, and internally version-consistent without starting the official `7.0.0` release.

**Architecture:** Initialize the shared auth-view website value at provider boot and replace it with the resolved website only when WNCMS is installed. Keep local Composer development on stable constraints by declaring explicit versions for the four path repositories, while leaving the ignored lock file untouched. Finish by synchronizing installer metadata with the already-established alpha changelog version.

**Tech Stack:** PHP 8.4, Laravel 13, Orchestra Testbench 11, PHPUnit 12, Composer 2, WNCMS service providers and release metadata.

## Global Constraints

- Work on branch `codex/v7-ai-first` in the existing checkout.
- Use `/www/server/php/84/bin/php` for PHP and PHPUnit commands.
- Target exactly `7.0.0-alpha1`; do not start the official `7.0.0` release gate.
- Do not read, edit, stage, or commit the existing untracked `updates/update_core_7.0.0.php`.
- Do not create `updates/update_core_7.0.0-alpha1.php`; preview releases do not ship updater files.
- Keep `composer.lock` ignored, byte-for-byte unmodified, and uncommitted.
- Keep `minimum-stability` as `stable` and `prefer-stable` as `true`.
- Do not perform a broad dependency upgrade.
- Apply WNCMS full PHPDoc to every new or modified PHP method.
- Synchronize behavior-changing release notes across `CHANGELOG.md`, `_en`, `_zh_CN`, and `_ja`.
- Complete each task with TDD where behavior changes, independent review, and its own commit.

---

### Task 1: Restore the auth website default

**Files:**
- Modify: `src/Providers/WncmsServiceProvider.php:440-459`
- Modify: `tests/Feature/GoogleLoginTest.php`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Consumes: `WncmsServiceProvider::loadGlobalVariables(): void`, `wncms_is_installed()`, and `wncms()->website()->get()`.
- Produces: every rendered view receives a defined `website` value; it is `null` before installation and the resolved website after installation.

- [ ] **Step 1: Add the auth-layout fallback regression test**

Add this method to `tests/Feature/GoogleLoginTest.php` before changing production code:

```php
/**
 * Render the auth layout with system metadata when no website is shared.
 *
 * @return void
 */
public function test_auth_layout_uses_system_metadata_when_website_is_unavailable(): void
{
    $html = view('wncms::auth.login')->render();

    $this->assertStringContainsString('<title>WNCMS</title>', $html);
}
```

This test catches removal of the provider-level default because direct rendering must not depend on an installed website.

- [ ] **Step 2: Run the regression and confirm RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/GoogleLoginTest.php --filter=auth_layout_uses_system_metadata_when_website_is_unavailable
```

Expected before the fix: failure caused by `Undefined variable $website` in `resources/views/layouts/auth.blade.php`.

- [ ] **Step 3: Implement the minimal provider fix**

Normalize the method PHPDoc and initialize the shared value before the installation guard:

```php
/**
 * Setup shared view variables and composers.
 *
 * @return void
 */
protected function loadGlobalVariables(): void
{
    View::share('wncms', wncms());
    View::share('website', null);

    if (function_exists('wncms_is_installed') && wncms_is_installed()) {
        View::share('website', wncms()->website()->get());

        View::composer('*', function ($view) {
            if (Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'frontend.')) {
                $view->with('user', auth()->user());
            }
        });
    }
}
```

Do not move website resolution into the Blade layout or duplicate it across auth controllers.

- [ ] **Step 4: Add the behavior-focused changelog entry in all four locales**

Append one flat bullet under the existing `v7.0.0-alpha1` heading in each file:

```markdown
- Auth layouts now fall back to system metadata when no active website is available, preventing login and registration pages from failing during early application boot.
```

```markdown
- 當沒有可用的目前網站時，認證版面會改用系統中繼資料，避免登入與註冊頁面在應用程式啟動初期發生錯誤。
```

```markdown
- 当没有可用的当前网站时，认证页面会改用系统元数据，避免登录与注册页面在应用程序启动初期发生错误。
```

```markdown
- 有効な現在のウェブサイトがない場合、認証レイアウトはシステムメタデータへフォールバックし、アプリケーション起動初期のログイン・登録ページの失敗を防止します。
```

Use the snippets respectively in `_en`, default, `_zh_CN`, and `_ja`.

- [ ] **Step 5: Run focused GREEN verification**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/GoogleLoginTest.php
```

Expected: the new regression and all existing Google login flows pass with no warnings.

- [ ] **Step 6: Run the complete package suite**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit
```

Expected: exit code `0`, including the three auth-page tests that previously failed.

- [ ] **Step 7: Run static checks and commit**

Run:

```bash
/www/server/php/84/bin/php -l src/Providers/WncmsServiceProvider.php
/www/server/php/84/bin/php -l tests/Feature/GoogleLoginTest.php
git diff --check
git status --short
```

Stage only the provider, Google test, and four changelogs, then commit:

```bash
git commit -m "fix(v7): define auth website fallback"
```

---

### Task 2: Stabilize local Composer path repositories

**Files:**
- Modify: `composer.json:35-65`
- Verify without modification: `composer.lock`

**Interfaces:**
- Consumes: the four existing path repositories and their stable root requirements.
- Produces: Composer resolves each local symlink as the matching stable version while consumer projects remain unaffected because dependency repository declarations are ignored outside the root package.

- [ ] **Step 1: Record the ignored lock-file checksum**

Run:

```bash
git check-ignore -v composer.lock
sha256sum composer.lock
```

Record the checksum in the task report. Do not stage, delete, or regenerate the file.

- [ ] **Step 2: Add explicit versions to all four path repositories**

Extend each existing `options` object without changing `symlink: true`:

```json
"options": {
    "symlink": true,
    "versions": {
        "secretwebmaster/laravel-localization": "2.4.1"
    }
}
```

Use the same structure for the remaining repositories with these exact mappings:

```text
secretwebmaster/laravel-optionable = 2.1.1
secretwebmaster/wncms-tags = 1.8.1
secretwebmaster/wncms-translatable = 1.4.1
```

- [ ] **Step 3: Validate JSON and Composer metadata**

Run:

```bash
/www/server/php/84/bin/php -r 'json_decode(file_get_contents("composer.json"), true, 512, JSON_THROW_ON_ERROR);'
composer validate --no-check-publish --no-check-lock
```

Expected: both commands exit `0`. The existing ignored stale-lock warning may still be printed and must be reported rather than hidden.

- [ ] **Step 4: Prove stable dependency resolution without writes**

Run this dry-run only:

```bash
composer update secretwebmaster/laravel-localization secretwebmaster/laravel-optionable secretwebmaster/wncms-tags secretwebmaster/wncms-translatable laravel/mcp --with-dependencies --dry-run --no-install --no-ansi
```

Expected: dependency resolution succeeds under `minimum-stability: stable`; no package is installed or updated on disk.

- [ ] **Step 5: Confirm the lock file and dependency policy are unchanged**

Run:

```bash
sha256sum composer.lock
git status --short
git diff -- composer.json
```

The checksum must match Step 1. `composer.json` must still contain `minimum-stability: stable` and `prefer-stable: true`.

- [ ] **Step 6: Commit only Composer root metadata**

Stage only `composer.json`, verify `git diff --cached --name-only`, then commit:

```bash
git commit -m "build(v7): stabilize local path versions"
```

---

### Task 3: Synchronize alpha release metadata

**Files:**
- Modify: `config/installer.php:14`
- Verify: `documentations/change/CHANGELOG.md`
- Verify: `documentations/change/CHANGELOG_en.md`
- Verify: `documentations/change/CHANGELOG_zh_CN.md`
- Verify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Consumes: the established changelog target `v7.0.0-alpha1` dated `2026-07-27`.
- Produces: `config('installer.version') === '7.0.0-alpha1'` with no alpha updater file.

- [ ] **Step 1: Update the installer version**

Change the existing entry to the exact WNCMS array-arrow style:

```php
'version' => '7.0.0-alpha1',
```

Do not change installer behavior or other requirements.

- [ ] **Step 2: Verify all changelog mirrors already target the same alpha**

Run:

```bash
rg -n -m1 '^## v' documentations/change/CHANGELOG.md documentations/change/CHANGELOG_en.md documentations/change/CHANGELOG_zh_CN.md documentations/change/CHANGELOG_ja.md
```

Expected: each first version heading is exactly `## v7.0.0-alpha1 2026-07-27`. Do not add version-management bullets.

- [ ] **Step 3: Verify runtime metadata and the alpha updater rule**

Run:

```bash
/www/server/php/84/bin/php -r '$config = require "config/installer.php"; exit(($config["version"] ?? null) === "7.0.0-alpha1" ? 0 : 1);'
git ls-files 'updates/update_core_7.0.0-alpha1.php'
```

Expected: the PHP command exits `0`; the tracked-file query prints nothing.

- [ ] **Step 4: Run syntax, scope, and diff checks**

Run:

```bash
/www/server/php/84/bin/php -l config/installer.php
git diff --check
git status --short
git diff --cached --name-only
```

Before staging, the only task change must be `config/installer.php`; the existing untracked official updater must remain unstaged and unread.

- [ ] **Step 5: Commit the alpha metadata synchronization**

Stage only `config/installer.php`, verify the staged file list again, then commit:

```bash
git commit -m "chore(v7): align alpha installer version"
```

---

## Final Cross-Task Verification

After all three task reviews are clean:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit
composer validate --no-check-publish --no-check-lock
/www/server/php/84/bin/php -r '$config = require "config/installer.php"; exit(($config["version"] ?? null) === "7.0.0-alpha1" ? 0 : 1);'
git diff --check
git status --short
```

The full suite must pass. Composer validation must exit `0`, with the known ignored-lock warning reported if present. The only permitted untracked file is `updates/update_core_7.0.0.php`; it remains outside this plan and outside every commit.

The official `7.0.0` updater review, WebUI install/upgrade smoke tests, final changelog and installer synchronization, tag, push, and publication remain a separately authorized release gate.
