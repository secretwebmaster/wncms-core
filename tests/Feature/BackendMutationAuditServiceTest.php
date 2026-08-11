<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Wncms\Models\Link;
use Wncms\Models\MutationAudit;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Automation\BackendMutationAuditService;
use Wncms\Tests\TestCase;

class BackendMutationAuditServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_disabled_adapter_returns_null_without_persisting(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $link = Link::create($this->linkData());
        $service = app(BackendMutationAuditService::class);
        $snapshot = $service->snapshot($link);
        $beforeCount = MutationAudit::count();

        $audit = $service->write(
            $link,
            'links',
            'update',
            'link_edit',
            $snapshot,
            $snapshot
        );

        $this->assertFalse($service->enabled());
        $this->assertNull($audit);
        $this->assertSame($beforeCount, MutationAudit::count());
    }

    public function test_enabled_adapter_persists_ui_metadata_and_redacts_relationship_changes(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $user = User::firstOrFail();
        $website = Website::firstOrFail();
        $this->actingAs($user);
        $link = Link::create($this->linkData(['name' => 'Before audit']));
        $service = app(BackendMutationAuditService::class);
        $before = $service->snapshot($link, ['website_ids' => [$website->id]]);
        $link->name = 'After audit';
        $after = $service->snapshot($link, ['website_ids' => [$website->id]]);

        $audit = $service->write(
            $link,
            'links',
            'update',
            'link_edit',
            $before,
            $after,
            [$website->id],
            ['api_token' => 'do-not-store-this']
        );

        $this->assertNotNull($audit);
        $this->assertSame([
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
        ], $audit->only([
            'surface',
            'actor_type',
            'actor_id',
            'domain',
            'action',
            'model_key',
            'model_id',
            'website_ids',
            'permission',
            'result_code',
            'result_status',
        ]));
        $this->assertSame('After audit', $audit->input_summary['changes']['after']['attributes']['name']);
        $this->assertSame('[redacted]', $audit->input_summary['changes']['relationships']['api_token']);
    }

    /**
     * Build valid Link attributes with unique identifiers.
     *
     * @param  array  $overrides
     * @return array
     */
    protected function linkData(array $overrides = []): array
    {
        return array_merge([
            'status' => 'active',
            'tracking_code' => 'backend-audit-code-' . uniqid(),
            'slug' => 'backend-audit-link-' . uniqid(),
            'name' => 'Backend audit link',
            'url' => 'https://example.com/backend-audit-link',
            'clicks' => 0,
            'sort' => 10,
            'is_pinned' => false,
            'is_recommended' => false,
        ], $overrides);
    }
}
