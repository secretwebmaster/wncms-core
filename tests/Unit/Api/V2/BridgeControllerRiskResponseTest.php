<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Wncms\Http\Controllers\Api\V2\Backend\BridgeController;
use Wncms\Tests\TestCase;

class BridgeControllerRiskResponseTest extends TestCase
{
    /**
     * Verify a legacy failure payload cannot be normalized into a successful confirmation response.
     *
     * @return void
     */
    public function test_legacy_failure_payload_is_normalized_to_non_success_status(): void
    {
        config(['wncms-backend-api-v2.actions' => [[
            'name' => 'test.legacy_failure',
            'controller' => LegacyFailureController::class,
            'action' => 'fail',
            'permission' => '',
        ]]]);
        $request = Request::create('/api/v2/backend/test', 'POST');
        $route = new Route(['POST'], '/api/v2/backend/test', fn () => null);
        $route->defaults('name', 'test.legacy_failure');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = app(BridgeController::class)->dispatch($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('legacy.operation_failed', $response->getData(true)['meta']['error_code']);
    }
}

class LegacyFailureController
{
    /**
     * Return the legacy success-status failure shape.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail(): JsonResponse
    {
        return response()->json(['status' => 'fail', 'message' => 'Rejected']);
    }
}
