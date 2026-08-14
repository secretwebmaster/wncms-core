<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\ServiceTokenService;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Resources\Api\V2\ServiceTokenResource;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\User;

class ServiceTokenController extends ApiV2Controller
{
    public function __construct(private ServiceTokenService $tokens) {}

    public function options(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        return $this->ok($this->tokens->optionsFor($context->actor()));
    }

    public function index(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        $page = $this->tokens->listQuery($context->actor())->paginate($this->normalizePerPage($request));
        $page->setCollection($page->getCollection()->map(
            fn (ApiServiceToken $token): array => (new ServiceTokenResource($token))->toArray($request),
        ));

        return $this->ok($page);
    }

    public function store(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        try {
            $created = $this->tokens->create($context, $request->only([
                'name', 'template', 'additions', 'removals', 'website_ids', 'expires_in_days',
            ]));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok([
            'token' => $created['token'],
            'service_token' => (new ServiceTokenResource($created['model']))->toArray($request),
        ], 'service_token_created', Response::HTTP_CREATED);
    }

    public function show(Request $request, string $tokenId): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        $token = $this->tokens->showQuery($context->actor(), $tokenId)->first();
        if (! $token instanceof ApiServiceToken) {
            return $this->notFound();
        }

        return $this->ok((new ServiceTokenResource($token))->toArray($request));
    }

    public function rotate(Request $request, string $tokenId): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        $token = $this->tokens->showQuery($context->actor(), $tokenId)->first();
        if (! $token instanceof ApiServiceToken) {
            return $this->notFound();
        }

        try {
            $rotated = $this->tokens->rotate($context, $token);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok([
            'token' => $rotated['token'],
            'service_token' => (new ServiceTokenResource($rotated['model']))->toArray($request),
        ], 'service_token_rotated');
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }

        $token = $this->tokens->showQuery($context->actor(), $tokenId)->first();
        if (! $token instanceof ApiServiceToken) {
            return $this->notFound();
        }

        try {
            $this->tokens->revoke($context, $token);
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'service_token_revoked');
    }

    private function interactiveContext(Request $request): ?AuthenticationContext
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);

        return $context instanceof AuthenticationContext
            && $context->credentialType() === ApiCredential::TYPE_INTERACTIVE_ACCESS
            && $context->actor() instanceof User
            ? $context
            : null;
    }

    private function credentialTypeDenied(): JsonResponse
    {
        return $this->responseFactory()->failure(
            'risk.credential_type_denied',
            'Interactive authentication is required',
            Response::HTTP_FORBIDDEN,
        );
    }

    private function notFound(): JsonResponse
    {
        return $this->responseFactory()->failure(
            'resource.not_found',
            'Resource not found',
            Response::HTTP_NOT_FOUND,
        );
    }
}
