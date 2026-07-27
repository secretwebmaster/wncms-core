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

    protected function automationAdmin(?Website $website = null): User
    {
        Permission::findOrCreate('link_create', 'web');

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
        if ($website && !$admin->websites()->where('websites.id', $website->id)->exists()) {
            $admin->websites()->syncWithoutDetaching([$website->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }
}
