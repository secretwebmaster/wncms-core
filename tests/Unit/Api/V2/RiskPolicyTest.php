<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\TestCase;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Risk\RiskPolicy;

class RiskPolicyTest extends TestCase
{
    public function test_permanent_cross_site_full_admin_escalates_to_critical(): void
    {
        $this->assertSame('critical', (new RiskPolicy)->effective($this->operation('high'), [
            'expiry' => 'permanent',
            'website_ids' => [1, 2],
            'template' => 'full_admin',
        ], []));
    }

    public function test_effective_risk_never_downgrades_contract_or_environment_risk(): void
    {
        $policy = new RiskPolicy;

        $this->assertSame('sensitive', $policy->effective($this->operation('sensitive'), [], ['security_risk' => 'normal']));
        $this->assertSame('high', $policy->effective($this->operation('normal'), [], ['security_risk' => 'high']));
        $this->assertSame('critical', $policy->effective($this->operation('normal'), ['expiry' => 'permanent'], []));
    }

    public function test_planned_mode_applies_only_to_eligible_high_and_critical_operations(): void
    {
        $policy = new RiskPolicy;

        $this->assertFalse($policy->requiresPlan($this->operation('high', true), 'high', 'direct'));
        $this->assertTrue($policy->requiresPlan($this->operation('high', true), 'high', 'planned'));
        $this->assertFalse($policy->requiresPlan($this->operation('high', false), 'high', 'planned'));
        $this->assertFalse($policy->requiresPlan($this->operation('sensitive', true), 'sensitive', 'planned'));
    }

    private function operation(string $securityRisk, bool $eligible = true): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.tokens.store',
            domain: 'tokens',
            surface: 'backend',
            method: 'POST',
            path: '/api/v2/backend/tokens',
            routeName: 'api.v2.backend.tokens.store',
            permission: 'api_token_create',
            ability: 'tokens.create',
            websiteScoped: true,
            risk: 'write',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
            securityRisk: $securityRisk,
            actionPlanEligible: $eligible,
        );
    }
}
