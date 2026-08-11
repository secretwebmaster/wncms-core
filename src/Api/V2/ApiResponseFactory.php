<?php

namespace Wncms\Api\V2;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Wncms\Services\Automation\AutomationResult;

class ApiResponseFactory
{
    /**
     * Build a successful API v2 response envelope.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @param  array  $meta
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(
        mixed $data = null,
        string $message = 'success',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $requestId = $this->requestId();
        $meta = array_merge($meta, ['request_id' => $requestId]);

        return response()
            ->json(AutomationResult::success($message, $data, $meta, $code), $code)
            ->header('X-Request-ID', $requestId);
    }

    /**
     * Build a failed API v2 response envelope.
     *
     * @param  string  $errorCode
     * @param  string  $message
     * @param  int  $code
     * @param  array  $errors
     * @param  mixed  $data
     * @param  array  $meta
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failure(
        string $errorCode,
        string $message,
        int $code,
        array $errors = [],
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        $requestId = $this->requestId();
        $meta = array_merge($meta, [
            'request_id' => $requestId,
            'error_code' => $errorCode,
        ]);

        return response()
            ->json(AutomationResult::fail($message, $data, $meta, $errors, $code), $code)
            ->header('X-Request-ID', $requestId);
    }

    /**
     * Convert a throwable into a stable API v2 failure response.
     *
     * Unexpected exception details are exposed only when application debug mode is enabled.
     *
     * @param  \Throwable  $exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fromThrowable(\Throwable $exception): JsonResponse
    {
        return $this->throwableResponse($exception, true);
    }

    /**
     * Convert an already reported throwable into a stable API v2 failure response.
     *
     * @param  \Throwable  $exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fromReportedThrowable(\Throwable $exception): JsonResponse
    {
        return $this->throwableResponse($exception, false);
    }

    /**
     * Convert a throwable into a stable API v2 response and optionally report it.
     *
     * @param  \Throwable  $exception
     * @param  bool  $reportUnexpected
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function throwableResponse(\Throwable $exception, bool $reportUnexpected): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->failure(
                'validation.failed',
                __('validation.failed'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->errors()
            );
        }

        if ($exception instanceof AuthenticationException) {
            return $this->failure(
                'authentication.invalid_token',
                __('auth.unauthenticated'),
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($exception instanceof AuthorizationException) {
            return $this->failure(
                'authorization.denied',
                __('auth.unauthorized'),
                Response::HTTP_FORBIDDEN
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->failure(
                'resource.not_found',
                'Resource not found',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = trim($exception->getMessage());

            if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                if ($reportUnexpected) {
                    report($exception);
                }

                return $this->failure(
                    'server.unexpected_error',
                    config('app.debug') && $message !== '' ? $message : 'Unexpected server error',
                    $status
                );
            }

            return $this->failure(
                $this->errorCodeForStatus($status),
                $message !== '' ? $message : (Response::$statusTexts[$status] ?? 'Request failed'),
                $status
            );
        }

        if ($reportUnexpected) {
            report($exception);
        }

        return $this->failure(
            'server.unexpected_error',
            config('app.debug') ? $exception->getMessage() : 'Unexpected server error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Resolve the stable non-field error code for an HTTP status.
     *
     * @param  int  $status
     *
     * @return string
     */
    public function errorCodeForStatus(int $status): string
    {
        return match ($status) {
            Response::HTTP_UNAUTHORIZED => 'authentication.invalid_token',
            Response::HTTP_FORBIDDEN => 'authorization.denied',
            Response::HTTP_NOT_FOUND => 'resource.not_found',
            Response::HTTP_CONFLICT => 'request.conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'validation.failed',
            default => $status >= Response::HTTP_INTERNAL_SERVER_ERROR
                ? 'server.unexpected_error'
                : 'validation.failed',
        };
    }

    /**
     * Resolve or create the request ID for the current API request.
     *
     * @return string
     */
    protected function requestId(): string
    {
        $request = app()->bound('request') ? app('request') : null;
        $requestId = null;

        if ($request instanceof Request) {
            $requestId = $request->attributes->get('wncms_api_v2_request_id');
            if (! is_string($requestId) || ! Str::isUuid($requestId)) {
                $requestId = trim((string) $request->headers->get('X-Request-ID', ''));
            }
        }

        if (! is_string($requestId) || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        if ($request instanceof Request) {
            $request->attributes->set('wncms_api_v2_request_id', $requestId);
        }

        return $requestId;
    }
}
