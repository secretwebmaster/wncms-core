<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\OperationService;

class OperationController extends ApiV2Controller
{
    public const CANCEL_PERMISSION = 'operation_cancel';

    /**
     * Create the API v2 operation controller.
     *
     * @param  \Wncms\Api\V2\OperationService  $operations
     */
    public function __construct(protected OperationService $operations)
    {
    }

    /**
     * Return an operation only to the actor that queued it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $operation = $this->operations->findForActor($id, $this->actorId($request));
        if ($operation === null) {
            return $this->notFound();
        }

        return $this->ok($operation);
    }

    /**
     * Cancel an owned operation through the idempotent mutation route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        if ($this->operations->findForActor($id, $this->actorId($request)) === null) {
            return $this->notFound();
        }

        abort_unless($request->user()?->can(self::CANCEL_PERMISSION), Response::HTTP_FORBIDDEN);

        return $this->ok($this->operations->cancel($id), 'operation_cancelled');
    }

    /**
     * Resolve the authenticated numeric actor identifier.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return int
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function actorId(Request $request): int
    {
        $actorId = $request->user()?->getAuthIdentifier();

        if (is_int($actorId)) {
            if ($actorId <= 0) {
                throw new AuthenticationException('Authenticated actor is required');
            }

            return $actorId;
        }

        if (! is_string($actorId) || preg_match('/^[1-9][0-9]*$/D', $actorId) !== 1) {
            throw new AuthenticationException('Authenticated actor is required');
        }

        $maximum = (string) PHP_INT_MAX;
        if (
            strlen($actorId) > strlen($maximum)
            || (strlen($actorId) === strlen($maximum) && strcmp($actorId, $maximum) > 0)
        ) {
            throw new AuthenticationException('Authenticated actor is required');
        }

        return (int) $actorId;
    }

    /**
     * Return an indistinguishable missing-operation response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(): JsonResponse
    {
        return $this->responseFactory()->failure(
            'resource.not_found',
            'Operation not found',
            Response::HTTP_NOT_FOUND
        );
    }
}
