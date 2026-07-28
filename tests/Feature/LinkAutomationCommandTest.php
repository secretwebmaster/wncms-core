<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Wncms\Models\Link;
use Wncms\Models\MutationAudit;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Automation\LinkAutomationService;
use Wncms\Tests\TestCase;

class LinkAutomationCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_links_list_outputs_json_with_filters(): void
    {
        $keyword = 'Automation CLI ' . uniqid();

        $activeLink = Link::create($this->linkData([
            'status' => 'active',
            'slug' => 'automation-cli-active-' . uniqid(),
            'name' => $keyword . ' Active',
            'url' => 'https://example.com/automation-active',
        ]));

        Link::create($this->linkData([
            'status' => 'inactive',
            'slug' => 'automation-cli-inactive-' . uniqid(),
            'name' => $keyword . ' Inactive',
            'url' => 'https://example.com/automation-inactive',
        ]));

        $exitCode = Artisan::call('wncms:links:list', [
            '--json' => true,
            '--keyword' => $keyword,
            '--status' => 'active',
            '--sort' => 'id',
            '--direction' => 'asc',
            '--per-page' => 10,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('wncms:links:list', $decoded['meta']['command']);
        $this->assertSame(1, $decoded['data']['pagination']['total']);
        $this->assertSame($activeLink->id, $decoded['data']['items'][0]['id']);
        $this->assertSame('active', $decoded['data']['items'][0]['status']);
    }

    public function test_links_inspect_outputs_json_by_slug(): void
    {
        $slug = 'automation-cli-inspect-' . uniqid();
        $link = Link::create($this->linkData([
            'slug' => $slug,
            'name' => 'Automation Inspect Link',
            'tracking_code' => 'automation-inspect-code',
        ]));

        $exitCode = Artisan::call('wncms:links:inspect', [
            'identifier' => $slug,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('wncms:links:inspect', $decoded['meta']['command']);
        $this->assertSame($link->id, $decoded['data']['item']['id']);
        $this->assertSame($slug, $decoded['data']['item']['slug']);
        $this->assertSame('automation-inspect-code', $decoded['data']['item']['tracking_code']);
    }

    public function test_links_inspect_returns_failure_when_missing(): void
    {
        $exitCode = Artisan::call('wncms:links:inspect', [
            'identifier' => 'missing-link-' . uniqid(),
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(404, $decoded['code']);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame('Link not found.', $decoded['message']);
    }

    /**
     * Verify Link commands preserve their human-readable success tables.
     *
     * @return void
     */
    public function test_link_commands_keep_their_human_success_tables(): void
    {
        $website = Website::first();
        $link = Link::create($this->linkData());

        Artisan::call('wncms:links:list');
        $listOutput = Artisan::output();
        Artisan::call('wncms:links:inspect', ['identifier' => $link->id]);
        $inspectOutput = Artisan::output();
        Artisan::call('wncms:links:create', [
            '--name' => 'Automation Human Plan',
            '--url' => 'https://example.com/human-plan',
            '--website' => $website->id,
        ]);
        $createOutput = Artisan::output();
        Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Automation Human Update',
            '--website' => $website->id,
        ]);
        $updateOutput = Artisan::output();
        Artisan::call('wncms:links:delete', [
            'identifier' => $link->id,
            '--website' => $website->id,
        ]);
        $deleteOutput = Artisan::output();
        Artisan::call('wncms:links:bulk-update', [
            '--items' => json_encode([['identifier' => $link->id, 'sort' => 20]]),
            '--website' => $website->id,
        ]);
        $bulkUpdateOutput = Artisan::output();
        Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$link->id]),
            '--categories' => json_encode(['Partners']),
            '--website' => $website->id,
        ]);
        $bulkTagOutput = Artisan::output();

        $this->assertStringContainsString('WNCMS Links', $listOutput);
        $this->assertStringContainsString('Page ', $listOutput);
        $this->assertStringContainsString('Field', $inspectOutput);
        $this->assertStringContainsString('Value', $inspectOutput);
        $this->assertStringContainsString($link->name, $inspectOutput);
        $this->assertStringContainsString('operation', $createOutput);
        $this->assertStringContainsString('operation', $updateOutput);
        $this->assertStringContainsString('operation', $deleteOutput);
        $this->assertStringContainsString('Requested', $bulkUpdateOutput);
        $this->assertStringContainsString('Requested', $bulkTagOutput);
    }

    /**
     * Verify Link command failures share the message and error table output.
     *
     * @return void
     */
    public function test_link_command_human_failures_share_message_and_error_table(): void
    {
        Artisan::call('wncms:links:inspect', [
            'identifier' => 'missing-link-' . uniqid(),
        ]);
        $inspectOutput = Artisan::output();
        Artisan::call('wncms:links:bulk-update', ['--items' => '{invalid']);
        $validationOutput = Artisan::output();

        $this->assertStringContainsString('Link not found.', $inspectOutput);
        $this->assertStringContainsString('Field', $inspectOutput);
        $this->assertStringContainsString('Errors', $inspectOutput);
        $this->assertStringContainsString('Link bulk update validation failed.', $validationOutput);
        $this->assertStringContainsString('Field', $validationOutput);
        $this->assertStringContainsString('Errors', $validationOutput);
    }

    /**
     * Verify Link commands preserve the JSON envelope and exit mapping.
     *
     * @return void
     */
    public function test_link_commands_keep_the_exact_json_envelope_and_exit_mapping(): void
    {
        $successExitCode = Artisan::call('wncms:links:list', ['--json' => true]);
        $successOutput = Artisan::output();
        $success = json_decode(trim($successOutput), true);
        $failureExitCode = Artisan::call('wncms:links:inspect', [
            'identifier' => 'missing-link-' . uniqid(),
            '--json' => true,
        ]);
        $failureOutput = Artisan::output();
        $failure = json_decode(trim($failureOutput), true);

        $this->assertStringContainsString("{\n", $successOutput);
        $this->assertSame(['code', 'status', 'message', 'data', 'meta', 'errors'], array_keys($success));
        $this->assertSame(['code', 'status', 'message', 'data', 'meta', 'errors'], array_keys($failure));
        $this->assertSame(0, $successExitCode);
        $this->assertSame(1, $failureExitCode);
    }

    public function test_links_create_outputs_dry_run_without_writing_by_default(): void
    {
        $website = Website::first();
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        $exitCode = Artisan::call('wncms:links:create', [
            '--json' => true,
            '--name' => 'Automation CLI Dry Run',
            '--url' => 'https://example.com/cli-dry-run',
            '--website' => $website->id,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(202, $decoded['code']);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('wncms:links:create', $decoded['meta']['command']);
        $this->assertTrue($decoded['data']['plan']['dry_run']);
        $this->assertFalse($decoded['data']['plan']['will_write']);
        $this->assertSame('dry_run', $decoded['data']['plan']['safety']['write_mode']);
        $this->assertSame('mutation_audits', $decoded['data']['plan']['audit']['table']);
        $this->assertSame($beforeCount, Link::count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_create_force_requires_actor(): void
    {
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        $exitCode = Artisan::call('wncms:links:create', [
            '--json' => true,
            '--name' => 'Automation CLI Missing Actor',
            '--url' => 'https://example.com/cli-missing-actor',
            '--force' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(401, $decoded['code']);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame('Link create guard check failed.', $decoded['message']);
        $this->assertSame(['required'], $decoded['errors']['actor']);
        $this->assertSame('guarded', $decoded['data']['plan']['safety']['write_mode']);
        $this->assertSame($beforeCount, Link::count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_create_force_writes_with_actor_and_audit(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $admin = $this->automationAdmin($website);
        $slug = 'automation-cli-create-' . uniqid();
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        try {
            $exitCode = Artisan::call('wncms:links:create', [
                '--json' => true,
                '--name' => 'Automation CLI Created',
                '--url' => 'https://example.com/cli-created',
                '--slug' => $slug,
                '--website' => $website->id,
                '--actor-user' => $admin->id,
                '--link-categories' => 'Partners',
                '--force' => true,
            ]);

            $decoded = json_decode(trim(Artisan::output()), true);
            $link = Link::where('slug', $slug)->first();

            $this->assertSame(0, $exitCode);
            $this->assertSame(201, $decoded['code']);
            $this->assertSame('success', $decoded['status']);
            $this->assertSame('Link created.', $decoded['message']);
            $this->assertNotNull($link);
            $this->assertSame($beforeCount + 1, Link::count());
            $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
            $this->assertSame($link->id, $decoded['data']['item']['id']);
            $this->assertSame([$website->id], $decoded['data']['item']['website_ids']);
            $this->assertSame('guarded', $decoded['data']['plan']['safety']['write_mode']);
            $this->assertFalse($decoded['data']['plan']['dry_run']);
            $this->assertTrue($decoded['data']['plan']['will_write']);
            $this->assertSame($decoded['data']['audit']['id'], MutationAudit::latest('id')->value('id'));

            $audit = MutationAudit::find($decoded['data']['audit']['id']);
            $this->assertSame('cli', $audit->surface);
            $this->assertSame($admin->id, $audit->actor_id);
            $this->assertSame('links', $audit->domain);
            $this->assertSame('create', $audit->action);
            $this->assertSame('link', $audit->model_key);
            $this->assertSame($link->id, $audit->model_id);
            $this->assertSame([$website->id], $audit->website_ids);
            $this->assertSame('link_create', $audit->permission);
            $this->assertSame(201, $audit->result_code);
            $this->assertSame('success', $audit->result_status);
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

    public function test_links_create_rejects_missing_website_for_admin(): void
    {
        $admin = $this->automationAdmin();
        $missingWebsiteId = 99999999;
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        $exitCode = Artisan::call('wncms:links:create', [
            '--json' => true,
            '--name' => 'Automation Missing Website',
            '--url' => 'https://example.com/missing-website',
            '--website' => $missingWebsiteId,
            '--actor-user' => $admin->id,
            '--force' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(422, $decoded['code']);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame([$missingWebsiteId], $decoded['errors']['website_ids']);
        $this->assertSame([$missingWebsiteId], $decoded['data']['plan']['guard']['website_scope']['missing_ids']);
        $this->assertSame($beforeCount, Link::count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_create_rejects_missing_website_for_non_admin(): void
    {
        Permission::findOrCreate('link_create', 'web');
        $member = User::create([
            'username' => 'automation-member-' . uniqid(),
            'email' => 'automation-member-' . uniqid() . '@example.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
        $member->givePermissionTo('link_create');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $missingWebsiteId = 99999998;

        $exitCode = Artisan::call('wncms:links:create', [
            '--json' => true,
            '--name' => 'Automation Member Missing Website',
            '--url' => 'https://example.com/member-missing-website',
            '--website' => $missingWebsiteId,
            '--actor-user' => $member->id,
            '--force' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(422, $decoded['code']);
        $this->assertSame([$missingWebsiteId], $decoded['errors']['website_ids']);
        $this->assertSame([$missingWebsiteId], $decoded['data']['plan']['guard']['website_scope']['missing_ids']);
    }

    public function test_links_create_hooks_receive_declared_actor_context(): void
    {
        $admin = $this->automationAdmin();
        $slug = 'automation-hook-actor-' . uniqid();
        $hookActorIds = [];

        Event::listen('wncms.backend.links.store.before', function ($request) use (&$hookActorIds) {
            $hookActorIds['request'] = $request->user()?->getKey();
            $hookActorIds['auth'] = auth()->id();
        });

        $exitCode = Artisan::call('wncms:links:create', [
            '--json' => true,
            '--name' => 'Automation Hook Actor',
            '--url' => 'https://example.com/hook-actor',
            '--slug' => $slug,
            '--actor-user' => $admin->id,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($admin->id, $hookActorIds['request']);
        $this->assertSame($admin->id, $hookActorIds['auth']);
        $this->assertNull(auth()->user());
        $this->assertTrue(Link::where('slug', $slug)->exists());
    }

    public function test_link_service_plans_create_without_writing(): void
    {
        $website = Website::first();
        $admin = $this->automationAdmin($website);
        $service = app(LinkAutomationService::class);
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        $plan = $service->planCreate([
            'name' => 'Automation Planned Create',
            'url' => 'https://example.com/planned-create',
            'link_categories' => ['Partners'],
        ], [
            'uid' => 'planned-create-uid',
            'website_id' => $website->id,
            'surface' => 'cli',
            'run_id' => 'test-run-id',
            'actor_user_id' => $admin->id,
        ]);

        $this->assertSame($beforeCount, Link::count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
        $this->assertSame('create', $plan['operation']);
        $this->assertTrue($plan['dry_run']);
        $this->assertFalse($plan['will_write']);
        $this->assertSame('planned-create-uid', $plan['attributes']['slug']);
        $this->assertSame('planned-create-uid', $plan['attributes']['tracking_code']);
        $this->assertSame('Automation Planned Create', $plan['attributes']['name']);
        $this->assertSame([$website->id], $plan['relationships']['website_ids']);
        $this->assertSame(['Partners'], $plan['relationships']['tags']['link_categories']);
        $this->assertSame('link_create', $plan['safety']['permission']);
        $this->assertSame('pass', $plan['validation']['status']);
        $this->assertSame('pass', $plan['guard']['status']);
        $this->assertSame($admin->id, $plan['guard']['actor']['id']);
        $this->assertSame('pass', $plan['guard']['permission']['status']);
        $this->assertSame('pass', $plan['guard']['website_scope']['status']);
        $this->assertSame('mutation_audits', $plan['audit']['table']);
        $this->assertFalse($plan['audit']['will_write']);
        $this->assertSame('test-run-id', $plan['audit']['attributes']['run_id']);
        $this->assertSame('cli', $plan['audit']['attributes']['surface']);
        $this->assertSame('links', $plan['audit']['attributes']['domain']);
        $this->assertSame('create', $plan['audit']['attributes']['action']);
        $this->assertSame('link', $plan['audit']['attributes']['model_key']);
        $this->assertSame('link_create', $plan['audit']['attributes']['permission']);
        $this->assertSame([$website->id], $plan['audit']['attributes']['website_ids']);
        $this->assertSame(202, $plan['audit']['attributes']['result_code']);
        $this->assertSame('success', $plan['audit']['attributes']['result_status']);
    }

    public function test_link_service_guard_fails_write_mode_without_actor(): void
    {
        $service = app(LinkAutomationService::class);

        $plan = $service->planCreate([
            'name' => 'Automation Missing Actor',
            'url' => 'https://example.com/missing-actor',
        ], [
            'write_mode' => true,
        ]);

        $this->assertSame('fail', $plan['guard']['status']);
        $this->assertSame(401, $plan['guard']['code']);
        $this->assertSame(['required'], $plan['guard']['errors']['actor']);
        $this->assertSame(401, $plan['audit']['attributes']['result_code']);
        $this->assertSame('fail', $plan['audit']['attributes']['result_status']);
    }

    public function test_link_service_guard_fails_for_actor_without_permission(): void
    {
        Permission::findOrCreate('link_create', 'web');

        $member = User::create([
            'username' => 'automation-member-' . uniqid(),
            'email' => 'automation-member-' . uniqid() . '@example.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');

        $service = app(LinkAutomationService::class);
        $plan = $service->planCreate([
            'name' => 'Automation Denied Actor',
            'url' => 'https://example.com/denied-actor',
        ], [
            'write_mode' => true,
            'actor_user_id' => $member->id,
        ]);

        $this->assertSame('fail', $plan['guard']['status']);
        $this->assertSame(403, $plan['guard']['code']);
        $this->assertSame($member->id, $plan['guard']['actor']['id']);
        $this->assertSame('fail', $plan['guard']['permission']['status']);
        $this->assertSame(['link_create'], $plan['guard']['errors']['permission']);
        $this->assertSame(403, $plan['audit']['attributes']['result_code']);
        $this->assertSame('fail', $plan['audit']['attributes']['result_status']);
    }

    public function test_link_service_plans_update_changes_without_writing(): void
    {
        $link = Link::create($this->linkData([
            'name' => 'Automation Update Before',
            'slug' => 'automation-plan-update-' . uniqid(),
            'is_pinned' => false,
        ]));

        $service = app(LinkAutomationService::class);
        $plan = $service->planUpdate($link->id, [
            'name' => 'Automation Update After',
            'is_pinned' => true,
            'status' => 'inactive',
        ]);

        $link->refresh();

        $this->assertSame('update', $plan['operation']);
        $this->assertTrue($plan['dry_run']);
        $this->assertFalse($plan['will_write']);
        $this->assertSame($link->id, $plan['target']['id']);
        $this->assertSame('Automation Update Before', $plan['changes']['name']['from']);
        $this->assertSame('Automation Update After', $plan['changes']['name']['to']);
        $this->assertFalse($plan['changes']['is_pinned']['from']);
        $this->assertTrue($plan['changes']['is_pinned']['to']);
        $this->assertSame('link_edit', $plan['safety']['permission']);
        $this->assertSame('pass', $plan['validation']['status']);
        $this->assertSame('Automation Update Before', $link->name);
        $this->assertFalse((bool) $link->is_pinned);
    }

    public function test_link_service_plans_delete_without_deleting(): void
    {
        $link = Link::create($this->linkData([
            'slug' => 'automation-plan-delete-' . uniqid(),
        ]));

        $service = app(LinkAutomationService::class);
        $plan = $service->planDelete($link->slug);

        $this->assertSame('delete', $plan['operation']);
        $this->assertTrue($plan['dry_run']);
        $this->assertFalse($plan['will_write']);
        $this->assertSame($link->id, $plan['target']['id']);
        $this->assertSame('link_delete', $plan['safety']['permission']);
        $this->assertTrue(Link::whereKey($link->id)->exists());
    }

    public function test_links_update_outputs_dry_run_without_writing_by_default(): void
    {
        $link = Link::create($this->linkData([
            'name' => 'Automation Update Dry Run Before',
            'slug' => 'automation-update-dry-run-' . uniqid(),
        ]));
        $beforeAuditCount = MutationAudit::count();

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Automation Update Dry Run After',
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);
        $link->refresh();

        $this->assertSame(0, $exitCode);
        $this->assertSame(202, $decoded['code']);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('wncms:links:update', $decoded['meta']['command']);
        $this->assertSame('Automation Update Dry Run Before', $link->name);
        $this->assertSame('Automation Update Dry Run After', $decoded['data']['plan']['changes']['name']['to']);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_update_force_writes_patch_with_actor_audit_and_hooks(): void
    {
        $link = Link::create($this->linkData([
            'name' => 'Automation Update Before',
            'slug' => 'automation-update-force-' . uniqid(),
            'status' => 'active',
            'url' => 'https://example.com/automation-update-before',
            'is_pinned' => true,
        ]));
        $admin = $this->automationAdmin();
        $beforeAuditCount = MutationAudit::count();
        $hookActorIds = [];

        Event::listen('wncms.backend.links.update.before', function ($hookLink, $request) use (&$hookActorIds) {
            $hookActorIds['link'] = $hookLink->getKey();
            $hookActorIds['request'] = $request->user()?->getKey();
            $hookActorIds['auth'] = auth()->id();
        });
        Event::listen('wncms.backend.links.update.attributes.before', function ($hookLink, $request, &$attributes) {
            $attributes['name'] = 'Automation Update Hook Mutation';
        });

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->slug,
            '--name' => 'Automation Update After',
            '--status' => 'inactive',
            '--is-pinned' => 'false',
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);
        $link->refresh();
        $audit = MutationAudit::find($decoded['data']['audit']['id']);

        $this->assertSame(0, $exitCode);
        $this->assertSame(200, $decoded['code']);
        $this->assertSame('Automation Update Hook Mutation', $link->name);
        $this->assertSame('inactive', $link->status);
        $this->assertSame('https://example.com/automation-update-before', $link->url);
        $this->assertFalse((bool) $link->is_pinned);
        $this->assertSame($link->id, $hookActorIds['link']);
        $this->assertSame($admin->id, $hookActorIds['request']);
        $this->assertSame($admin->id, $hookActorIds['auth']);
        $this->assertNull(auth()->user());
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame('update', $audit->action);
        $this->assertSame('link_edit', $audit->permission);
        $this->assertSame($link->id, $audit->model_id);
        $this->assertSame('Automation Update Hook Mutation', $decoded['data']['changes']['name']['to']);
        $this->assertSame('Automation Update Hook Mutation', $audit->input_summary['changes']['name']['to']);
    }

    public function test_links_update_force_requires_actor_and_permission(): void
    {
        $link = Link::create($this->linkData([
            'slug' => 'automation-update-guard-' . uniqid(),
        ]));

        $missingActorExitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Denied update',
            '--force' => true,
            '--json' => true,
        ]);
        $missingActor = json_decode(trim(Artisan::output()), true);

        $member = User::create([
            'username' => 'automation-update-member-' . uniqid(),
            'email' => 'automation-update-member-' . uniqid() . '@example.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $deniedExitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Denied update',
            '--actor-user' => $member->id,
            '--force' => true,
            '--json' => true,
        ]);
        $denied = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $missingActorExitCode);
        $this->assertSame(401, $missingActor['code']);
        $this->assertSame(1, $deniedExitCode);
        $this->assertSame(403, $denied['code']);
        $this->assertSame(['link_edit'], $denied['errors']['permission']);
        $this->assertSame('Automation Link', $link->fresh()->name);
    }

    public function test_links_update_rejects_unknown_website_and_cross_website_target_scope(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $otherWebsite = Website::query()->whereKey('!=', $website->id)->first() ?: Website::create([
            'domain' => 'automation-scope-' . uniqid() . '.test',
            'site_name' => 'Automation Scope Website',
        ]);
        $admin = $this->automationAdmin($website);
        $link = Link::create($this->linkData([
            'slug' => 'automation-update-scope-' . uniqid(),
        ]));
        $link->bindWebsites([$otherWebsite->id]);

        try {
            $unknownExitCode = Artisan::call('wncms:links:update', [
                'identifier' => $link->id,
                '--name' => 'Unknown website update',
                '--website' => 99999997,
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $unknown = json_decode(trim(Artisan::output()), true);

            $member = User::create([
                'username' => 'automation-scope-member-' . uniqid(),
                'email' => 'automation-scope-member-' . uniqid() . '@example.com',
                'password' => Hash::make('wncms.cc'),
                'email_verified_at' => now(),
            ]);
            $member->assignRole('member');
            $member->givePermissionTo('link_edit');
            $member->websites()->sync([$website->id]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $scopeExitCode = Artisan::call('wncms:links:update', [
                'identifier' => $link->id,
                '--name' => 'Cross website update',
                '--actor-user' => $member->id,
                '--force' => true,
                '--json' => true,
            ]);
            $scope = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $unknownExitCode);
            $this->assertSame(422, $unknown['code']);
            $this->assertSame(1, $scopeExitCode);
            $this->assertSame(403, $scope['code']);
            $this->assertSame([$otherWebsite->id], $scope['errors']['website_ids']);
            $this->assertSame('Automation Link', $link->fresh()->name);
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

    public function test_links_update_returns_not_found_and_skips_noop_force_write(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData([
            'name' => 'Automation Noop Update',
            'slug' => 'automation-update-noop-' . uniqid(),
        ]));
        $beforeAuditCount = MutationAudit::count();

        $missingExitCode = Artisan::call('wncms:links:update', [
            'identifier' => 'missing-update-' . uniqid(),
            '--name' => 'Missing update',
            '--json' => true,
        ]);
        $missing = json_decode(trim(Artisan::output()), true);

        $noopExitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Automation Noop Update',
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $noop = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $missingExitCode);
        $this->assertSame(404, $missing['code']);
        $this->assertSame(0, $noopExitCode);
        $this->assertSame(200, $noop['code']);
        $this->assertSame('Link update skipped; no changes detected.', $noop['message']);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_update_preserves_boolean_fields_when_they_are_not_explicitly_supplied(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData([
            'slug' => 'automation-update-preserve-booleans-' . uniqid(),
            'is_pinned' => true,
            'is_recommended' => true,
        ]));

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => 'Automation Boolean Preservation',
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);

        $link->refresh();

        $this->assertSame(0, $exitCode);
        $this->assertSame('Automation Boolean Preservation', $link->name);
        $this->assertTrue((bool) $link->is_pinned);
        $this->assertTrue((bool) $link->is_recommended);
    }

    public function test_links_update_clears_explicit_nullable_fields(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData([
            'slug' => 'automation-update-clear-nullable-' . uniqid(),
            'expired_at' => now()->addDay(),
            'hit_at' => now(),
        ]));

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--remark' => '',
            '--slogan' => '',
            '--description' => '',
            '--external-thumbnail' => '',
            '--color' => '',
            '--background' => '',
            '--expired-at' => '',
            '--hit-at' => '',
            '--contact' => '',
            '--sort' => '',
            '--clicks' => '',
            '--tracking-code' => '',
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);

        $link->refresh();

        $this->assertSame(0, $exitCode);
        foreach (['remark', 'slogan', 'description', 'external_thumbnail', 'color', 'background', 'expired_at', 'hit_at', 'contact', 'sort', 'clicks', 'tracking_code'] as $field) {
            $this->assertNull($link->getAttribute($field));
        }
    }

    public function test_links_update_rejects_explicit_empty_required_fields_and_invalid_booleans(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData([
            'slug' => 'automation-update-invalid-patch-' . uniqid(),
            'name' => 'Automation Required Name',
            'is_pinned' => true,
        ]));

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--name' => '',
            '--is-pinned' => 'sometimes',
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $decoded = json_decode(trim(Artisan::output()), true);
        $link->refresh();

        $this->assertSame(1, $exitCode);
        $this->assertSame(422, $decoded['code']);
        $this->assertSame(['required'], $decoded['errors']['name']);
        $this->assertSame(['invalid'], $decoded['errors']['is_pinned']);
        $this->assertSame('Automation Required Name', $link->name);
        $this->assertTrue((bool) $link->is_pinned);
    }

    public function test_links_delete_outputs_dry_run_without_writing_by_default(): void
    {
        $link = Link::create($this->linkData(['slug' => 'automation-delete-dry-run-' . uniqid()]));
        $beforeAuditCount = MutationAudit::count();
        $exitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--json' => true]);
        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(202, $decoded['code']);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('wncms:links:delete', $decoded['meta']['command']);
        $this->assertTrue($decoded['data']['plan']['dry_run']);
        $this->assertTrue(Link::whereKey($link->id)->exists());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_delete_force_deletes_with_actor_and_audit(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData(['slug' => 'automation-delete-force-' . uniqid()]));
        $beforeAuditCount = MutationAudit::count();
        $exitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--actor-user' => $admin->id, '--force' => true, '--json' => true]);
        $decoded = json_decode(trim(Artisan::output()), true);
        $audit = MutationAudit::find($decoded['data']['audit']['id']);

        $this->assertSame(0, $exitCode);
        $this->assertSame(200, $decoded['code']);
        $this->assertFalse(Link::whereKey($link->id)->exists());
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame($link->id, $decoded['data']['deleted']['id']);
        $this->assertSame('delete', $audit->action);
        $this->assertSame('link_delete', $audit->permission);
        $this->assertSame($link->id, $audit->model_id);
        $this->assertSame($link->id, $audit->input_summary['target']['id']);
    }

    public function test_links_delete_force_requires_actor_and_permission(): void
    {
        $link = Link::create($this->linkData());
        $missingActorExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--force' => true, '--json' => true]);
        $missingActor = json_decode(trim(Artisan::output()), true);
        $member = User::create([
            'username' => 'automation-delete-member-' . uniqid(),
            'email' => 'automation-delete-member-' . uniqid() . '@example.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $deniedExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--actor-user' => $member->id, '--force' => true, '--json' => true]);
        $denied = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $missingActorExitCode);
        $this->assertSame(401, $missingActor['code']);
        $this->assertSame(1, $deniedExitCode);
        $this->assertSame(403, $denied['code']);
        $this->assertSame(['link_delete'], $denied['errors']['permission']);
        $this->assertTrue(Link::whereKey($link->id)->exists());
    }

    public function test_links_delete_returns_conflict_when_deleting_listener_cancels_mutation(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData());
        $beforeAuditCount = MutationAudit::count();
        $dispatcher = clone Link::getEventDispatcher();
        Link::setEventDispatcher(clone $dispatcher);
        $listener = function () {
            return false;
        };
        Link::deleting($listener);

        try {
            $exitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--actor-user' => $admin->id, '--force' => true, '--json' => true]);
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(409, $decoded['code']);
            $this->assertSame(['cancelled'], $decoded['errors']['delete']);
            $this->assertTrue(Link::whereKey($link->id)->exists());
            $this->assertSame($beforeAuditCount, MutationAudit::count());
        } finally {
            Link::setEventDispatcher($dispatcher);
        }
    }

    public function test_links_delete_rejects_unknown_website_and_cross_website_target_scope(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $otherWebsite = Website::query()->whereKey('!=', $website->id)->first() ?: Website::create(['domain' => 'automation-delete-scope-' . uniqid() . '.test', 'site_name' => 'Automation Delete Scope Website']);
        $admin = $this->automationAdmin($website);
        $link = Link::create($this->linkData());
        $link->bindWebsites([$otherWebsite->id]);

        try {
            $unknownExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--website' => 99999996, '--actor-user' => $admin->id, '--force' => true, '--json' => true]);
            $unknown = json_decode(trim(Artisan::output()), true);
            $zeroWebsiteExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--website' => 0, '--json' => true]);
            $zeroWebsite = json_decode(trim(Artisan::output()), true);
            $member = User::create([
                'username' => 'automation-delete-scope-member-' . uniqid(),
                'email' => 'automation-delete-scope-member-' . uniqid() . '@example.com',
                'password' => Hash::make('wncms.cc'),
                'email_verified_at' => now(),
            ]);
            $member->assignRole('member');
            $member->givePermissionTo('link_delete');
            $member->websites()->sync([$website->id]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $scopeExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--actor-user' => $member->id, '--force' => true, '--json' => true]);
            $scope = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $unknownExitCode);
            $this->assertSame(422, $unknown['code']);
            $this->assertSame(1, $zeroWebsiteExitCode);
            $this->assertSame(422, $zeroWebsite['code']);
            $this->assertSame(['invalid'], $zeroWebsite['errors']['website_ids']);
            $this->assertSame(1, $scopeExitCode);
            $this->assertSame(403, $scope['code']);
            $this->assertSame([$otherWebsite->id], $scope['errors']['website_ids']);
            $this->assertTrue(Link::whereKey($link->id)->exists());
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

    public function test_links_delete_returns_not_found_for_missing_or_out_of_scope_target(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $otherWebsite = Website::query()->whereKey('!=', $website->id)->first() ?: Website::create(['domain' => 'automation-delete-lookup-' . uniqid() . '.test', 'site_name' => 'Automation Delete Lookup Website']);
        $link = Link::create($this->linkData());
        $link->bindWebsites([$otherWebsite->id]);

        try {
            $missingExitCode = Artisan::call('wncms:links:delete', ['identifier' => 'missing-delete-' . uniqid(), '--json' => true]);
            $missing = json_decode(trim(Artisan::output()), true);
            $outOfScopeExitCode = Artisan::call('wncms:links:delete', ['identifier' => $link->id, '--website' => $website->id, '--json' => true]);
            $outOfScope = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $missingExitCode);
            $this->assertSame(404, $missing['code']);
            $this->assertSame(1, $outOfScopeExitCode);
            $this->assertSame(404, $outOfScope['code']);
            $this->assertTrue(Link::whereKey($link->id)->exists());
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

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

    public function test_links_bulk_update_is_atomic_when_a_target_is_outside_website_scope(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $otherWebsite = Website::query()->whereKey('!=', $website->id)->first() ?: Website::create([
            'domain' => 'automation-bulk-scope-' . uniqid() . '.test',
            'site_name' => 'Automation Bulk Scope Website',
        ]);
        $admin = $this->automationAdmin($website);
        $first = Link::create($this->linkData(['sort' => 10]));
        $first->bindWebsites([$website->id]);
        $second = Link::create($this->linkData(['sort' => 20]));
        $second->bindWebsites([$otherWebsite->id]);

        try {
            $exitCode = Artisan::call('wncms:links:bulk-update', [
                '--items' => json_encode([
                    ['identifier' => $first->id, 'sort' => 11],
                    ['identifier' => $second->id, 'sort' => 21],
                ]),
                '--website' => $website->id,
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(404, $decoded['code']);
            $this->assertSame(10, $first->fresh()->sort);
            $this->assertSame(20, $second->fresh()->sort);
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

    public function test_links_bulk_update_rolls_back_earlier_changes_when_a_later_update_is_cancelled(): void
    {
        $admin = $this->automationAdmin();
        $first = Link::create($this->linkData(['sort' => 10]));
        $second = Link::create($this->linkData(['sort' => 20]));
        $beforeAuditCount = MutationAudit::count();
        $dispatcher = clone Link::getEventDispatcher();
        Link::setEventDispatcher(clone $dispatcher);
        Link::updating(function (Link $link) use ($second) {
            return $link->is($second) ? false : null;
        });

        try {
            $exitCode = Artisan::call('wncms:links:bulk-update', [
                '--items' => json_encode([
                    ['identifier' => $first->id, 'sort' => 11],
                    ['identifier' => $second->id, 'sort' => 21],
                ]),
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(409, $decoded['code']);
            $this->assertSame(10, $first->fresh()->sort);
            $this->assertSame(20, $second->fresh()->sort);
            $this->assertSame($beforeAuditCount, MutationAudit::count());
        } finally {
            Link::setEventDispatcher($dispatcher);
        }
    }

    public function test_links_create_rejects_invalid_website_in_dry_run_and_write_modes(): void
    {
        $admin = $this->automationAdmin();
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();

        foreach ([false, true] as $force) {
            $exitCode = Artisan::call('wncms:links:create', [
                '--name' => 'Invalid create website ' . uniqid(),
                '--url' => 'https://example.com/invalid-create-website',
                '--website' => 0,
                '--actor-user' => $admin->id,
                '--force' => $force,
                '--json' => true,
            ]);
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(422, $decoded['code']);
        }

        $this->assertSame($beforeCount, Link::count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_create_and_update_return_conflict_when_model_events_cancel(): void
    {
        $admin = $this->automationAdmin();
        $beforeCount = Link::count();
        $beforeAuditCount = MutationAudit::count();
        $dispatcher = clone Link::getEventDispatcher();
        Link::setEventDispatcher(clone $dispatcher);
        Link::creating(fn() => false);

        try {
            $createExitCode = Artisan::call('wncms:links:create', [
                '--name' => 'Cancelled create',
                '--url' => 'https://example.com/cancelled-create',
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $create = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $createExitCode);
            $this->assertSame(409, $create['code']);
            $this->assertSame($beforeCount, Link::count());
            $this->assertSame($beforeAuditCount, MutationAudit::count());
        } finally {
            Link::setEventDispatcher($dispatcher);
        }

        $link = Link::create($this->linkData(['sort' => 10]));
        $dispatcher = clone Link::getEventDispatcher();
        Link::setEventDispatcher(clone $dispatcher);
        Link::updating(fn() => false);

        try {
            $updateExitCode = Artisan::call('wncms:links:update', [
                'identifier' => $link->id,
                '--sort' => 11,
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $update = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $updateExitCode);
            $this->assertSame(409, $update['code']);
            $this->assertSame(10, $link->fresh()->sort);
            $this->assertSame($beforeAuditCount, MutationAudit::count());
        } finally {
            Link::setEventDispatcher($dispatcher);
        }
    }

    public function test_links_update_rejects_stale_target_changes_without_writing(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData(['sort' => 10]));
        $beforeAuditCount = MutationAudit::count();

        Event::listen('wncms.backend.links.update.before', function (Link $hookLink) {
            $hookLink->updateQuietly(['sort' => 12]);
        });

        $exitCode = Artisan::call('wncms:links:update', [
            'identifier' => $link->id,
            '--sort' => 11,
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(409, $decoded['code']);
        $this->assertSame(10, $link->fresh()->sort);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_bulk_update_rejects_non_integer_sort_values(): void
    {
        $link = Link::create($this->linkData(['sort' => 10]));

        foreach ([null, true, 1.5, '1.5'] as $sort) {
            $exitCode = Artisan::call('wncms:links:bulk-update', [
                '--items' => json_encode([['identifier' => $link->id, 'sort' => $sort]]),
                '--json' => true,
            ]);
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(422, $decoded['code']);
            $this->assertSame(10, $link->fresh()->sort);
        }
    }

    public function test_links_bulk_sync_tags_outputs_atomic_dry_run_without_writing(): void
    {
        $first = Link::create($this->linkData());
        $second = Link::create($this->linkData());
        $first->syncTagsWithType(['Existing category'], 'link_category');
        $second->syncTagsWithType(['Existing tag'], 'link_tag');
        $beforeAuditCount = MutationAudit::count();
        $cache = wncms()->cache()->tags(['links']);
        $cache->put('link-bulk-sync-tags-dry-run', 'unchanged', 60);

        $exitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$first->id, $second->slug]),
            '--categories' => json_encode(['Partners']),
            '--tags' => json_encode(['Featured']),
            '--json' => true,
        ]);
        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(202, $decoded['code']);
        $this->assertTrue($decoded['data']['plan']['atomic']);
        $this->assertSame(['requested' => 2, 'changed' => 2, 'noop' => 0], $decoded['data']['plan']['summary']);
        $this->assertSame(['Existing category'], $this->tagNames($first->fresh(), 'link_category'));
        $this->assertSame(['Existing tag'], $this->tagNames($second->fresh(), 'link_tag'));
        $this->assertSame($beforeAuditCount, MutationAudit::count());
        $this->assertSame('unchanged', $cache->get('link-bulk-sync-tags-dry-run'));
    }

    public function test_links_bulk_sync_tags_force_applies_sync_attach_and_detach(): void
    {
        $admin = $this->automationAdmin();
        $link = Link::create($this->linkData());
        $link->syncTagsWithType(['Old category'], 'link_category');
        $link->syncTagsWithType(['Old tag'], 'link_tag');

        $syncExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$link->id]),
            '--action' => 'sync',
            '--categories' => json_encode(['Partners']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $sync = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $syncExitCode);
        $this->assertSame(200, $sync['code']);
        $this->assertSame(['Partners'], $this->tagNames($link->fresh(), 'link_category'));
        $this->assertSame(['Old tag'], $this->tagNames($link->fresh(), 'link_tag'));

        $attachExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$link->slug]),
            '--action' => 'attach',
            '--tags' => json_encode(['Featured']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $attach = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $attachExitCode);
        $this->assertSame(200, $attach['code']);
        $this->assertSame(['Partners'], $this->tagNames($link->fresh(), 'link_category'));
        $this->assertSame(['Featured', 'Old tag'], $this->tagNames($link->fresh(), 'link_tag'));

        $detachExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$link->id]),
            '--action' => 'detach',
            '--categories' => json_encode(['Partners']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $detach = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $detachExitCode);
        $this->assertSame(200, $detach['code']);
        $this->assertSame([], $this->tagNames($link->fresh(), 'link_category'));
        $this->assertSame(['Featured', 'Old tag'], $this->tagNames($link->fresh(), 'link_tag'));
    }

    public function test_links_bulk_sync_tags_audits_only_changes_with_one_run_id(): void
    {
        $admin = $this->automationAdmin();
        $changed = Link::create($this->linkData());
        $noop = Link::create($this->linkData());
        $noop->syncTagsWithType(['Partners'], 'link_category');
        $beforeAuditCount = MutationAudit::count();

        $exitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$changed->id, $noop->id]),
            '--action' => 'sync',
            '--categories' => json_encode(['Partners']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $decoded = json_decode(trim(Artisan::output()), true);
        $audits = MutationAudit::query()->latest('id')->take(1)->get();

        $this->assertSame(0, $exitCode);
        $this->assertSame(200, $decoded['code']);
        $this->assertSame(['requested' => 2, 'changed' => 1, 'noop' => 1], $decoded['data']['summary']);
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame($changed->id, $audits->first()->model_id);
        $this->assertSame($decoded['data']['run_id'], $audits->first()->run_id);
    }

    public function test_links_bulk_sync_tags_rejects_invalid_input_without_partial_writes(): void
    {
        $link = Link::create($this->linkData());
        $link->syncTagsWithType(['Existing category'], 'link_category');
        $beforeAuditCount = MutationAudit::count();
        $tooManyIdentifiers = array_map(fn(int $index) => $index + 100000, range(0, 100));
        $payloads = [
            ['--identifiers' => '{broken', '--categories' => json_encode(['Partners'])],
            ['--identifiers' => '{"0":' . $link->id . '}', '--categories' => json_encode(['Partners'])],
            ['--identifiers' => json_encode([$link->id]), '--categories' => '{}', '--tags' => json_encode(['Featured'])],
            ['--identifiers' => json_encode([$link->id]), '--action' => 'invalid', '--categories' => json_encode(['Partners'])],
            ['--identifiers' => json_encode([$link->id]), '--categories' => json_encode([]), '--tags' => json_encode([])],
            ['--identifiers' => json_encode([$link->id]), '--categories' => json_encode([['invalid']])],
            ['--identifiers' => json_encode([$link->id, $link->slug]), '--categories' => json_encode(['Partners'])],
            ['--identifiers' => json_encode($tooManyIdentifiers), '--categories' => json_encode(['Partners'])],
        ];

        foreach ($payloads as $payload) {
            $exitCode = Artisan::call('wncms:links:bulk-sync-tags', array_merge($payload, ['--json' => true]));
            $decoded = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $exitCode);
            $this->assertSame(422, $decoded['code']);
            $this->assertSame(['Existing category'], $this->tagNames($link->fresh(), 'link_category'));
        }

        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_links_bulk_sync_tags_rejects_missing_actor_permission_and_scoped_target(): void
    {
        $originalModels = config('wncms.models');
        config(['wncms.models.link.website_mode' => 'multi']);
        $website = Website::first();
        $otherWebsite = Website::query()->whereKey('!=', $website->id)->first() ?: Website::create([
            'domain' => 'automation-bulk-tag-scope-' . uniqid() . '.test',
            'site_name' => 'Automation Bulk Tag Scope Website',
        ]);
        $admin = $this->automationAdmin($website);
        $link = Link::create($this->linkData());
        $link->bindWebsites([$otherWebsite->id]);
        $beforeAuditCount = MutationAudit::count();
        $member = User::create([
            'username' => 'bulk-tag-member-' . uniqid(),
            'email' => 'bulk-tag-member-' . uniqid() . '@example.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $missingActorExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
                '--identifiers' => json_encode([$link->id]),
                '--categories' => json_encode(['Partners']),
                '--force' => true,
                '--json' => true,
            ]);
            $missingActor = json_decode(trim(Artisan::output()), true);
            $deniedExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
                '--identifiers' => json_encode([$link->id]),
                '--categories' => json_encode(['Partners']),
                '--actor-user' => $member->id,
                '--force' => true,
                '--json' => true,
            ]);
            $denied = json_decode(trim(Artisan::output()), true);
            $scopedExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
                '--identifiers' => json_encode([$link->id]),
                '--categories' => json_encode(['Partners']),
                '--website' => $website->id,
                '--actor-user' => $admin->id,
                '--force' => true,
                '--json' => true,
            ]);
            $scoped = json_decode(trim(Artisan::output()), true);

            $this->assertSame(1, $missingActorExitCode);
            $this->assertSame(401, $missingActor['code']);
            $this->assertSame(1, $deniedExitCode);
            $this->assertSame(403, $denied['code']);
            $this->assertSame(1, $scopedExitCode);
            $this->assertSame(404, $scoped['code']);
            $this->assertSame([], $this->tagNames($link->fresh(), 'link_category'));
            $this->assertSame($beforeAuditCount, MutationAudit::count());
        } finally {
            config(['wncms.models' => $originalModels]);
        }
    }

    public function test_links_bulk_sync_tags_rolls_back_and_rejects_stale_tag_state(): void
    {
        $admin = $this->automationAdmin();
        $first = Link::create($this->linkData());
        $second = Link::create($this->linkData());
        $beforeAuditCount = MutationAudit::count();
        $eventCount = 0;
        $phase = 'stale';

        Event::listen('eloquent.retrieved: ' . Link::class, function () use (&$eventCount, &$phase, $second) {
            $eventCount++;
            if ($phase === 'stale' && $eventCount === 3) {
                $second->syncTagsWithType(['Stale category'], 'link_category');
            }

            if ($phase === 'later_failure' && $eventCount === 6) {
                $second->syncTagsWithType(['Later category'], 'link_category');
            }
        });

        $staleExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$first->id, $second->id]),
            '--categories' => json_encode(['Partners']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $stale = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $staleExitCode);
        $this->assertSame(409, $stale['code']);
        $this->assertSame([], $this->tagNames($first->fresh(), 'link_category'));
        $this->assertSame([], $this->tagNames($second->fresh(), 'link_category'));
        $this->assertSame($beforeAuditCount, MutationAudit::count());

        $phase = 'later_failure';
        $eventCount = 0;
        $rollbackExitCode = Artisan::call('wncms:links:bulk-sync-tags', [
            '--identifiers' => json_encode([$first->id, $second->id]),
            '--categories' => json_encode(['Partners']),
            '--actor-user' => $admin->id,
            '--force' => true,
            '--json' => true,
        ]);
        $rollback = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $rollbackExitCode);
        $this->assertSame(409, $rollback['code']);
        $this->assertSame([], $this->tagNames($first->fresh(), 'link_category'));
        $this->assertSame([], $this->tagNames($second->fresh(), 'link_category'));
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    /**
     * Build test link data with stable defaults.
     *
     * @param array $overrides
     * @return array
     */
    protected function linkData(array $overrides = []): array
    {
        return array_merge([
            'status' => 'active',
            'tracking_code' => 'automation-code-' . uniqid(),
            'slug' => 'automation-link-' . uniqid(),
            'name' => 'Automation Link',
            'url' => 'https://example.com/automation-link',
            'description' => 'Automation description',
            'external_thumbnail' => 'https://example.com/thumbnail.jpg',
            'clicks' => 0,
            'remark' => 'Automation remark',
            'sort' => 10,
            'color' => '#ffffff',
            'background' => '#000000',
            'is_pinned' => false,
            'expired_at' => null,
            'slogan' => 'Automation slogan',
            'contact' => '@automation',
            'is_recommended' => false,
            'hit_at' => null,
        ], $overrides);
    }

    /**
     * Return normalized Link tag names for one tag type.
     *
     * @param Link $link
     * @param string $type
     * @return array
     */
    protected function tagNames(Link $link, string $type): array
    {
        return $link->tagsWithType($type)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Return an administrator authorized for protected Link mutations.
     *
     * Ensures command tests have the required Link permissions and optional website scope.
     *
     * @param  \Wncms\Models\Website|null  $website
     * @return \Wncms\Models\User
     */
    protected function automationAdmin(?Website $website = null): User
    {
        Permission::findOrCreate('link_create', 'web');
        Permission::findOrCreate('link_edit', 'web');
        Permission::findOrCreate('link_delete', 'web');

        $admin = User::where('email', 'admin@demo.com')->first() ?: User::first();
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        if (!$admin->hasRole('superadmin')) {
            $admin->assignRole('superadmin');
        }
        if (!$admin->hasPermissionTo('link_create')) {
            $admin->givePermissionTo('link_create');
        }
        if (!$admin->hasPermissionTo('link_edit')) {
            $admin->givePermissionTo('link_edit');
        }
        if (!$admin->hasPermissionTo('link_delete')) {
            $admin->givePermissionTo('link_delete');
        }
        if ($website && !$admin->websites()->where('websites.id', $website->id)->exists()) {
            $admin->websites()->syncWithoutDetaching([$website->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }
}
