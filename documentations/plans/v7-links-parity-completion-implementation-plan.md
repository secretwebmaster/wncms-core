# WNCMS v7 Links Parity Completion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete guarded Link bulk tag synchronization, consolidate Link CLI rendering, finalize the safe Links API v2 contract, and add an opt-in local MCP server with read-only Link tools.

**Architecture:** `LinkAutomationService` remains the single Link automation boundary. CLI, API v2, and MCP adapt their inputs and outputs to the stable automation envelope; writes keep the existing dry-run, actor, authorization, transaction, audit, hook, website-scope, and cache rules.

**Tech Stack:** PHP 8.4, Laravel 13, Artisan, Eloquent, Sanctum, PHPUnit 12, Laravel MCP `^0.9`, and WNCMS automation services.

## Global Constraints

- Work on `codex/v7-ai-first`; do not push or merge.
- Do not inspect, modify, stage, or commit `updates/update_core_7.0.0.php`.
- Use test-driven development and record focused RED and GREEN commands in each task report.
- Use full WNCMS PHPDoc on new or changed methods and preserve WNCMS formatting.
- Preserve the `code`, `status`, `message`, `data`, `meta`, and `errors` automation envelope.
- Default mutations to dry-run; writes require explicit force and an authorized actor.
- Keep Link writes inside `LinkAutomationService`; API and CLI controllers may only adapt transport data.
- Keep MCP disabled by default, local-only, and read-only.
- Update stable public behavior in English, zh-CN, and zh-TW manuals and keep the four changelog mirrors aligned.
- Commit each completed task separately after its focused verification and independent review.
- The current full-suite baseline has 138 tests with 3 unrelated `GoogleLoginTest` failures caused by an undefined `$website` view variable; do not expand this plan to fix them.

---

### Task 1: Guarded Link Bulk Tag Synchronization

**Files:**
- Create: `src/Console/Commands/BulkSyncLinkTags.php`
- Create: `src/Services/Automation/BulkSyncTagsAbortException.php`
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
- Consumes: the existing bulk-update guard, transaction, audit, website-scope, and cache patterns in `LinkAutomationService`.
- Produces: `LinkAutomationService::planBulkSyncTags(array $identifiers, string $action, array $tags, array $options = []): array`.
- Produces: `LinkAutomationService::bulkSyncTags(array $identifiers, string $action, array $tags, array $options = []): array`.
- Produces CLI: `wncms:links:bulk-sync-tags --identifiers='[1,"slug"]' --action=sync|attach|detach [--categories='["Partners"]'] [--tags='["Featured"]'] [--website=1] [--actor-user=1] [--dry-run] [--force] [--json]`.

- [ ] **Step 1: Write the focused command tests**

Add tests whose names and primary assertions are:

```php
public function test_links_bulk_sync_tags_outputs_atomic_dry_run_without_writing(): void
{
    // Two ID/slug targets, supplied category and tag names.
    // Assert code 202, atomic=true, requested=2, changed=2, no pivot writes,
    // no mutation audits, and unchanged cache-observable state.
}

public function test_links_bulk_sync_tags_force_applies_sync_attach_and_detach(): void
{
    // Exercise each action and assert an omitted tag type remains unchanged.
}

public function test_links_bulk_sync_tags_audits_only_changes_with_one_run_id(): void
{
    // One changed Link and one no-op Link; assert one audit and one shared run_id.
}

public function test_links_bulk_sync_tags_rejects_invalid_input_without_partial_writes(): void
{
    // Cover malformed JSON, invalid action, empty tags, non-scalar tag names,
    // duplicate resolved targets, and 101 identifiers; assert code 422.
}

public function test_links_bulk_sync_tags_rejects_missing_actor_permission_and_scoped_target(): void
{
    // Assert 401, 403, and 404 outcomes with no tag or audit changes.
}

public function test_links_bulk_sync_tags_rolls_back_and_rejects_stale_tag_state(): void
{
    // Force a later target failure and an in-transaction state mismatch;
    // assert rollback and conflict code 409.
}
```

- [ ] **Step 2: Run the focused tests and record RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php --filter='test_links_bulk_sync_tags'
```

Expected: the tests fail because `wncms:links:bulk-sync-tags` is not registered.

- [ ] **Step 3: Implement strict input parsing and planning**

The command must decode each JSON option as a list and map transport keys:

```php
$tags = [
    'link_categories' => $this->decodeListOption('categories'),
    'link_tags' => $this->decodeListOption('tags'),
];
```

The planner must return this stable shape:

```php
[
    'operation' => 'bulk_sync_tags',
    'action' => 'sync',
    'atomic' => true,
    'items' => [
        [
            'identifier' => 1,
            'status' => 'change',
            'before' => [
                'link_categories' => [],
                'link_tags' => [],
            ],
            'after' => [
                'link_categories' => ['Partners'],
                'link_tags' => ['Featured'],
            ],
        ],
    ],
    'summary' => [
        'requested' => 1,
        'changed' => 1,
        'noop' => 0,
    ],
]
```

Accept 1–100 identifiers, normalize names with `trim`, reject non-scalar or
empty names, de-duplicate deterministically, require at least one non-empty
tag type, and leave omitted or empty tag types unchanged.

- [ ] **Step 4: Implement the guarded atomic writer**

Mirror `bulkUpdate()` with one transaction and an operation-specific abort
exception. Re-plan with row locks, compare approved and fresh tag states, guard
every target again, then use:

```php
$link->syncTagsWithType($names, $tagType);
$link->attachTags($names, $tagType);
$link->detachTags($names, $tagType);
```

Return `202` for previews, `200` for completed/no-op execution, `422` for
validation, `401` for a missing actor, `403` for authorization, `404` for a
missing/scoped target, and `409` for stale state or transaction abortion.
Write one audit per changed Link with one run ID, dispatch no new hook, and flush
the `links` cache once after a changed commit.

- [ ] **Step 5: Run focused and adjacent tests**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkHookIntegrationTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
```

Expected: both commands pass without failures.

- [ ] **Step 6: Update manuals, coverage records, contract, and changelogs**

Document the exact CLI options, tag semantics, dry-run/force rules, atomicity,
error codes, audits, and examples in all three manual locales. Add the command
to the coverage evidence, mark the implemented CLI slice accurately, and add
one aligned user-facing bullet to all four `v7.0.0-alpha1` changelogs.

- [ ] **Step 7: Verify and commit**

Run:

```bash
/www/server/php/84/bin/php -l src/Console/Commands/BulkSyncLinkTags.php
/www/server/php/84/bin/php -l src/Services/Automation/BulkSyncTagsAbortException.php
/www/server/php/84/bin/php -l src/Services/Automation/LinkAutomationService.php
git diff --check
/www/server/php/84/bin/php vendor/bin/testbench wncms:check-backend-api-v2-parity --coverage --no-ansi
```

After review, stage only Task 1 files and commit:

```bash
git commit -m "feat(v7): add guarded Link bulk tag sync"
```

---

### Task 2: Shared Link CLI Output Renderer

**Files:**
- Create: `src/Console/Commands/AutomationCommand.php`
- Modify: `src/Console/Commands/ListLinks.php`
- Modify: `src/Console/Commands/InspectLink.php`
- Modify: `src/Console/Commands/CreateLink.php`
- Modify: `src/Console/Commands/UpdateLink.php`
- Modify: `src/Console/Commands/DeleteLink.php`
- Modify: `src/Console/Commands/BulkUpdateLinks.php`
- Modify: `src/Console/Commands/BulkSyncLinkTags.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `tests/Feature/LinkAutomationCommandTest.php`

**Interfaces:**
- Produces: abstract `Wncms\Console\Commands\AutomationCommand`.
- Produces: `protected function outputAutomationResult(array $result, ?callable $humanSuccessRenderer = null): int`.
- Produces shared protected helpers for validation errors, Link item summaries, and mutation-plan summaries.
- Preserves concrete list, inspect, bulk-update, and bulk-tag summary tables.

- [ ] **Step 1: Write renderer characterization and normalization tests**

Add representative tests:

```php
public function test_link_commands_keep_their_human_success_tables(): void
{
    // Assert list headers/pagination, inspect Field|Value, mutation plan rows,
    // Link item rows, and both bulk summary tables.
}

public function test_link_command_human_failures_share_message_and_error_table(): void
{
    // Inspect a missing Link and submit a validation failure.
    // Assert the message plus Field|Errors output for both.
}

public function test_link_commands_keep_the_exact_json_envelope_and_exit_mapping(): void
{
    // Assert pretty JSON, all six envelope keys, success exit 0, failure exit 1.
}
```

- [ ] **Step 2: Run the focused tests and record RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php --filter='human_failures_share_message_and_error_table'
```

Expected: the missing-Link human output test fails because `InspectLink` does not
currently render the shared field-error table.

- [ ] **Step 3: Implement the abstract renderer**

Create:

```php
abstract class AutomationCommand extends Command
{
    protected function outputAutomationResult(
        array $result,
        ?callable $humanSuccessRenderer = null
    ): int {
        // JSON stays byte-for-byte compatible with the current encoder flags.
        // Human failures render message and errors.
        // Human successes invoke the optional concrete renderer.
        // status === 'success' maps to SUCCESS; every other status maps to FAILURE.
    }
}
```

Move only presentation behavior into the base class. Keep command signatures,
service calls, metadata, and domain-specific tables in their concrete commands.

- [ ] **Step 4: Make dynamic command discovery abstract-safe**

`WncmsServiceProvider::loadCommands()` scans this directory, so exclude abstract
classes before calling `$this->commands()`:

```php
$reflection = new \ReflectionClass($class);

if ($reflection->isInstantiable()) {
    $commandClasses[] = $class;
}
```

Convert all seven Link commands to extend `AutomationCommand`, remove duplicated
JSON/error/exit helpers, and use callbacks for their success tables.

- [ ] **Step 5: Run regression tests and verify**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkHookIntegrationTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
/www/server/php/84/bin/php vendor/bin/testbench list --raw | rg '^wncms:links:'
find src/Console/Commands src/Providers -type f \( -name '*Link*.php' -o -name 'AutomationCommand.php' -o -name 'WncmsServiceProvider.php' \) -print0 | xargs -0 -n1 /www/server/php/84/bin/php -l
git diff --check
```

Expected: all focused tests pass and all seven Link commands are discoverable.
This behavior-preserving refactor does not add a changelog entry.

- [ ] **Step 6: Review and commit**

After independent review, stage only Task 2 files and commit:

```bash
git commit -m "refactor(v7): share Link CLI result rendering"
```

---

### Task 3: Guarded Links API v2 Reference Contract

**Files:**
- Create: `src/Http/Controllers/Api/V2/Backend/LinkController.php`
- Create: `tests/Feature/LinkApiV2ControllerTest.php`
- Modify: `config/wncms-backend-api-v2.php`
- Modify: `tests/Unit/CheckBackendApiV2ParityCommandTest.php`
- Modify: `documentations/manual/api/endpoints/links.md`
- Modify: `documentations/manual/api/overview.md`
- Modify: `documentations/manual/api/authentication.md`
- Modify: `documentations/manual/developer/route/api.md`
- Modify: `documentations/manual/zh-CN/api/endpoints/links.md`
- Modify: `documentations/manual/zh-CN/api/overview.md`
- Modify: `documentations/manual/zh-CN/api/authentication.md`
- Modify: `documentations/manual/zh-CN/developer/route/api.md`
- Modify: `documentations/manual/zh-TW/api/endpoints/links.md`
- Modify: `documentations/manual/zh-TW/api/overview.md`
- Modify: `documentations/manual/zh-TW/api/authentication.md`
- Modify: `documentations/manual/zh-TW/developer/route/api.md`
- Modify: `documentations/plans/v7-ai-first-coverage-matrix.md`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Produces dedicated `Wncms\Http\Controllers\Api\V2\Backend\LinkController`.
- Resource methods: `index`, `show`, `store`, `update`, and `destroy`.
- Extra action methods: `bulkUpdate` and `bulkSyncTags`.
- Consumes `LinkAutomationService::list()`, `inspect()`, guarded single mutations,
  `bulkUpdate()`, and `bulkSyncTags()`.
- Retains current route names and URLs while removing the Links bulk-delete route.

- [ ] **Step 1: Write API v2 contract tests**

Create tests with real HTTP requests and an authenticated token user:

```php
public function test_links_api_v2_requires_authentication_and_permission(): void
{
    // Assert 401 without a token and 403 without the route permission.
}

public function test_links_api_v2_lists_and_inspects_only_the_selected_website(): void
{
    // Assert filters, pagination, allowed sort/direction, ID/slug lookup,
    // normalized envelopes, and no cross-website result leakage.
}

public function test_links_api_v2_mutations_preview_by_default(): void
{
    // POST, PATCH, DELETE, bulk-update, and bulk-sync-tags without force.
    // Assert code 202, no model/tag/audit writes.
}

public function test_links_api_v2_forced_mutations_use_the_token_user_as_actor(): void
{
    // Execute each supported mutation with force=true.
    // Assert writes, hooks where defined, audits surface=api_v2, and cache behavior.
}

public function test_links_api_v2_bulk_mutations_are_atomic(): void
{
    // Include a scoped/missing/stale target and assert no partial writes.
}

public function test_links_api_v2_bulk_delete_is_unavailable(): void
{
    // Assert the named route is absent and POST /links/bulk_delete is not served.
}
```

- [ ] **Step 2: Run the API tests and record RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkApiV2ControllerTest.php
```

Expected: the dedicated controller/contract assertions fail because Links still
use the generic raw-resource controller.

- [ ] **Step 3: Implement the dedicated transport adapter**

Use a single options builder:

```php
protected function mutationOptions(Request $request): array
{
    $dryRun = $request->boolean('dry_run');
    $website = $request->input('website_id') ?? wncms()->website()->get()?->getKey();

    return [
        'surface' => 'api_v2',
        'actor_user_id' => (int) $request->user()->getKey(),
        'website_id' => $website === null ? null : (int) $website,
        'force' => !$dryRun && $request->boolean('force'),
        'dry_run' => $dryRun,
    ];
}
```

Authorize each method using the Links resource permission config. Wrap read
results in `AutomationResult`, pass mutation results through unchanged, and return:

```php
return response()->json($result, (int) $result['code']);
```

Validate list filters before calling the service. Allow statuses
`active|inactive|all`, directions `asc|desc`, page/per-page bounds, and sort
columns `id|sort|name|clicks|created_at|updated_at`.

- [ ] **Step 4: Rewire Links routes safely**

Set the Links resource controller to the dedicated API controller and:

```php
'enable_bulk_delete' => false,
```

Point `links.bulk_update` and `links.bulk_sync_tags` action configs to the
dedicated API controller methods. Preserve BridgeController's standard-envelope
normalization but remove the backend HTML controller from these two API paths.

- [ ] **Step 5: Run API, automation, route, and parity tests**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkApiV2ControllerTest.php
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkHookIntegrationTest.php tests/Feature/LinkApiV2ControllerTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
/www/server/php/84/bin/php vendor/bin/testbench route:list --path=api/v2/backend/links
```

Expected: guarded routes pass; the route list contains CRUD, bulk update, and
bulk tag sync but no Links bulk delete.

- [ ] **Step 6: Finalize API docs, evidence, and changelogs**

Document exact routes, token actor, website scope, filters, envelopes, preview
default, `force=true`, `dry_run=true`, permissions, audits, and the bulk-delete
gap in all three locales. Add the API test path to coverage evidence; keep the
Links API v2 surface `Partial` only because guarded bulk delete remains absent,
while docs/tests may become `Complete`. Add one aligned API behavior bullet to
all four changelog mirrors.

- [ ] **Step 7: Verify, review, and commit**

Run:

```bash
/www/server/php/84/bin/php -l src/Http/Controllers/Api/V2/Backend/LinkController.php
/www/server/php/84/bin/php -l tests/Feature/LinkApiV2ControllerTest.php
git diff --check
/www/server/php/84/bin/php vendor/bin/testbench wncms:check-backend-api-v2-parity --coverage --no-ansi
```

After independent review, stage only Task 3 files and commit:

```bash
git commit -m "feat(v7): add guarded Links API v2 contract"
```

---

### Task 4: Opt-in Local MCP Link Read Tools

**Files:**
- Create: `routes/ai.php`
- Create: `src/Mcp/Servers/WncmsServer.php`
- Create: `src/Mcp/Tools/ListLinksTool.php`
- Create: `src/Mcp/Tools/InspectLinkTool.php`
- Create: `tests/Feature/Mcp/LinksToolsTest.php`
- Create: `documentations/manual/developer/mcp/overview.md`
- Create: `documentations/manual/zh-CN/developer/mcp/overview.md`
- Create: `documentations/manual/zh-TW/developer/mcp/overview.md`
- Modify: `composer.json`
- Modify: `config/wncms.php`
- Modify: `src/Providers/WncmsServiceProvider.php`
- Modify: `tests/Unit/CheckBackendApiV2ParityCommandTest.php`
- Modify: `config/wncms-backend-api-v2.php`
- Modify: `documentations/manual/developer/overview.md`
- Modify: `documentations/manual/zh-CN/developer/overview.md`
- Modify: `documentations/manual/zh-TW/developer/overview.md`
- Modify: `documentations/plans/v7-ai-first-coverage-matrix.md`
- Modify: `documentations/change/CHANGELOG.md`
- Modify: `documentations/change/CHANGELOG_en.md`
- Modify: `documentations/change/CHANGELOG_zh_CN.md`
- Modify: `documentations/change/CHANGELOG_ja.md`

**Interfaces:**
- Requires production dependency `laravel/mcp:^0.9`.
- Adds `wncms.mcp.enabled`, backed by `WNCMS_MCP_ENABLED`, default `false`.
- Registers local server name `wncms` only while enabled.
- Produces tools `wncms-links-list` and `wncms-links-inspect`.
- Both tools return the full automation envelope through
  `Laravel\Mcp\Response::structured()`.

- [ ] **Step 1: Add and install the official MCP dependency**

Add:

```json
"laravel/mcp": "^0.9"
```

to `require`, then synchronize the ignored local lock/vendor state:

```bash
composer update laravel/mcp laravel/sanctum secretwebmaster/laravel-localization --with-all-dependencies --no-interaction
composer show laravel/mcp
```

Expected: Composer resolves a `0.9.x` version compatible with PHP 8.4 and
Laravel 13. Do not stage the ignored `composer.lock`.

- [ ] **Step 2: Write server and tool tests before WNCMS MCP classes**

Add tests using Laravel MCP's official server test API:

```php
public function test_wncms_local_server_is_disabled_by_default(): void
{
    // Assert WNCMS does not register the local server while wncms.mcp.enabled=false.
}

public function test_links_list_tool_returns_a_structured_automation_envelope(): void
{
    $response = WncmsServer::tool(ListLinksTool::class, [
        'status' => 'active',
        'website_id' => $this->website->id,
    ]);

    $response->assertOk()->assertName('wncms-links-list');
    // Decode structured content and assert envelope, filters, pagination, metadata.
}

public function test_links_inspect_tool_returns_item_or_not_found_envelope(): void
{
    // Assert ID/slug success and a structured code=404 failure envelope.
}

public function test_links_mcp_tools_are_read_only_and_website_scoped(): void
{
    // Assert annotations plus unchanged Link, tag pivot, and MutationAudit counts.
}
```

- [ ] **Step 3: Run the MCP tests and record RED**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/Mcp/LinksToolsTest.php
```

Expected: tests fail because the WNCMS server and tools do not exist.

- [ ] **Step 4: Register the opt-in local server**

Add to `config/wncms.php`:

```php
'mcp' => [
    'enabled' => env('WNCMS_MCP_ENABLED', false),
    'server' => 'wncms',
],
```

Add a provider helper that requires `routes/ai.php` only when enabled. The route
file contains only:

```php
Mcp::local(config('wncms.mcp.server', 'wncms'), WncmsServer::class);
```

Do not call `Mcp::web()`, add an HTTP route, add OAuth, or register any mutation
tool.

- [ ] **Step 5: Implement the server and read tools**

Register both tools on `WncmsServer`. Annotate each tool with the exact current
Laravel MCP namespaces:

```php
#[Name('wncms-links-list')]
#[Description('List WNCMS Links within a selected website. This tool never writes data.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
```

The list schema accepts `status`, `keyword`, `website_id`, `page`, `per_page`,
`sort`, and `direction` with the API allow-lists and bounds. Inspect requires
`identifier` and accepts `website_id`.

Wrap service output:

```php
$result = AutomationResult::success('Links listed.', $data, [
    'surface' => 'mcp',
    'tool' => 'wncms-links-list',
    'domain' => 'links',
    'action' => 'list',
    'website_id' => $websiteId,
]);

return Response::structured($result);
```

Use the same envelope with `code=404`, `status=fail`, and identifier errors for
an inspect miss. The enabled local process is the trust boundary; do not attach
API v2 middleware or invent a remote actor.

- [ ] **Step 6: Run MCP and adjacent tests**

Run:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/Mcp/LinksToolsTest.php
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkApiV2ControllerTest.php tests/Feature/Mcp/LinksToolsTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
WNCMS_MCP_ENABLED=true /www/server/php/84/bin/php vendor/bin/testbench mcp:inspector wncms --help
```

Expected: tool tests pass, Link mutations remain unchanged, and the local server
is discoverable only while enabled.

- [ ] **Step 7: Document MCP, update coverage, and sync changelogs**

Document installation, `WNCMS_MCP_ENABLED=true`, local client configuration,
`php artisan mcp:start wncms`, both schemas, response envelopes, website
scoping, read-only behavior, and trusted-machine security in all three locales.
Link the pages from each developer overview. Add both tool names and the MCP test
and docs paths to coverage; mark the implemented read-only Links MCP slice
`Complete` while explicitly retaining mutation MCP as out of scope. Add one
aligned MCP behavior bullet to all four changelog mirrors.

- [ ] **Step 8: Verify, review, and commit**

Run:

```bash
composer validate --no-check-publish --no-interaction
vendor/bin/pint --test
find src/Mcp src/Providers tests/Feature/Mcp -type f -name '*.php' -print0 | xargs -0 -n1 /www/server/php/84/bin/php -l
git diff --check
/www/server/php/84/bin/php vendor/bin/testbench wncms:check-backend-api-v2-parity --coverage --no-ansi
```

After independent review, stage only Task 4 files and commit:

```bash
git commit -m "feat(v7): add local read-only Link MCP tools"
```

---

## Final Cross-Phase Gate

After all four task reviews and commits:

```bash
/www/server/php/84/bin/php vendor/bin/phpunit tests/Feature/LinkAutomationCommandTest.php tests/Feature/LinkHookIntegrationTest.php tests/Feature/LinkApiV2ControllerTest.php tests/Feature/Mcp/LinksToolsTest.php tests/Unit/CheckBackendApiV2ParityCommandTest.php
vendor/bin/pint --test
git diff --check
/www/server/php/84/bin/php vendor/bin/testbench wncms:check-backend-api-v2-parity --coverage --no-ansi
git status --short
```

Request one broad independent review from the design commit through the final
implementation commit. Address confirmed findings in one final fix commit, rerun
the full focused gate, and leave `updates/update_core_7.0.0.php` untracked.
