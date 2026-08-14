<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Wncms\Api\V2\Data\ApiOperationContract;

final class LegacyTokenPolicy
{
    public function allows(ApiOperationContract $operation, CarbonImmutable $now): bool
    {
        if (! in_array(ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, $operation->acceptedCredentialTypes, true)
            || $operation->requiresStepUp || $operation->securityRisk === 'critical') {
            return false;
        }
        $config = AuthSecurityConfig::fromRuntime();
        $cutoff = $config->legacyPersonalTokensCutoffAt();

        return $config->legacyPersonalTokensEnabled()
            && is_string($cutoff)
            && $cutoff !== ''
            && $now->isBefore(CarbonImmutable::parse($cutoff)->utc());
    }
}
