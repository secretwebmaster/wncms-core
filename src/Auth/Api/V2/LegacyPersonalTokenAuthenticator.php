<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Models\User;

final class LegacyPersonalTokenAuthenticator
{
    private const REQUIRED_COLUMNS = ['id', 'tokenable_type', 'tokenable_id', 'token'];

    private const OPTIONAL_COLUMNS = ['abilities', 'last_used_at', 'expires_at', 'created_at'];

    public function __construct(private LegacyTokenPolicy $policy) {}

    public function authenticate(ApiCredential $credential, ApiOperationContract $operation): AuthenticationContext
    {
        if (! $credential->isLegacyCandidate() || ! $this->policy->allows($operation, CarbonImmutable::now('UTC'))) {
            throw new AuthenticationException('authentication.invalid_token');
        }
        $schema = $this->schemaStatus();
        if (! $schema['compatible']) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        [$id, $hashInput] = $this->hashInput($credential);
        $query = DB::table('personal_access_tokens')->where('token', hash('sha256', $hashInput));
        if ($id !== null) {
            $query->where('id', $id);
        }
        $token = $query->first();
        if ($token === null) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        $userClass = wncms()->getModelClass('user');
        if (! is_a((string) $token->tokenable_type, $userClass, true)) {
            throw new AuthenticationException('authentication.invalid_token');
        }
        if (isset($token->expires_at) && $token->expires_at !== null && ! CarbonImmutable::parse($token->expires_at)->isFuture()) {
            throw new AuthenticationException('authentication.legacy_token_expired');
        }
        $user = $userClass::query()->find($token->tokenable_id);
        if (! $user instanceof User || (method_exists($user, 'hasRole') && $user->hasRole('suspended'))) {
            throw new AuthenticationException('authentication.invalid_token');
        }
        $websiteIds = array_map('intval', $user->websites()->pluck('websites.id')->all());
        if (count($websiteIds) !== 1) {
            throw new AuthenticationException('authentication.invalid_token');
        }
        $abilities = isset($token->abilities) ? json_decode((string) $token->abilities, true) : ['*'];
        if (! is_array($abilities)) {
            $abilities = [];
        }

        if (in_array('last_used_at', $schema['optional_present'], true)) {
            try {
                $now = CarbonImmutable::now('UTC');
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->where(function ($query) use ($now): void {
                        $query->whereNull('last_used_at')->orWhere('last_used_at', '<=', $now->subMinutes(5));
                    })
                    ->update(['last_used_at' => $now]);
            } catch (\Throwable $exception) {
                Log::warning('WNCMS legacy personal token last-used metadata could not be updated.', [
                    'credential_id' => (string) $token->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return new AuthenticationContext($user, ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, (string) $token->id, null, $abilities, $websiteIds);
    }

    /** @return array{present: bool, compatible: bool, required_missing: array<int, string>, optional_present: array<int, string>} */
    public function schemaStatus(): array
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return ['present' => false, 'compatible' => false, 'required_missing' => self::REQUIRED_COLUMNS, 'optional_present' => []];
        }
        $columns = Schema::getColumnListing('personal_access_tokens');
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $columns));

        return [
            'present' => true,
            'compatible' => $missing === [],
            'required_missing' => $missing,
            'optional_present' => array_values(array_intersect(self::OPTIONAL_COLUMNS, $columns)),
        ];
    }

    /** @return array{0: int|null, 1: string} */
    private function hashInput(ApiCredential $credential): array
    {
        if ($credential->publicId() !== null && str_contains($credential->plainText(), '|')) {
            [, $secret] = explode('|', $credential->plainText(), 2);

            return [(int) $credential->publicId(), $secret];
        }

        return [null, $credential->plainText()];
    }
}
