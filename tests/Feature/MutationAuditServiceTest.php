<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Wncms\Models\MutationAudit;
use Wncms\Services\Automation\MutationAuditService;
use Wncms\Tests\TestCase;

class MutationAuditServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_disabled_audit_returns_stable_preview_and_does_not_persist(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $service = app(MutationAuditService::class);
        $plan = $this->mutationPlan();
        $beforeCount = MutationAudit::count();

        $preview = $service->previewFromPlan($plan);

        $this->assertFalse($preview['enabled']);
        $this->assertFalse($preview['will_write']);
        $this->assertNull($service->writeFromPlan($plan));
        $this->assertSame(['enabled' => false, 'id' => null], $service->reference());
        $this->assertSame($beforeCount, MutationAudit::count());
    }

    public function test_enabled_audit_previews_persists_and_returns_reference(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $service = app(MutationAuditService::class);
        $plan = $this->mutationPlan();
        $beforeCount = MutationAudit::count();

        $preview = $service->previewFromPlan($plan);
        $plan['audit'] = $preview;
        $audit = $service->writeFromPlan($plan);

        $this->assertTrue($preview['enabled']);
        $this->assertTrue($preview['will_write']);
        $this->assertNotNull($audit);
        $this->assertSame($beforeCount + 1, MutationAudit::count());
        $this->assertSame([
            'enabled' => true,
            'id' => (int) $audit->getKey(),
        ], $service->reference($audit));
    }

    /**
     * Build a successful non-dry-run mutation plan.
     *
     * @return array
     */
    protected function mutationPlan(): array
    {
        return [
            'operation' => 'create',
            'model_key' => 'link',
            'dry_run' => false,
            'will_write' => true,
            'target' => ['id' => 123],
            'attributes' => ['name' => 'Audited link'],
            'relationships' => ['website_ids' => []],
            'validation' => ['status' => 'pass', 'errors' => []],
            'guard' => ['status' => 'pass', 'errors' => []],
            'safety' => [
                'permission' => 'link_create',
                'write_mode' => 'guarded',
            ],
        ];
    }
}
