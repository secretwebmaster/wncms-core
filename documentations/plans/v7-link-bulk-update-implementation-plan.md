# WNCMS v7 Link Bulk Update Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an atomic, dry-run-first `wncms:links:bulk-update` command for AI-safe bulk updates of Link `url` and `sort` fields.

**Architecture:** Extend `LinkAutomationService` with one bulk planner and one guarded transactional writer that reuse the existing actor resolver, mutation guard, audit service, target normalization, and cache policy. The CLI accepts a JSON target list, returns the existing automation result envelope, and writes one audit row per changed Link with a shared run ID.

**Tech Stack:** PHP 8.4, Laravel 13, Artisan, Eloquent transactions, PHPUnit 12, WNCMS automation services.

## Global Constraints

- Work on `codex/v7-ai-first`; do not push or merge.
- Do not modify or commit `updates/update_core_7.0.0.php`; official release artifacts remain last.
- Use test-driven development: record the failing command test before production code, then record the passing run.
- Preserve existing WNCMS PHP formatting, full function PHPDoc, automation envelopes, and dynamic command registration.
- Accept 1 to 100 unique items through `--items=` JSON; every item requires `identifier` and may contain only `url` and/or `sort`.
- Treat the batch as atomic and fail before writes for malformed input, duplicates, missing targets, invalid fields, permission failures, or website-scope failures.
- Default to dry-run; real writes require `--force` and an actor with `link_edit`.
- Re-resolve and guard every target inside one transaction before writing; flush `links` cache once after commit only when something changed.
- Dispatch no bulk-update hooks because the existing backend Link bulk-update path has none.
- Write one audit per changed Link with one shared run ID; do not audit no-op items.
- Update English, zh-CN, and zh-TW manuals plus all four changelog mirrors in the same feature commit.

---

### Task 1: Guarded Link Bulk Update Command

**Files:**
- Create: `src/Console/Commands/BulkUpdateLinks.php`
- Modify: `src/Services/Automation/LinkAutomationService.php`
- Modify: `tests/Feature/LinkAutomationCommandTest.php`
- Modify: `documentations/manual/developer/command/overview.md`
- Modify: `documentations/manual/developer/manager/link-manager.md`
- Modify: `documentations/manual/zh-CN/developer/command/overview.md`
- Modify: `documentations/manual/zh-CN/developer/manager/link-manager.md`
- Modify: `documentations/manual/zh-TW/developer/command/overview.md`
- Modify: `documentations/manual/zh-TW/developer/manager/link-manager.md`
- Modify: `documentations/plans/v7-ai-first-coverage-matrix.md`
- Modify: `documentations/plans/v7-ai-first-mutation-contract.md`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Consumes: `LinkAutomationService::planUpdate()`, `AutomationActorResolver`, `MutationGuardService`, `MutationAuditService`, and the standard automation result envelope.
- Produces: `LinkAutomationService::planBulkUpdate(array $items, array $options = []): array` and `LinkAutomationService::bulkUpdate(array $items, array $options = []): array`.
- Produces CLI: `wncms:links:bulk-update --items='[{"identifier":1,"url":"https://example.com","sort":10}]' [--website=1] [--actor-user=1] [--dry-run] [--force] [--json]`.

- [ ] **Step 1: Write focused failing command tests**

Add tests that assert:

```php
public function test_links_bulk_update_outputs_atomic_dry_run_without_writing(): void
{
    $first = Link::create($this->linkData(['url' => 'https://example.com/first', 'sort' => 10]));
    $second = Link::create($this->linkData(['url' => 'https://example.com/second', 'sort' => 20]));
    $beforeAuditCount = MutationAudit::count();

    $exitCode = Artisan::call('wncms:links:bulk-update', [
        '--items' => json_encode([
            ['identifier' => $first->id, 'url' => 'https://example.com/first-updated'],
            ['identifier' => $second->slug, 'sort' => 30],
        ]),
        '--json' => true,
    ]);
    $decoded = json_decode(trim(Artisan::output()), true);

    $this->assertSame(0, $exitCode);
    $this->assertSame(202, $decoded['code']);
    $this->assertTrue($decoded['data']['plan']['atomic']);
    $this->assertSame(['requested' => 2, 'changed' => 2, 'noop' => 0], $decoded['data']['plan']['summary']);
    $this->assertSame('https://example.com/first', $first->fresh()->url);
    $this->assertSame(20, $second->fresh()->sort);
    $this->assertSame($beforeAuditCount, MutationAudit::count());
}

public function test_links_bulk_update_force_updates_changed_targets_and_audits_each_change(): void
{
    $admin = $this->automationAdmin();
    $changed = Link::create($this->linkData(['url' => 'https://example.com/changed-before']));
    $noop = Link::create($this->linkData(['sort' => 20]));
    $beforeAuditCount = MutationAudit::count();

    $exitCode = Artisan::call('wncms:links:bulk-update', [
        '--items' => json_encode([
            ['identifier' => $changed->id, 'url' => 'https://example.com/changed-after'],
            ['identifier' => $noop->id, 'sort' => 20],
        ]),
        '--actor-user' => $admin->id,
        '--force' => true,
        '--json' => true,
    ]);
    $decoded = json_decode(trim(Artisan::output()), true);
    $audit = MutationAudit::query()->latest('id')->first();

    $this->assertSame(0, $exitCode);
    $this->assertSame(200, $decoded['code']);
    $this->assertSame(['requested' => 2, 'changed' => 1, 'noop' => 1], $decoded['data']['summary']);
    $this->assertSame('https://example.com/changed-after', $changed->fresh()->url);
    $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
    $this->assertSame($decoded['data']['run_id'], $audit->run_id);
    $this->assertSame($changed->id, $audit->model_id);
}

public function test_links_bulk_update_rejects_invalid_or_duplicate_items_without_writing(): void
{
    $link = Link::create($this->linkData());
    $payloads = [
        '{broken',
        '[]',
        json_encode([['identifier' => $link->id, 'name' => 'unsupported']]),
        json_encode([
            ['identifier' => $link->id, 'sort' => 1],
            ['identifier' => $link->slug, 'sort' => 2],
        ]),
    ];

    foreach ($payloads as $payload) {
        $exitCode = Artisan::call('wncms:links:bulk-update', ['--items' => $payload, '--json' => true]);
        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(422, $decoded['code']);
        $this->assertSame(10, $link->fresh()->sort);
    }
}

public function test_links_bulk_update_is_atomic_for_missing_permission_or_scoped_target(): void
{
    $first = Link::create($this->linkData(['sort' => 10]));
    $second = Link::create($this->linkData(['sort' => 20]));
    $admin = $this->automationAdmin();
    $payload = json_encode([
        ['identifier' => $first->id, 'sort' => 11],
        ['identifier' => 'missing-' . uniqid(), 'sort' => 21],
    ]);

    $missingExitCode = Artisan::call('wncms:links:bulk-update', [
        '--items' => $payload,
        '--actor-user' => $admin->id,
        '--force' => true,
        '--json' => true,
    ]);
    $missing = json_decode(trim(Artisan::output()), true);

    $member = User::create([
        'username' => 'bulk-update-member-' . uniqid(),
        'email' => 'bulk-update-member-' . uniqid() . '@example.com',
        'password' => Hash::make('wncms.cc'),
        'email_verified_at' => now(),
    ]);
    $member->assignRole('member');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $deniedExitCode = Artisan::call('wncms:links:bulk-update', [
        '--items' => json_encode([['identifier' => $first->id, 'sort' => 11]]),
        '--actor-user' => $member->id,
        '--force' => true,
        '--json' => true,
    ]);
    $denied = json_decode(trim(Artisan::output()), true);

    $this->assertSame(1, $missingExitCode);
    $this->assertSame(404, $missing['code']);
    $this->assertSame(1, $deniedExitCode);
    $this->assertSame(403, $denied['code']);
    $this->assertSame(10, $first->fresh()->sort);
    $this->assertSame(20, $second->fresh()->sort);
}

public function test_links_bulk_update_rejects_more_than_one_hundred_items(): void
{
    $items = array_map(
        fn(int $index) => ['identifier' => $index + 100000, 'sort' => $index],
        range(0, 100)
    );
    $exitCode = Artisan::call('wncms:links:bulk-update', [
        '--items' => json_encode($items),
        '--json' => true,
    ]);
    $decoded = json_decode(trim(Artisan::output()), true);

    $this->assertSame(1, $exitCode);
    $this->assertSame(422, $decoded['code']);
    $this->assertSame(['maximum:100'], $decoded['errors']['items']);
}
```

Add one website-mode test alongside the atomic test: bind the second Link to another website, execute with `--website` set to the first website, assert `404`, and assert neither Link changed.

- [ ] **Step 2: Run the focused tests and record RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php
```

Expected: the new tests fail because `wncms:links:bulk-update` is not defined.

- [ ] **Step 3: Implement input parsing and bulk planning**

Create `BulkUpdateLinks` with the exact command signature from **Interfaces**. Decode `--items` into an array and return a structured `422` envelope for malformed JSON before calling the service.

Implement `planBulkUpdate()` so it:

```php
[
    'operation' => 'bulk_update',
    'atomic' => true,
    'items' => [
        [
            'identifier' => 1,
            'status' => 'change',
            'plan' => [],
        ],
    ],
    'summary' => [
        'requested' => 1,
        'changed' => 1,
        'noop' => 0,
    ],
]
```

Validate the 1-100 limit, unique resolved target IDs, required `identifier`, allowed keys (`identifier`, `url`, `sort`), non-empty `url` when supplied, and at least one patch field per item.

- [ ] **Step 4: Implement atomic guarded writes**

Implement `bulkUpdate()` with these exact outcomes:

- dry-run success: `202`;
- successful write or all-no-op write: `200`;
- malformed/domain input: `422`;
- missing actor: `401`;
- permission/site denial: `403`;
- target missing or scoped out: `404`;
- cancelled/stale mutation conflict: `409`.

Resolve and guard all targets again inside one database transaction before applying updates. If any target fails, return without writes. Use one generated run ID for all changed-item audits, write no audit for no-op items, and flush `links` once after a committed batch with changes.

- [ ] **Step 5: Run focused and adjacent tests**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkHookIntegrationTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
```

Expected: all tests pass with pristine output.

- [ ] **Step 6: Update public docs, coverage, contract, and changelogs**

Document the JSON schema, atomic behavior, 100-item limit, permission/site checks, audit policy, error codes, and examples in all three manual locales. Update coverage/contract wording and add aligned `v7.0.0-alpha1` behavior bullets to all four changelog files without modifying installer or updater metadata.

- [ ] **Step 7: Verify and commit**

Run:

```bash
find src/Console/Commands src/Services/Automation -type f \( -name 'BulkUpdateLinks.php' -o -name 'LinkAutomationService.php' \) -print0 | xargs -0 -n1 /www/server/php/84/bin/php -l
git diff --check
/www/server/php/84/bin/php vendor/bin/testbench wncms:check-backend-api-v2-parity --coverage --no-ansi
```

Then stage only Task 1 files and commit:

```bash
git commit -m "feat(v7): add guarded Link bulk update command"
```
