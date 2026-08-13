<?php

namespace Wncms\Api\V2\Risk;

use Wncms\Api\V2\Data\ApiOperationContract;

final class RiskPolicy
{
    private const LEVELS = ['normal' => 0, 'sensitive' => 1, 'high' => 2, 'critical' => 3];

    public function effective(ApiOperationContract $operation, array $normalizedInput, array $environment): string
    {
        $risk = $this->valid($operation->securityRisk);
        $inputRisk = 'normal';
        $permanent = ($normalizedInput['expiry'] ?? null) === 'permanent'
            || ($normalizedInput['expires_in_days'] ?? null) === 'permanent';
        $crossSite = count(array_unique((array) ($normalizedInput['website_ids'] ?? []))) > 1;
        $broad = in_array(($normalizedInput['template'] ?? null), ['site_manager', 'full_admin'], true)
            || ($normalizedInput['ability_template'] ?? null) === 'full_admin';

        if ($permanent) {
            $inputRisk = 'critical';
        } elseif ($crossSite || $broad) {
            $inputRisk = 'high';
        }

        return $this->maximum($risk, $inputRisk, $this->valid((string) ($environment['security_risk'] ?? 'normal')));
    }

    public function requiresPlan(ApiOperationContract $operation, string $effectiveRisk, string $mode): bool
    {
        return $mode === 'planned'
            && $operation->actionPlanEligible
            && in_array($effectiveRisk, ['high', 'critical'], true);
    }

    private function valid(string $risk): string
    {
        return array_key_exists($risk, self::LEVELS) ? $risk : 'critical';
    }

    private function maximum(string ...$risks): string
    {
        usort($risks, static fn (string $left, string $right): int => self::LEVELS[$right] <=> self::LEVELS[$left]);

        return $risks[0];
    }
}
