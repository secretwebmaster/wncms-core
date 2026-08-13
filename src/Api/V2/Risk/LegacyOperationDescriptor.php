<?php

namespace Wncms\Api\V2\Risk;

final readonly class LegacyOperationDescriptor
{
    /**
     * Create one explicit legacy operation security descriptor.
     *
     * @param  string  $operationId
     * @param  string  $securityRisk
     * @param  array<int, string>  $acceptedCredentialTypes
     * @param  bool  $requiresStepUp
     * @param  array<int, string>  $stepUpPurposes
     * @param  bool  $actionPlanEligible
     * @param  array<int, string>  $domainModelKeys
     * @param  array<int, string>  $transactionalOutboxModelKeys
     * @param  string  $sideEffectKind
     * @param  string  $canonicalizer
     * @param  string  $targetResolver
     * @param  bool  $idempotent
     * @return void
     */
    public function __construct(
        public string $operationId,
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
    ) {}
}
