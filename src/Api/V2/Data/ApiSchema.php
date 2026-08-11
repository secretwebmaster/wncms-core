<?php

namespace Wncms\Api\V2\Data;

final class ApiSchema implements \JsonSerializable
{
    /**
     * Create a schema value.
     *
     * @param  array<string, mixed>|bool  $schema
     * @return void
     */
    private function __construct(private readonly array|bool $schema)
    {
    }

    /**
     * Create an object schema.
     *
     * @param  array<string, array<string, mixed>|bool>  $properties
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
     * @param  \Wncms\Api\V2\Data\ApiSchema  $items
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
     * Create a root schema that allows every JSON value.
     *
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function allowAll(): self
    {
        return new self(true);
    }

    /**
     * Create a root schema that denies every JSON value.
     *
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    public static function denyAll(): self
    {
        return new self(false);
    }

    /**
     * Export the JSON Schema value.
     *
     * @return array<string, mixed>|bool
     */
    public function toArray(): array|bool
    {
        return $this->schema;
    }

    /**
     * Export the JSON Schema value with stable object map types.
     *
     * @return array<string, mixed>|bool
     */
    public function jsonSerialize(): array|bool
    {
        return is_array($this->schema)
            ? self::normalizeForJson($this->schema)
            : $this->schema;
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
