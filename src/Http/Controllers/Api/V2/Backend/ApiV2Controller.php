<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Http\Controllers\Api\V1\ApiController;

class ApiV2Controller extends ApiController
{
    /**
     * Build a successful API v2 response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @param  array  $meta
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ok(
        mixed $data = null,
        string $message = 'success',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        return $this->responseFactory()->success($data, $message, $code, $meta);
    }

    /**
     * Build a failed API v2 response while preserving the legacy method signature.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array  $errors
     * @param  mixed  $data
     * @param  array  $meta
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(
        string $message = 'fail',
        int $code = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return $this->responseFactory()->failure(
            $this->responseFactory()->errorCodeForStatus($code),
            $message,
            $code,
            $errors,
            $data,
            $meta
        );
    }

    /**
     * Convert a throwable into a stable API v2 failure response.
     *
     * @param  \Throwable  $e
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function fromThrowable(\Throwable $e): JsonResponse
    {
        return $this->responseFactory()->fromThrowable($e);
    }

    /**
     * Map a mandatory security-audit failure to the stable fail-closed response.
     *
     * The audited service owns rollback; controllers own transport mapping.
     *
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function securityAuditUnavailable(\Throwable $exception): JsonResponse
    {
        report($exception);

        return $this->responseFactory()->failure(
            'security.audit_unavailable',
            'Security audit is unavailable',
            Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    /**
     * Resolve the shared API v2 response factory.
     *
     * @return \Wncms\Api\V2\ApiResponseFactory
     */
    protected function responseFactory(): ApiResponseFactory
    {
        return app(ApiResponseFactory::class);
    }

    protected function resolveModelClass(mixed $modelKey): ?string
    {
        if (!is_string($modelKey) || trim($modelKey) === '') {
            return null;
        }

        try {
            return wncms()->getModelClass($modelKey);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveModelOrFail(string $modelClass, int|string $id): ?Model
    {
        return $modelClass::query()->find($id);
    }

    protected function normalizePerPage(Request $request, int $default = 20, int $max = 100): int
    {
        $perPage = (int) $request->input('per_page', $default);
        if ($perPage <= 0) {
            return $default;
        }

        return min($perPage, $max);
    }
}
