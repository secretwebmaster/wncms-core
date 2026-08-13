<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Http\Request;
use Wncms\Auth\Api\V2\AuthSecurityConfig;

final class RiskEnvironmentProvider
{
    /**
     * Resolve conservative server-owned runtime risk signals.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return array<string, mixed>
     */
    public function resolve(?Request $request = null): array
    {
        return [
            'security_risk' => (string) config('wncms-api-v2.risk.environment_security_risk', 'normal'),
            'high_risk_action_mode' => AuthSecurityConfig::fromRuntime()->highRiskMode(),
            'ip' => $request?->ip() ?? 'unavailable',
            'device' => trim((string) ($request?->userAgent() ?? 'unavailable')),
            'runtime_signal' => (string) config('wncms-api-v2.risk.runtime_signal', 'normal'),
        ];
    }
}
