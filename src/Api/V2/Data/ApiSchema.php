<?php

namespace Wncms\Api\V2\Data;

final class ApiSchema
{
    /**
     * Create a schema value.
     *
     * @param  array<string, mixed>  $schema
     * @return void
     */
    private function __construct(private readonly array $schema)
    {
    }

    /**
     * Create an object schema.
     *
     * @param  array<string, array<string, mixed>>  $properties
     * @param  array<int, string>  $required
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function object(array $properties = [], array $required = []): self
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return new self($schema);
    }

    /**
     * Create an array schema.
     *
     * @param  self  $items
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function arrayOf(self $items): self
    {
        return new self([
            'type' => 'array',
            'items' => $items->toArray(),
        ]);
    }

    /**
     * Create a string schema.
     *
     * @param  array<int, string>|null  $enum
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function string(?array $enum = null): self
    {
        $schema = ['type' => 'string'];

        if ($enum !== null) {
            $schema['enum'] = $enum;
        }

        return new self($schema);
    }

    /**
     * Create an integer schema.
     *
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function integer(): self
    {
        return new self(['type' => 'integer']);
    }

    /**
     * Create a boolean schema.
     *
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function boolean(): self
    {
        return new self(['type' => 'boolean']);
    }

    /**
     * Export the JSON Schema value.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->schema;
    }
}
