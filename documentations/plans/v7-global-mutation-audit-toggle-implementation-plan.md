# WNCMS v7 Global Mutation Audit Toggle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a default-disabled global mutation audit switch, make CLI/API audit persistence conditional without changing their safety guards, and audit all six Link backend UI mutation routes when enabled.

**Architecture:** `MutationAuditService` is the single runtime gate and owns redaction, preview metadata, nullable persistence, and stable audit references. A new `BackendMutationAuditService` adapts successful UI model changes into the existing plan format. `LinkController` retains its current mutation behavior when audit is disabled and adds snapshots plus transactionally coupled audit writes only on the enabled path.

**Tech Stack:** PHP 8.4, Laravel 13, Eloquent transactions, Spatie tags/media, PHPUnit 12.

## Global Constraints

- The stable setting key is `enable_mutation_audit` under the Admin settings tab.
- The fallback and shipped default are `false`.
- Runtime code reads `config('wncms.mutation_audit.enabled', false)`; only provider boot resolves `gss('enable_mutation_audit', false)`.
- Disabled CLI/API mutations keep actor, permission, website-scope, validation, stale-state, force, and transaction safeguards.
- Disabled automation responses keep object-shaped audit metadata with `enabled=false` and nullable IDs.
- Enabled UI mutation and audit insert share one database transaction; an audit failure rolls back the mutation.
- Audit only committed, actual changes. Validation failures, no-ops, missing targets, caught exceptions, and rolled-back changes create no audit row.
- Bulk operations use one shared run ID, audit each changed Link once, and flush the `links` cache once after a changed commit.
- Do not add an audit viewer, retention/export policy, API bulk delete, mutation MCP, or auditing for non-Link UI domains.
- Do not read, edit, stage, or commit `updates/update_core_7.0.0.php`.
- Keep default locales synchronized: `en`, `zh_CN`, `zh_TW`, and `ja`; keep public manual structure aligned for English, `zh-CN`, and `zh-TW`.

---

### Task 1: Register and boot the global setting

**Files:**
- Modify: `config/wncms.php`
- Modify: `config/wncms-system-settings.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `lang/en/word.php`
- Modify: `lang/zh_CN/word.php`
- Modify: `lang/zh_TW/word.php`
- Modify: `lang/ja/word.php`
- Create: `tests/Feature/MutationAuditSettingTest.php`

**Interfaces:**
- Produces: `config('wncms.mutation_audit.enabled', false): bool` and the backend field `settings[enable_mutation_audit]`.
- Consumed by: `MutationAuditService::enabled()` in Task 2.

- [ ] **Step 1: Write failing setting registration and boot tests**

Create `MutationAuditSettingTest` using `DatabaseTransactions`. Cover these exact assertions:

```php
public function test_mutation_audit_setting_is_registered_under_admin_and_defaults_disabled(): void
{
    $adminFields = collect(config('wncms-system-settings.admin.tab_content'));

    $this->assertTrue($adminFields->contains(
        fn(array $field): bool => $field === ['type' => 'switch', 'name' => 'enable_mutation_audit']
    ));
    $this->assertFalse((bool) config('wncms.mutation_audit.enabled', false));
}

public function test_mutation_audit_setting_saves_and_boots_runtime_config(): void
{
    $this->withoutMiddleware();
    $this->put(route('settings.update'), [
        'settings' => ['enable_mutation_audit' => '1'],
    ])->assertRedirect();

    $this->assertSame('1', (string) gss('enable_mutation_audit', false, false));

    config(['wncms.mutation_audit.enabled' => false]);
    $provider = new \Wncms\Providers\WncmsServiceProvider($this->app);
    $method = new \ReflectionMethod($provider, 'loadSystemSettings');
    $method->invoke($provider);

    $this->assertTrue(config('wncms.mutation_audit.enabled'));
}
```

Add a third test loading each `lang/{locale}/word.php` array and asserting non-empty `enable_mutation_audit` and `enable_mutation_audit_description` values.

- [ ] **Step 2: Run the tests and verify RED**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/MutationAuditSettingTest.php
```

Expected: failures because the config field, runtime config mapping, and translations do not exist.

- [ ] **Step 3: Add the default config and Admin switch**

Add to `config/wncms.php`:

```php
'mutation_audit' => [
    'enabled' => false,
],
```

Add to `config/wncms-system-settings.php` under `admin.tab_content`:

```php
['type' => 'switch', 'name' => 'enable_mutation_audit'],
```

The generic settings Blade already renders a hidden `0` fallback and checkbox `1`; do not add a custom Blade control.

- [ ] **Step 4: Resolve the cached setting once during boot**

Call a new protected method from `loadSystemSettings()`:

```php
$this->loadMutationAuditSettings();
```

Implement it with a full PHPDoc:

```php
protected function loadMutationAuditSettings(): void
{
    config([
        'wncms.mutation_audit.enabled' => (bool) gss('enable_mutation_audit', false),
    ]);
}
```

- [ ] **Step 5: Add all four translation pairs**

Use these meanings:

```php
// en
'enable_mutation_audit' => 'Enable mutation audit',
'enable_mutation_audit_description' => 'Record successful backend UI, CLI, and API mutations. Enabling this adds snapshot and audit queries; disabling it removes mutation accountability.',

// zh_CN
'enable_mutation_audit' => '启用变更审计',
'enable_mutation_audit_description' => '记录成功的后台 UI、CLI 与 API 变更。启用后会增加快照与审计查询；停用后将不保留变更问责记录。',

// zh_TW
'enable_mutation_audit' => '啟用異動審計',
'enable_mutation_audit_description' => '記錄成功的後台 UI、CLI 與 API 異動。啟用後會增加快照與審計查詢；停用後將不保留異動問責記錄。',

// ja
'enable_mutation_audit' => '変更監査を有効化',
'enable_mutation_audit_description' => '成功したバックエンド UI、CLI、API の変更を記録します。有効化するとスナップショットと監査クエリが追加され、無効化すると変更の追跡記録は保存されません。',
```

- [ ] **Step 6: Run focused tests and commit**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/MutationAuditSettingTest.php
php -l src/Providers/WncmsServiceProvider.php
git diff --check
```

Expected: setting tests pass and syntax/diff checks exit zero.

Commit:

```bash
git add config/wncms.php config/wncms-system-settings.php src/Providers/WncmsServiceProvider.php lang/en/word.php lang/zh_CN/word.php lang/zh_TW/word.php lang/ja/word.php tests/Feature/MutationAuditSettingTest.php
git commit -m "feat(v7): add global mutation audit setting"
```

---

### Task 2: Make audit previews and persistence gate-aware

**Files:**
- Modify: `src/Services/Automation/MutationAuditService.php`
- Modify: `src/Services/Automation/LinkAutomationService.php`
- Create: `tests/Feature/MutationAuditServiceTest.php`
- Modify: `tests/Feature/LinkAutomationCommandTest.php`
- Modify: `tests/Feature/LinkApiV2ControllerTest.php`

**Interfaces:**
- Consumes: `config('wncms.mutation_audit.enabled', false)`.
- Produces:
  - `MutationAuditService::enabled(): bool`
  - `MutationAuditService::writeFromPlan(array $plan, array $overrides = []): ?MutationAudit`
  - `MutationAuditService::reference(?MutationAudit $audit = null): array{enabled: bool, id: int|null}`

- [ ] **Step 1: Write failing gate and response-shape tests**

In `MutationAuditServiceTest`, build a valid Link mutation plan and assert:

```php
config(['wncms.mutation_audit.enabled' => false]);
$preview = app(MutationAuditService::class)->previewFromPlan($plan);
$this->assertFalse($preview['enabled']);
$this->assertFalse($preview['will_write']);
$this->assertNull(app(MutationAuditService::class)->writeFromPlan($plan));
$this->assertSame(['enabled' => false, 'id' => null], app(MutationAuditService::class)->reference());
$this->assertSame($beforeCount, MutationAudit::count());
```

Then enable config and assert preview `enabled=true`, write persistence, and `reference($audit)` returns the persisted integer ID.

Add one CLI write and one API v2 write test with audit disabled. Each must prove the Link mutation succeeds, `mutation_audits` count is unchanged, and response audit metadata is object-shaped with `enabled=false` and `id=null` (bulk responses use `ids=[]`).

- [ ] **Step 2: Preserve existing audit-write tests explicitly**

At the start of `LinkAutomationCommandTest::setUp()` and `LinkApiV2ControllerTest::setUp()`, set:

```php
config(['wncms.mutation_audit.enabled' => true]);
```

Individual disabled tests override it to `false`. This keeps existing persistence assertions intentional instead of relying on the new shipped default.

- [ ] **Step 3: Run focused tests and verify RED**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/MutationAuditServiceTest.php tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkApiV2ControllerTest.php
```

Expected: failures because `enabled`, nullable writes, and stable disabled references are not implemented.

- [ ] **Step 4: Implement the single global gate**

Add to `MutationAuditService`:

```php
public function enabled(): bool
{
    return (bool) config('wncms.mutation_audit.enabled', false);
}

public function reference(?MutationAudit $audit = null): array
{
    return [
        'enabled' => $this->enabled(),
        'id' => $audit === null ? null : (int) $audit->getKey(),
    ];
}
```

Add `enabled` to preview output. Set preview `will_write` only when enabled, the plan is non-dry-run, the plan says it will write, and validation/guard status passes. Change `writeFromPlan()` return type to `?MutationAudit` and return `null` before building/persisting attributes when disabled.

- [ ] **Step 5: Update all five Link automation persistence call sites**

For create/update/delete, replace direct `$audit->getKey()` calls with:

```php
'audit' => app(MutationAuditService::class)->reference($audit),
```

For bulk update and bulk tag sync, append IDs only when `$audit !== null`, and add a top-level data audit object:

```php
'audit' => [
    'enabled' => app(MutationAuditService::class)->enabled(),
    'ids' => $audits,
],
```

Do not bypass any existing guard, stale-state, force, transaction, hook, or cache logic.

- [ ] **Step 6: Run focused tests and commit**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/MutationAuditServiceTest.php tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkApiV2ControllerTest.php
php -l src/Services/Automation/MutationAuditService.php
php -l src/Services/Automation/LinkAutomationService.php
git diff --check
```

Commit:

```bash
git add src/Services/Automation/MutationAuditService.php src/Services/Automation/LinkAutomationService.php tests/Feature/MutationAuditServiceTest.php tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkApiV2ControllerTest.php
git commit -m "feat(v7): gate automation mutation audits"
```

---

### Task 3: Add the backend UI audit adapter

**Files:**
- Create: `src/Services/Automation/BackendMutationAuditService.php`
- Create: `tests/Feature/BackendMutationAuditServiceTest.php`

**Interfaces:**
- Consumes: `MutationAuditService::enabled()`, `previewFromPlan()`, and nullable `writeFromPlan()`.
- Produces:
  - `BackendMutationAuditService::enabled(): bool`
  - `BackendMutationAuditService::snapshot(Model $model, array $relationships = []): array`
  - `BackendMutationAuditService::write(Model $model, string $domain, string $action, string $permission, array $before, array $after, array $websiteIds = [], array $relationshipChanges = [], ?string $runId = null, string $message = 'Backend mutation completed.'): ?MutationAudit`

- [ ] **Step 1: Write failing adapter tests**

Cover disabled nullable writes, enabled UI metadata, redaction, and stable model resolution. The enabled assertion must verify one row with:

```php
[
    'surface' => 'ui',
    'actor_type' => 'user',
    'actor_id' => $user->id,
    'domain' => 'links',
    'action' => 'update',
    'model_key' => 'link',
    'model_id' => $link->id,
    'website_ids' => [$website->id],
    'permission' => 'link_edit',
    'result_code' => 200,
    'result_status' => 'success',
]
```

Pass a relationship key containing `api_token` and assert its stored value is `[redacted]`.

- [ ] **Step 2: Run tests and verify RED**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/BackendMutationAuditServiceTest.php
```

Expected: failure because the adapter class does not exist.

- [ ] **Step 3: Implement the focused adapter**

`snapshot()` returns normalized model attributes plus caller-supplied relationship state:

```php
return [
    'attributes' => $model->attributesToArray(),
    'relationships' => $relationships,
];
```

`write()` immediately returns `null` when disabled. When enabled, it obtains `auth()->user()`, builds the existing plan keys (`operation`, `model_key`, `target`, `changes`, `relationships`, `safety`, `validation`, `guard`, `cache`, `hooks`, `dry_run=false`, `will_write=true`), creates a `surface=ui` preview, and delegates persistence to `MutationAuditService`. Use `Str::uuid()` when no run ID is supplied.

The adapter must not query Link tags, websites, media, or caches; those states are supplied by the controller.

- [ ] **Step 4: Run focused tests and commit**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/BackendMutationAuditServiceTest.php
php -l src/Services/Automation/BackendMutationAuditService.php
git diff --check
```

Commit:

```bash
git add src/Services/Automation/BackendMutationAuditService.php tests/Feature/BackendMutationAuditServiceTest.php
git commit -m "feat(v7): add backend mutation audit adapter"
```

---

### Task 4: Audit single Link backend mutations

**Files:**
- Modify: `src/Http/Controllers/Backend/LinkController.php`
- Create: `tests/Feature/LinkBackendMutationAuditTest.php`
- Modify: `tests/Feature/LinkHookIntegrationTest.php`

**Interfaces:**
- Consumes: `BackendMutationAuditService` and existing Link store/update/delete routes.
- Produces: enabled UI audit rows for `create`, `update`, and `delete`; disabled paths retain existing behavior without audit-only snapshots.

- [ ] **Step 1: Write failing disabled fast-path tests**

In `LinkBackendMutationAuditTest::setUp()`, disable middleware, act as an admin user, set Link website mode to `multi`, and configure the media model. With audit disabled, test store, update, and destroy. Assert mutations succeed, audit count is unchanged, and a `DB::listen()` query log contains no SQL referencing `mutation_audits`.

- [ ] **Step 2: Write failing enabled single-route tests**

Enable audit and assert:

- store creates one `surface=ui`, `action=create`, `permission=link_create` row after hooks, website/tag/media effects;
- update creates one `action=update`, `permission=link_edit` row only when attribute or relationship state changes;
- identical update creates no row;
- destroy creates one `action=delete`, `permission=link_delete` row preserving the pre-delete snapshot.

Add an update rollback test by binding a mock `BackendMutationAuditService` whose `write()` throws `RuntimeException`. Use `withoutExceptionHandling()`, expect the exception, and assert the original Link attributes remain in the database.

- [ ] **Step 3: Run tests and verify RED**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/LinkBackendMutationAuditTest.php tests/Feature/LinkHookIntegrationTest.php
```

Expected: missing UI audits and rollback coupling failures.

- [ ] **Step 4: Add Link-specific snapshot helpers**

Add protected helpers with full PHPDoc to `LinkController`:

```php
protected function mutationAuditService(): BackendMutationAuditService
protected function linkAuditState($link): array
protected function auditedMutation(callable $callback): mixed
```

`linkAuditState()` returns model attributes and deterministic, sorted relationship state for website IDs, `link_category` names, `link_tag` names, and media IDs grouped by `link_thumbnail`/`link_icon`. Only call it after `enabled()` passes.

`auditedMutation()` runs the callback directly when disabled and through `DB::transaction()` when enabled.

- [ ] **Step 5: Couple store and update to audit transactions**

Keep validation and before hooks in their current order. Put model/media/website/tag mutation, after hook, final snapshot, and audit insert inside `auditedMutation()`. Move cache flush after the transaction. For update, capture the before snapshot only when enabled and skip `write()` when before and after snapshots are identical.

Use fixed permissions and messages:

```php
// store
action: 'create', permission: 'link_create', message override: 'Link created.'

// update
action: 'update', permission: 'link_edit', message override: 'Link updated.'
```

- [ ] **Step 6: Override Link destroy**

When disabled, delegate to `parent::destroy($id)` unchanged. When enabled, find the Link, preserve existing not-found response, capture its full state, delete and write the `action=delete`/`permission=link_delete` audit in one transaction, then flush `links` after commit and return the existing translated redirect message.

- [ ] **Step 7: Run focused tests and commit**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/LinkBackendMutationAuditTest.php tests/Feature/LinkHookIntegrationTest.php
php -l src/Http/Controllers/Backend/LinkController.php
git diff --check
```

Commit:

```bash
git add src/Http/Controllers/Backend/LinkController.php tests/Feature/LinkBackendMutationAuditTest.php tests/Feature/LinkHookIntegrationTest.php
git commit -m "feat(v7): audit Link backend mutations"
```

---

### Task 5: Audit bulk Link backend mutations

**Files:**
- Modify: `src/Http/Controllers/Backend/LinkController.php`
- Modify: `tests/Feature/LinkBackendMutationAuditTest.php`

**Interfaces:**
- Consumes: the adapter and Link snapshot helpers from Tasks 3-4.
- Produces: enabled UI audit rows for `bulk_delete`, `bulk_update`, and `bulk_sync_tags`, with one run ID per request.

- [ ] **Step 1: Write failing bulk-route tests**

Cover these cases:

- disabled bulk delete/update/tag sync mutate successfully and create no audit rows;
- enabled bulk delete writes one `action=bulk_delete`, `permission=link_bulk_delete` row per deleted Link with one shared run ID;
- enabled bulk update audits changed Links only, uses `permission=link_edit`, skips missing/no-op items, and flushes cache once after commit;
- enabled bulk tag sync audits changed Links only for `sync`, `attach`, and `detach`, uses one run ID and `permission=link_edit`;
- invalid/empty bulk tag input creates no rows;
- a mocked adapter failure during the second item rolls back every model and audit change in the batch.

- [ ] **Step 2: Run tests and verify RED**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/LinkBackendMutationAuditTest.php --filter=bulk
```

Expected: failures because inherited bulk delete and existing bulk methods do not write audits or guarantee audit-coupled atomicity.

- [ ] **Step 3: Override bulk delete with enabled/disabled paths**

When disabled, call `parent::bulk_delete($request)` unchanged. When enabled, normalize IDs exactly as the base controller, load existing Links, generate one `Str::uuid()` run ID, and transactionally capture/delete/audit each Link. Flush `links` once only when at least one Link was deleted, then preserve the existing AJAX/non-AJAX translated responses and count.

- [ ] **Step 4: Gate and transact bulk update**

Preserve the existing disabled loop. On the enabled path, generate one run ID and transactionally process existing targets. Capture before state only for items with a real URL/sort change, perform the update, capture after state, and write one `bulk_update` audit per changed Link. Keep skipped missing/no-op behavior and the current JSON schema. Flush once after a changed commit.

- [ ] **Step 5: Gate and transact bulk tag sync**

Keep parsing, missing-ID, missing-Link, and action validation responses unchanged. Preserve the disabled mutation path. On the enabled path, run the entire batch in one transaction, capture each Link before and after its requested tag operation, audit only changed states with one run ID, and let an audit exception roll back the batch. Catch the exception through the existing failure response and do not flush on rollback/no-op.

- [ ] **Step 6: Run focused and controller regression tests**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/LinkBackendMutationAuditTest.php tests/Feature/LinkHookIntegrationTest.php
php -l src/Http/Controllers/Backend/LinkController.php
git diff --check
```

Commit:

```bash
git add src/Http/Controllers/Backend/LinkController.php tests/Feature/LinkBackendMutationAuditTest.php
git commit -m "feat(v7): audit Link backend bulk mutations"
```

---

### Task 6: Synchronize public docs, coverage, and changelogs

**Files:**
- Modify: `documentations/manual/developer/manager/link-manager.md`
- Modify: `documentations/manual/zh-CN/developer/manager/link-manager.md`
- Modify: `documentations/manual/zh-TW/developer/manager/link-manager.md`
- Modify: `documentations/plans/v7-ai-first-coverage-matrix.md`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Consumes: final behavior and response shapes from Tasks 1-5.
- Produces: stable user-facing enablement, performance, accountability, and Link UI coverage documentation.

- [ ] **Step 1: Add aligned manual sections**

Add a `Global mutation audit setting` section in English and structurally matching `全域異動審計設定`/`全局变更审计设置` sections. Document:

- backend path: Settings → Admin → `enable_mutation_audit`;
- default disabled and runtime config key;
- disabled performance path and loss of CLI/API accountability;
- recommendation to enable on sites using automation writes;
- enabled Link UI route coverage for create/update/delete/bulk delete/bulk update/bulk tag sync;
- automation shapes: `audit.enabled`, nullable `audit.id`, and bulk `audit.ids`;
- transaction, no-op, rollback, shared-run, redaction, and post-commit cache rules.

- [ ] **Step 2: Update coverage tracking**

Change the Links safety cell from UI audit `Partial` to complete UI/CLI/API audit coverage while retaining two explicit gaps: guarded API bulk delete and mutation MCP. Remove completed concrete task 1 and renumber the remaining tasks.

- [ ] **Step 3: Add one aligned behavior bullet to all changelogs**

Use these entries under `v7.0.0-alpha1`:

```markdown
// en
- Added a default-disabled global mutation audit setting and transactionally audited all Link backend UI writes when enabled, while CLI/API responses preserve stable disabled audit metadata.

// zh-TW
- 新增預設停用的全域異動審計設定；啟用後會以交易方式審計所有 Link 後台 UI 寫入，CLI/API 在停用時仍保留穩定的審計 metadata。

// zh-CN
- 新增默认停用的全局变更审计设置；启用后会以事务方式审计所有 Link 后台 UI 写入，CLI/API 在停用时仍保留稳定的审计 metadata。

// ja
- デフォルト無効のグローバル変更監査設定を追加し、有効時は Link バックエンド UI の全書き込みをトランザクション内で監査します。無効時も CLI/API は安定した監査 metadata を維持します。
```

- [ ] **Step 4: Verify locale structure and commit**

Run:

```bash
rg -n "enable_mutation_audit|audit\.enabled|audit\.ids" documentations/manual/developer/manager/link-manager.md documentations/manual/zh-CN/developer/manager/link-manager.md documentations/manual/zh-TW/developer/manager/link-manager.md
git diff --check
```

Commit:

```bash
git add documentations/manual/developer/manager/link-manager.md documentations/manual/zh-CN/developer/manager/link-manager.md documentations/manual/zh-TW/developer/manager/link-manager.md documentations/plans/v7-ai-first-coverage-matrix.md documentations/change/CHANGELOG.md documentations/change/CHANGELOG_en.md documentations/change/CHANGELOG_zh_CN.md documentations/change/CHANGELOG_ja.md
git commit -m "docs(v7): document global mutation audit toggle"
```

---

### Task 7: Run the final release-quality gate

**Files:**
- Verify only: all files changed by Tasks 1-6.

**Interfaces:**
- Consumes: complete implementation and docs.
- Produces: evidence that the alpha branch is test-clean, secure, and scoped without starting a release.

- [ ] **Step 1: Run PHP syntax checks**

Run:

```bash
php -l src/Providers/WncmsServiceProvider.php
php -l src/Services/Automation/MutationAuditService.php
php -l src/Services/Automation/BackendMutationAuditService.php
php -l src/Services/Automation/LinkAutomationService.php
php -l src/Http/Controllers/Backend/LinkController.php
php -l tests/Feature/MutationAuditSettingTest.php
php -l tests/Feature/MutationAuditServiceTest.php
php -l tests/Feature/BackendMutationAuditServiceTest.php
php -l tests/Feature/LinkBackendMutationAuditTest.php
```

- [ ] **Step 2: Run focused feature gates**

Run:

```bash
vendor/bin/phpunit --colors=never tests/Feature/MutationAuditSettingTest.php tests/Feature/MutationAuditServiceTest.php tests/Feature/BackendMutationAuditServiceTest.php tests/Feature/LinkBackendMutationAuditTest.php tests/Feature/LinkHookIntegrationTest.php tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkApiV2ControllerTest.php
```

- [ ] **Step 3: Run full regression and dependency gates**

Run:

```bash
vendor/bin/phpunit --colors=never
composer audit --locked --no-interaction
composer validate --no-check-publish
```

Expected: all PHPUnit tests pass, Composer reports zero advisories, and `composer.json` is valid.

- [ ] **Step 4: Verify repository scope**

Run:

```bash
git diff --check
git status --short --branch
git log --oneline --decorate -12
```

Confirm `updates/update_core_7.0.0.php` remains untracked and absent from every task commit. Do not tag, push, publish, or modify release metadata.
