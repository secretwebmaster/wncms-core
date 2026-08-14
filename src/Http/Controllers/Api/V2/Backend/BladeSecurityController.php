<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wncms\Services\Security\BladeAvailabilityService;

final class BladeSecurityController extends ApiV2Controller
{
    public function __construct(private BladeAvailabilityService $availability) {}

    public function show(): JsonResponse
    {
        return $this->ok($this->availability->state()->toArray());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);
        try {
            $state = $validated['enabled']
                ? $this->availability->enable('api_v2')
                : $this->availability->disable('api_v2');
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok($state->toArray(), 'blade_availability_updated');
    }
}
