<?php

namespace Wncms\Api\V2\Data;

final class ApiDomainContract
{
    /**
     * Create a domain contract.
     *
     * @param  string  $key
     * @param  string  $label
     * @return void
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {
    }

    /**
     * Export the domain contract.
     *
     * @return array{key: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
        ];
    }
}
