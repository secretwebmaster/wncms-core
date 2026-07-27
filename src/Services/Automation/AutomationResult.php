<?php

namespace Wncms\Services\Automation;

class AutomationResult
{
    /**
     * Build a success automation result envelope.
     *
     * @param string $message
     * @param mixed $data
     * @param array $meta
     * @param int $code
     * @return array
     */
    public static function success(string $message, mixed $data = null, array $meta = [], int $code = 200): array
    {
        return static::make($code, 'success', $message, $data, $meta, []);
    }

    /**
     * Build a failed automation result envelope.
     *
     * @param string $message
     * @param mixed $data
     * @param array $meta
     * @param array $errors
     * @param int $code
     * @return array
     */
    public static function fail(string $message, mixed $data = null, array $meta = [], array $errors = [], int $code = 400): array
    {
        return static::make($code, 'fail', $message, $data, $meta, $errors);
    }

    /**
     * Build a stable automation result envelope.
     *
     * @param int $code
     * @param string $status
     * @param string $message
     * @param mixed $data
     * @param array $meta
     * @param array $errors
     * @return array
     */
    public static function make(int $code, string $status, string $message, mixed $data = null, array $meta = [], array $errors = []): array
    {
        return [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }
}
