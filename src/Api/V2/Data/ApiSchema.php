<?php

namespace Wncms\Api\V2\Data;

final class ApiSchema implements \JsonSerializable
{
    private const SCHEMA_MAP_KEYWORDS = [
        '$defs',
        'properties',
        'patternProperties',
        'dependentSchemas',
    ];

    private const SCHEMA_VALUE_KEYWORDS = [
        'items',
        'not',
        'contains',
        'if',
        'then',
        'else',
        'propertyNames',
        'additionalProperties',
        'unevaluatedProperties',
        'unevaluatedItems',
        'contentSchema',
    ];

    private const SCHEMA_LIST_KEYWORDS = [
        'allOf',
        'anyOf',
        'oneOf',
        'prefixItems',
    ];

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
     * @return array<string, mixed>|bool|object
     */
    public function jsonSerialize(): array|bool|object
    {
        return self::normalizeSchemaForJson($this->schema);
    }

    /**
     * Normalize one schema without converting list- or data-valued arrays.
     *
     * @param  array<string, mixed>|bool  $schema
     *
     * @return array<string, mixed>|bool|object
     */
    private static function normalizeSchemaForJson(array|bool $schema): array|bool|object
    {
        if (is_bool($schema)) {
            return $schema;
        }

        if ($schema === []) {
            return (object) [];
        }

        $normalized = $schema;
        foreach ($schema as $keyword => $value) {
            if (in_array($keyword, self::SCHEMA_MAP_KEYWORDS, true) && is_array($value)) {
                $normalized[$keyword] = self::normalizeSchemaMapForJson($value);

                continue;
            }

            if (
                in_array($keyword, self::SCHEMA_VALUE_KEYWORDS, true)
                && (is_array($value) || is_bool($value))
            ) {
                $normalized[$keyword] = self::normalizeSchemaForJson($value);

                continue;
            }

            if (in_array($keyword, self::SCHEMA_LIST_KEYWORDS, true) && is_array($value)) {
                $normalized[$keyword] = array_values(array_map(
                    static fn (mixed $item): mixed => is_array($item) || is_bool($item)
                        ? self::normalizeSchemaForJson($item)
                        : $item,
                    $value
                ));
            }
        }

        return $normalized;
    }

    /**
     * Normalize a keyword whose members are named schemas.
     *
     * @param  array<int|string, mixed>  $schemas
     *
     * @return object
     */
    private static function normalizeSchemaMapForJson(array $schemas): object
    {
        $normalized = [];
        foreach ($schemas as $name => $schema) {
            $normalized[$name] = is_array($schema) || is_bool($schema)
                ? self::normalizeSchemaForJson($schema)
                : $schema;
        }

        return (object) $normalized;
    }
}
