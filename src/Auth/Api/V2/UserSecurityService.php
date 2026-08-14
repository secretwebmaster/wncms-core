<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Wncms\Models\User;
use Wncms\Notifications\ApiSecurityLink;
use Wncms\Services\Security\SecurityEventService;

final class UserSecurityService
{
    private const EMAIL_TOKEN_TTL_SECONDS = 1800;

    public function __construct(
        private SecurityEventService $events,
        private TokenHasher $hasher,
    ) {}

    public function changePassword(AuthenticationContext $context, string $currentPassword, string $newPassword): void
    {
        $user = $this->interactiveUser($context);
        if (! Hash::check($currentPassword, (string) $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Current password is not valid.']]);
        }

        $this->events->withinTransaction(function () use ($user, $newPassword): void {
            $this->updatePassword($user, $newPassword);
            $this->revokeCredentialRows($user, 'password_changed');
        }, $this->event('auth.password.changed', $user, $context, 'password_changed'), null, $this->mutationConnections());
    }

    public function resetPassword(string $brokerToken, string $email, string $newPassword): void
    {
        $status = Password::reset([
            'email' => $email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'token' => $brokerToken,
        ], function (User $user, string $password): void {
            $this->events->withinTransaction(function () use ($user, $password): void {
                $this->updatePassword($user, $password);
                $this->revokeCredentialRows($user, 'password_reset');
            }, $this->event('auth.password.reset_succeeded', $user, null, 'password_reset'), null, $this->mutationConnections());
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => ['Password reset credential is invalid or expired.']]);
        }
    }

    public function requestPasswordReset(string $email): void
    {
        $user = User::query()->where('email', $email)->first();
        if ($user instanceof User) {
            try {
                Password::sendResetLink(['email' => $email]);
            } catch (\Throwable $exception) {
                Log::warning('WNCMS password-reset notification could not be sent.', ['exception' => $exception::class]);
            }
        }

        $this->events->withinTransaction(static fn (): null => null, [
            'type' => 'auth.password.reset_requested',
            'severity' => 'info',
            'outcome' => 'succeeded',
            'context' => ['surface' => 'api_v2'],
        ], null, $this->events->modelConnectionNames(['api_security_event']));
    }

    public function requestEmailChange(AuthenticationContext $context, string $newEmail): void
    {
        $user = $this->interactiveUser($context);
        $newEmail = strtolower(trim($newEmail));
        if ($newEmail === '' || filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false
            || User::query()->where('email', $newEmail)->whereKeyNot($user->getKey())->exists()) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid or unavailable.']]);
        }

        $token = $this->issueEmailToken($user, $newEmail, 'email_change');
        $this->events->withinTransaction(static fn (): null => null,
            $this->event('auth.email_change.requested', $user, $context, 'email_change_requested'),
            null,
            $this->events->modelConnectionNames(['api_security_event']),
        );

        Notification::route('mail', $newEmail)->notify(new ApiSecurityLink('email_change', $token, $newEmail));
        Notification::route('mail', (string) $user->email)->notify(new ApiSecurityLink('email_change_notice'));
    }

    public function confirmEmailChange(string $verificationToken): void
    {
        $payload = $this->consumeEmailToken($verificationToken, 'email_change');
        $user = User::query()->find($payload['user_id']);
        if (! $user instanceof User || User::query()->where('email', $payload['email'])->whereKeyNot($user->getKey())->exists()) {
            throw ValidationException::withMessages(['token' => ['Email verification credential is invalid or expired.']]);
        }

        $this->events->withinTransaction(function () use ($user, $payload): void {
            $user->forceFill(['email' => $payload['email'], 'email_verified_at' => CarbonImmutable::now('UTC')])->save();
        }, $this->event('auth.email_change.confirmed', $user, null, 'email_change_confirmed'), null,
            $this->events->modelConnectionNames(['user']));
    }

    public function sendEmailVerification(AuthenticationContext $context): void
    {
        $user = $this->interactiveUser($context);
        if ($user->email_verified_at !== null) {
            return;
        }

        $token = $this->issueEmailToken($user, (string) $user->email, 'email_verification');
        $user->notify(new ApiSecurityLink('email_verification', $token, (string) $user->email));
    }

    public function confirmEmailVerification(string $verificationToken): void
    {
        $payload = $this->consumeEmailToken($verificationToken, 'email_verification');
        $user = User::query()->find($payload['user_id']);
        if (! $user instanceof User || ! hash_equals(strtolower((string) $user->email), strtolower($payload['email']))) {
            throw ValidationException::withMessages(['token' => ['Email verification credential is invalid or expired.']]);
        }

        $this->events->withinTransaction(function () use ($user): void {
            $user->forceFill(['email_verified_at' => CarbonImmutable::now('UTC')])->save();
        }, $this->event('auth.email_verified', $user, null, 'email_verified'), null,
            $this->events->modelConnectionNames(['user']));
    }

    public function revokeAllCredentials(User $user, string $reason): void
    {
        $this->events->withinTransaction(function () use ($user, $reason): void {
            $this->revokeCredentialRows($user, $reason);
        }, $this->event('security.auth_policy.changed', $user, null, $reason), null, $this->mutationConnections());
    }

    private function updatePassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
            'api_token' => null,
        ])->save();
    }

    private function revokeCredentialRows(User $user, string $reason): void
    {
        $now = CarbonImmutable::now('UTC');
        $sessionClass = wncms()->getModelClass('api_session');
        $sessionClass::query()->where('user_id', $user->getKey())->whereNull('revoked_at')->update([
            'revoked_at' => $now, 'revocation_reason' => $reason, 'updated_at' => $now,
        ]);
        foreach (['api_access_token', 'api_refresh_token', 'api_service_token'] as $modelKey) {
            $modelClass = wncms()->getModelClass($modelKey);
            $modelClass::query()->where('user_id', $user->getKey())->whereNull('revoked_at')->update([
                'revoked_at' => $now, 'updated_at' => $now,
            ]);
        }
        if (Schema::hasTable('personal_access_tokens')) {
            foreach (['tokenable_type', 'tokenable_id'] as $column) {
                if (! Schema::hasColumn('personal_access_tokens', $column)) {
                    throw ValidationException::withMessages(['credentials' => ['authentication.legacy_revocation_failed']]);
                }
            }
            DB::table('personal_access_tokens')->where('tokenable_type', $user->getMorphClass())
                ->where('tokenable_id', $user->getKey())->delete();
        }
        DB::table('password_reset_tokens')->where('email', (string) $user->email)->delete();
        $user->forceFill(['api_token' => null, 'remember_token' => Str::random(60)])->save();
    }

    /** @return array{user_id: int|string, email: string, purpose: string} */
    private function consumeEmailToken(string $token, string $purpose): array
    {
        $publicId = $this->emailTokenPublicId($token);
        $record = Cache::pull($this->emailTokenKey($publicId));
        if (! is_array($record) || ($record['purpose'] ?? null) !== $purpose
            || ! isset($record['hash'], $record['user_id'], $record['email'])
            || ! hash_equals((string) $record['hash'], hash('sha256', $token))) {
            throw ValidationException::withMessages(['token' => ['Email verification credential is invalid or expired.']]);
        }

        return ['user_id' => $record['user_id'], 'email' => (string) $record['email'], 'purpose' => $purpose];
    }

    private function issueEmailToken(User $user, string $email, string $purpose): string
    {
        $material = $this->hasher->issue('wncms_cp');
        Cache::put($this->emailTokenKey($material['public_id']), [
            'hash' => $material['hash'],
            'user_id' => $user->getKey(),
            'email' => $email,
            'purpose' => $purpose,
        ], self::EMAIL_TOKEN_TTL_SECONDS);

        return $material['plain_text'];
    }

    private function emailTokenPublicId(string $token): string
    {
        if (preg_match('/^wncms_cp_([0-9A-HJKMNP-TV-Z]{26})\.[A-Za-z0-9_-]+$/D', $token, $matches) !== 1) {
            throw ValidationException::withMessages(['token' => ['Email verification credential is invalid or expired.']]);
        }

        return $matches[1];
    }

    private function emailTokenKey(string $publicId): string
    {
        return 'wncms:api-v2:email-token:'.hash('sha256', $publicId);
    }

    private function interactiveUser(AuthenticationContext $context): User
    {
        if ($context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS || ! $context->actor() instanceof User) {
            throw ValidationException::withMessages(['credential' => ['Interactive authentication is required.']]);
        }

        return $context->actor();
    }

    /** @return array<string, mixed> */
    private function event(string $type, User $user, ?AuthenticationContext $context, string $reason): array
    {
        return ['type' => $type, 'severity' => 'warning', 'outcome' => 'succeeded', 'context' => array_filter([
            'surface' => 'api_v2', 'actor_type' => $user::class, 'actor_id' => $user->getKey(),
            'credential_type' => $context?->credentialType(), 'credential_id' => $context?->credentialPublicId(),
            'session_id' => $context?->sessionPublicId(), 'context' => ['reason' => $reason],
        ], static fn (mixed $value): bool => $value !== null)];
    }

    /** @return array<int, string> */
    private function mutationConnections(): array
    {
        return $this->events->modelConnectionNames([
            'user', 'api_session', 'api_access_token', 'api_refresh_token', 'api_service_token',
        ]);
    }
}
