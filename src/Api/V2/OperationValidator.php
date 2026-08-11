<?php

namespace Wncms\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use stdClass;
use Wncms\Api\V2\Data\AsyncOperation;

final class OperationValidator
{
    /**
     * Validate an asynchronous operation at the domain and JSON wire boundaries.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function validate(AsyncOperation $operation): void
    {
        if (! Str::isUuid($operation->id)) {
            throw new \InvalidArgumentException('Operation ID must be a valid UUID');
        }

        $this->assertNonEmptyUtf8($operation->type, 'Operation type');

        if ($operation->progress < 0 || $operation->progress > 100) {
            throw new \InvalidArgumentException('Operation progress must be between 0 and 100');
        }

        if ($operation->actorId <= 0) {
            throw new \InvalidArgumentException('Operation actor ID must be greater than zero');
        }

        if (! array_is_list($operation->websiteIds)) {
            throw new \InvalidArgumentException('Operation website IDs must be a list');
        }

        foreach ($operation->websiteIds as $websiteId) {
            if (is_int($websiteId)) {
                if ($websiteId <= 0) {
                    throw new \InvalidArgumentException('Operation website integer IDs must be greater than zero');
                }

                continue;
            }

            if (! is_string($websiteId)) {
                throw new \InvalidArgumentException('Operation website IDs must contain only integers or strings');
            }

            $this->assertNonEmptyUtf8($websiteId, 'Operation website string ID');
        }

        $createdAt = $this->parseUtcTimestamp($operation->createdAt);
        $updatedAt = $this->parseUtcTimestamp($operation->updatedAt);
        $expiresAt = $this->parseUtcTimestamp($operation->expiresAt);

        if ($updatedAt->lessThan($createdAt)) {
            throw new \InvalidArgumentException('Operation update time cannot precede creation time');
        }

        if ($expiresAt->lessThanOrEqualTo($updatedAt)) {
            throw new \InvalidArgumentException('Operation expiry must be after its update time');
        }

        $this->assertJsonSafe($operation->result, 'Operation result');
        $this->assertJsonSafe($operation->error, 'Operation error');
    }

    /**
     * Parse an operation timestamp in canonical second-precision UTC format.
     *
     * @param  string  $timestamp
     *
     * @return \Carbon\CarbonImmutable
     *
     * @throws \InvalidArgumentException
     */
    public function parseUtcTimestamp(string $timestamp): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $timestamp)) {
            throw new \InvalidArgumentException('Operation timestamp must use canonical UTC format');
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $timestamp, 'UTC');
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(
                'Operation timestamp must use canonical UTC format',
                previous: $exception
            );
        }

        if (! $parsed instanceof CarbonImmutable || $parsed->format('Y-m-d\TH:i:s\Z') !== $timestamp) {
            throw new \InvalidArgumentException('Operation timestamp must use canonical UTC format');
        }

        return $parsed;
    }

    /**
     * Assert a string is non-empty and valid UTF-8.
     *
     * @param  string  $value
     * @param  string  $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertNonEmptyUtf8(string $value, string $field): void
    {
        if (trim($value) === '' || preg_match('//u', $value) !== 1) {
            throw new \InvalidArgumentException($field.' must be non-empty valid UTF-8');
        }
    }

    /**
     * Assert a payload can be represented by stable JSON scalar, list, map, or standard-object values.
     *
     * @param  mixed  $value
     * @param  string  $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertJsonSafe(mixed $value, string $field): void
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException($field.' must be JSON-safe', previous: $exception);
        }

        $this->assertJsonShape($value, $field);
    }

    /**
     * Restrict a validated JSON value to portable scalar, array, and standard-object shapes.
     *
     * @param  mixed  $value
     * @param  string  $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertJsonShape(mixed $value, string $field): void
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new \InvalidArgumentException($field.' contains a non-finite number');
            }

            return;
        }

        if (is_string($value)) {
            if (preg_match('//u', $value) !== 1) {
                throw new \InvalidArgumentException($field.' contains invalid UTF-8');
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('//u', $key) !== 1) {
                    throw new \InvalidArgumentException($field.' contains an invalid UTF-8 map key');
                }

                $this->assertJsonShape($item, $field);
            }

            return;
        }

        if ($value instanceof stdClass) {
            foreach (get_object_vars($value) as $key => $item) {
                if (preg_match('//u', $key) !== 1) {
                    throw new \InvalidArgumentException($field.' contains an invalid UTF-8 object key');
                }

                $this->assertJsonShape($item, $field);
            }

            return;
        }

        throw new \InvalidArgumentException($field.' contains an unsupported JSON object or value');
    }
}
