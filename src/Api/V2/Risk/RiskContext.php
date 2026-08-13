<?php

namespace Wncms\Api\V2\Risk;

final readonly class RiskContext
{
    /**
     * Create canonical risk inputs resolved before execution.
     *
     * @param  array<string, mixed>  $normalizedInput
     * @param  array<string, mixed>  $targetState
     * @param  array<string, mixed>  $environment
     * @param  array<int, string>  $modelKeys
     * @param  array<int, string>  $connectionNames
     * @return void
     */
    public function __construct(
        public array $normalizedInput,
        public array $targetState,
        public array $environment,
        public array $modelKeys = [],
        public array $connectionNames = [],
    ) {}
}
