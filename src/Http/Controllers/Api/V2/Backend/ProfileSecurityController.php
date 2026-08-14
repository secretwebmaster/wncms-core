<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\UserSecurityService;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Models\User;

class ProfileSecurityController extends ApiV2Controller
{
    public function __construct(private UserSecurityService $security) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        try {
            $this->security->requestPasswordReset((string) $validated['email']);
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'password_reset_accepted', Response::HTTP_ACCEPTED);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);
        try {
            $this->security->resetPassword($validated['token'], $validated['email'], $validated['password']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(['reauthentication_required' => true], 'password_reset');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }
        $validated = $request->validate([
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $context->actor()->fill($validated)->save();

        return $this->ok($this->profile($context->actor()), 'profile_updated');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);
        try {
            $this->security->changePassword($context, $validated['current_password'], $validated['password']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(['reauthentication_required' => true], 'password_changed');
    }

    public function requestEmailChange(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            $this->security->requestEmailChange($context, $validated['email']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'email_change_verification_sent', Response::HTTP_ACCEPTED);
    }

    public function confirmEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);
        try {
            $this->security->confirmEmailChange($validated['token']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'email_changed');
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (! $context instanceof AuthenticationContext) {
            return $this->credentialTypeDenied();
        }
        try {
            $this->security->sendEmailVerification($context);
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'email_verification_sent', Response::HTTP_ACCEPTED);
    }

    public function confirmEmailVerification(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);
        try {
            $this->security->confirmEmailVerification($validated['token']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok(null, 'email_verified');
    }

    private function interactiveContext(Request $request): ?AuthenticationContext
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);

        return $context instanceof AuthenticationContext
            && $context->credentialType() === ApiCredential::TYPE_INTERACTIVE_ACCESS
            && $context->actor() instanceof User ? $context : null;
    }

    private function credentialTypeDenied(): JsonResponse
    {
        return $this->responseFactory()->failure('risk.credential_type_denied', 'Interactive authentication is required', Response::HTTP_FORBIDDEN);
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        return [
            'id' => $user->getKey(), 'first_name' => $user->first_name, 'last_name' => $user->last_name,
            'nickname' => $user->nickname, 'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }
}
