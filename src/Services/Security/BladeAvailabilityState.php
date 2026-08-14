<?php

namespace Wncms\Services\Security;

final readonly class BladeAvailabilityState
{
    /** @param array<int, string> $warnings */
    public function __construct(
        public string $status,
        public bool $enabled,
        public bool $installed,
        public mixed $value = null,
        public array $warnings = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'enabled' => $this->enabled,
            'installed' => $this->installed,
            'warnings' => $this->warnings,
        ];
    }
}
