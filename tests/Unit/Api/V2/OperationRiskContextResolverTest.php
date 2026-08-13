<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Http\Request;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContext;
use Wncms\Tests\TestCase;

class OperationRiskContextResolverTest extends TestCase
{
    public function test_operation_resolver_normalizes_types_and_defaults_and_uses_server_state(): void
    {
        $serverVersion = 7;
        $environmentRisk = 'normal';
        $resolver = app(OperationRiskContextResolver::class);
        $resolver->register('backend.widgets.update', function (array $input, array $parameters) use (&$serverVersion, &$environmentRisk): array {
            return [
                'target_state' => ['id' => (int) $parameters['id'], 'version' => $serverVersion],
                'environment' => ['security_risk' => $environmentRisk],
                'model_keys' => ['setting'],
            ];
        });
        $operation = $this->operation();

        $first = $resolver->resolve($operation, ['count' => '7'], ['id' => '19']);
        $this->assertSame(['count' => 7, 'enabled' => false], $first->normalizedInput);
        $this->assertSame(['id' => 19, 'version' => 7], $first->targetState);
        $this->assertSame(['security_risk' => 'normal'], $first->environment);

        $serverVersion = 8;
        $environmentRisk = 'critical';
        $second = $resolver->resolveRequest(
            Request::create('/widgets/19', 'PATCH', ['count' => 7, 'enabled' => false, 'target_state' => ['version' => 7]]),
            $operation,
            ['id' => 19],
        );
        $this->assertSame(['id' => 19, 'version' => 8], $second->targetState);
        $this->assertSame(['security_risk' => 'critical'], $second->environment);
        $this->assertInstanceOf(RiskContext::class, $second);
    }

    private function operation(): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.widgets.update', domain: 'widgets', surface: 'backend', method: 'PATCH',
            path: '/api/v2/backend/widgets/{id}', routeName: 'api.v2.backend.widgets.update',
            permission: 'widget_edit', ability: 'widgets.write', websiteScoped: true,
            risk: 'write', implementation: 'domain',
            request: ApiSchema::object([
                'count' => ['type' => 'integer'],
                'enabled' => ['type' => 'boolean', 'default' => false],
            ], ['count']),
            response: ApiSchema::object(), securityRisk: 'high', actionPlanEligible: true,
        );
    }
}
