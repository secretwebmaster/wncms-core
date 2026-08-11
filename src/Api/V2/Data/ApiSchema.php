<?php

namespace Wncms\Api\V2\Data;

final class ApiSchema implements \JsonSerializable
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

    /**
     * Export the JSON Schema value with stable object map types.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return self::normalizeForJson($this->schema);
    }

    /**
     * Normalize nested schema property maps for JSON serialization.
     *
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private static function normalizeForJson(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::normalizeForJson($item);
            }
        }

        if (isset($value['properties']) && is_array($value['properties'])) {
            $value['properties'] = (object) $value['properties'];
        }

        return $value;
    }
}
