<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Http\Controllers\Api\V1\ApiController;

class ApiV2Controller extends ApiController
{
    protected function ok(
        mixed $data = null,
        string $message = 'success',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'code' => $code,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => [],
        ], $code);
    }

    protected function error(
        string $message = 'fail',
        int $code = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'code' => $code,
            'status' => 'fail',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => $errors,
        ], $code);
    }

    protected function fromThrowable(\Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->error(
                __('validation.failed'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        }

        if ($e instanceof AuthorizationException) {
            return $this->error(__('auth.unauthorized'), Response::HTTP_FORBIDDEN);
        }

        report($e);
        return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
