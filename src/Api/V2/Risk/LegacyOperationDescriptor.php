<?php

namespace Wncms\Api\V2\Risk;

final readonly class LegacyOperationDescriptor
{
    /**
     * Create one explicit legacy operation security descriptor.
     *
     * @param  array<int, string>  $acceptedCredentialTypes
     * @param  array<int, string>  $stepUpPurposes
     * @param  array<int, string>  $domainModelKeys
     * @param  array<int, string>  $transactionalOutboxModelKeys
     * @param  array<int, string>  $relationshipBoundaries
     * @return void
     */
    public function __construct(
        public string $operationId,
        public string $ability,
        public string $dataRisk,
        public string $securityRisk,
        public array $acceptedCredentialTypes,
        public bool $requiresStepUp,
        public array $stepUpPurposes,
        public bool $actionPlanEligible,
        public array $domainModelKeys,
        public array $transactionalOutboxModelKeys,
        public string $sideEffectKind,
        public string $canonicalizer,
        public string $targetResolver,
        public bool $idempotent,
        public array $relationshipBoundaries = [],
    ) {}
}
