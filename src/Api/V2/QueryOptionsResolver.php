<?php

namespace Wncms\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\QueryOptions;

class QueryOptionsResolver
{
    /**
     * Normalize declared list query parameters from an API request.
     *
     * Resolves only parameters explicitly declared by the operation contract.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $contract
     *
     * @return \Wncms\Api\V2\Data\QueryOptions
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resolve(Request $request, ApiOperationContract $contract): QueryOptions
    {
        $query = $request->query();
        $page = $this->positiveInteger($query['page'] ?? 1, 'page');
        $perPage = min(100, $this->positiveInteger($query['per_page'] ?? 20, 'per_page'));
        $keyword = $this->nullableString($query['keyword'] ?? null, 'keyword');
        $filters = $this->filters($query['filter'] ?? [], $contract);
        $sort = $this->sort($query['sort'] ?? null, $contract);
        $direction = $this->direction($query['direction'] ?? 'asc');
        $includes = $this->list($query['include'] ?? null, 'include', $contract->includes);
        $fields = $this->list($query['fields'] ?? null, 'fields', $contract->fields);

        return new QueryOptions(
            page: $page,
            perPage: $perPage,
            keyword: $keyword,
            filters: $filters,
            sort: $sort,
            direction: $direction,
            includes: $includes,
            fields: $fields,
        );
    }

    /**
     * Normalize a required positive integer query parameter.
     *
     * Rejects non-integer and non-positive input before applying pagination limits.
     *
     * @param  mixed  $value
     * @param  string  $field
     *
     * @return int
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function positiveInteger(mixed $value, string $field): int
    {
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            $this->invalid($field, 'The '.$field.' field must be a positive integer.');
        }

        if (is_string($value)) {
            $normalized = ltrim($value, '0');
            if ($normalized === '') {
                $this->invalid($field, 'The '.$field.' field must be at least 1.');
            }

            $maximum = (string) PHP_INT_MAX;
            if (
                strlen($normalized) > strlen($maximum)
                || (strlen($normalized) === strlen($maximum) && strcmp($normalized, $maximum) > 0)
            ) {
                $this->invalid($field, 'The '.$field.' field exceeds the supported integer range.');
            }
        }

        $integer = (int) $value;
        if ($integer < 1) {
            $this->invalid($field, 'The '.$field.' field must be at least 1.');
        }

        return $integer;
    }

    /**
     * Normalize an optional string query parameter.
     *
     * Trims empty strings to null so omitted and blank query parameters are equivalent.
     *
     * @param  mixed  $value
     * @param  string  $field
     *
     * @return string|null
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $this->invalid($field, 'The '.$field.' field must be a string.');
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Normalize declared filter values.
     *
     * @param  mixed  $value
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $contract
     *
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function filters(mixed $value, ApiOperationContract $contract): array
    {
        if (! is_array($value)) {
            $this->invalid('filter', 'The filter field must be an object.');
        }

        foreach ($value as $field => $filter) {
            if (! is_string($field) || ! in_array($field, $contract->filters, true)) {
                $this->invalid('filter.'.$field, 'The selected filter is invalid.');
            }
        }

        return $value;
    }

    /**
     * Normalize a declared sort field.
     *
     * Requires a provided sort to be listed in the operation contract.
     *
     * @param  mixed  $value
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $contract
     *
     * @return string|null
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function sort(mixed $value, ApiOperationContract $contract): ?string
    {
        $sort = $this->nullableString($value, 'sort');

        if ($sort !== null && ! in_array($sort, $contract->sorts, true)) {
            $this->invalid('sort', 'The selected sort is invalid.');
        }

        return $sort;
    }

    /**
     * Normalize an ascending or descending sort direction.
     *
     * Converts the direction to lowercase before validating its supported values.
     *
     * @param  mixed  $value
     *
     * @return string
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function direction(mixed $value): string
    {
        $direction = $this->nullableString($value, 'direction');
        $direction = strtolower($direction ?? 'asc');

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $this->invalid('direction', 'The direction field must be asc or desc.');
        }

        return $direction;
    }

    /**
     * Normalize a comma-separated list and enforce its contract allowlist.
     *
     * @param  mixed  $value
     * @param  string  $field
     * @param  array<int, string>  $allowed
     *
     * @return array<int, string>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function list(mixed $value, string $field, array $allowed): array
    {
        $list = $this->nullableString($value, $field);
        if ($list === null) {
            return [];
        }

        $values = array_values(array_filter(array_map('trim', explode(',', $list)), static fn (string $item): bool => $item !== ''));

        foreach ($values as $item) {
            if (! in_array($item, $allowed, true)) {
                $this->invalid($field, 'The selected '.$field.' value is invalid.');
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * Throw a field-specific validation exception.
     *
     * Keeps query validation errors compatible with Laravel's standard error envelope.
     *
     * @param  string  $field
     * @param  string  $message
     *
     * @return never
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
