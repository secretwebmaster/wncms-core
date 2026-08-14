<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\User;
use Wncms\Services\Security\SecurityEventService;

final class ServiceTokenService
{
    public function __construct(
        private AbilityTemplateRegistry $templates,
        private TokenHasher $hasher,
        private SecurityEventService $events,
    ) {}

    /** @return array<string, mixed> */
    public function optionsFor(User $actor): array
    {
        return $this->templates->optionsFor($actor);
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function listQuery(User $actor): Builder
    {
        $modelClass = wncms()->getModelClass('api_service_token');

        return $modelClass::query()->where('user_id', $actor->getKey())->latest('created_at');
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function showQuery(User $actor, string $tokenId): Builder
    {
        return $this->listQuery($actor)->where('token_id', $tokenId);
    }

    /**
     * Create one actor-bounded hash-only service token.
     *
     * @param  array<string, mixed>  $input
     * @return array{token: string, model: \Wncms\Models\ApiServiceToken}
     */
    public function create(AuthenticationContext $context, array $input): array
    {
        $actor = $this->interactiveActor($context);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['name' => ['The name must contain between 1 and 120 characters.']]);
        }

        $template = trim((string) ($input['template'] ?? ''));
        $abilities = $this->templates->resolveGrant(
            $actor,
            $template,
            (array) ($input['additions'] ?? []),
            (array) ($input['removals'] ?? []),
        );
        $this->assertCredentialAbilityCeiling($context, $abilities);
        $websiteIds = $this->websiteScope($actor, $context, (array) ($input['website_ids'] ?? []));
        $expiresAt = $this->expiry($actor, $input['expires_in_days'] ?? null);
        $material = $this->hasher->issue('wncms_st');
        $modelClass = wncms()->getModelClass('api_service_token');

        $model = $this->events->withinTransaction(function () use (
            $modelClass, $material, $actor, $name, $template, $abilities, $websiteIds, $expiresAt
        ): ApiServiceToken {
            return $modelClass::create([
                'token_id' => $material['public_id'],
                'token_hash' => $material['hash'],
                'user_id' => $actor->getKey(),
                'name' => $name,
                'ability_template' => $template,
                'abilities' => $abilities,
                'website_ids' => $websiteIds,
                'expires_at' => $expiresAt,
            ]);
        }, $this->event('auth.service_token.created', $context, $material['public_id'], $websiteIds), null, $this->mutationConnections());

        return ['token' => $material['plain_text'], 'model' => $model];
    }

    /**
     * Atomically replace a token's public ID and secret while preserving its grant metadata.
     *
     * @return array{token: string, model: \Wncms\Models\ApiServiceToken}
     */
    public function rotate(AuthenticationContext $context, ApiServiceToken $token): array
    {
        $actor = $this->interactiveActor($context);
        $this->assertOwned($actor, $token);
        if ($token->revoked_at !== null) {
            throw ValidationException::withMessages(['token' => ['A revoked service token cannot be rotated.']]);
        }

        $material = $this->hasher->issue('wncms_st');
        $oldId = (string) $token->token_id;
        $model = $this->events->withinTransaction(function () use ($token, $material): ApiServiceToken {
            $modelClass = wncms()->getModelClass('api_service_token');
            $updated = $modelClass::query()
                ->whereKey($token->getKey())
                ->whereNull('revoked_at')
                ->where('token_id', $token->token_id)
                ->update([
                    'token_id' => $material['public_id'],
                    'token_hash' => $material['hash'],
                    'last_used_at' => null,
                    'updated_at' => CarbonImmutable::now('UTC'),
                ]);
            if ($updated !== 1) {
                throw ValidationException::withMessages(['token' => ['The service token changed before rotation completed.']]);
            }

            return $token->refresh();
        }, $this->event('auth.service_token.rotated', $context, $oldId, (array) $token->website_ids), null, $this->mutationConnections());

        return ['token' => $material['plain_text'], 'model' => $model];
    }

    public function revoke(AuthenticationContext $context, ApiServiceToken $token): void
    {
        $actor = $this->interactiveActor($context);
        $this->assertOwned($actor, $token);

        $this->events->withinTransaction(function () use ($token): void {
            $modelClass = wncms()->getModelClass('api_service_token');
            $modelClass::query()->whereKey($token->getKey())->whereNull('revoked_at')->update([
                'revoked_at' => CarbonImmutable::now('UTC'),
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
        }, $this->event('auth.service_token.revoked', $context, (string) $token->token_id, (array) $token->website_ids), null, $this->mutationConnections());
    }

    private function interactiveActor(AuthenticationContext $context): User
    {
        $actor = $context->actor();
        if ($context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS || ! $actor instanceof User) {
            throw ValidationException::withMessages(['credential' => ['Interactive authentication is required.']]);
        }

        return $actor;
    }

    /** @param array<int, string> $abilities */
    private function assertCredentialAbilityCeiling(AuthenticationContext $context, array $abilities): void
    {
        if (in_array('*', $context->abilities(), true)) {
            return;
        }

        $excess = array_values(array_diff($abilities, $context->abilities()));
        if ($excess !== []) {
            throw ValidationException::withMessages(['abilities' => ['Abilities exceed the current credential ceiling.']]);
        }
    }

    /** @param array<int, mixed> $requested @return array<int, int> */
    private function websiteScope(User $actor, AuthenticationContext $context, array $requested): array
    {
        $requested = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $requested),
            static fn (int $id): bool => $id > 0,
        )));
        if ($requested === []) {
            throw ValidationException::withMessages(['website_ids' => ['At least one website is required.']]);
        }

        $owned = array_map('intval', $actor->websites()->whereIn('websites.id', $requested)->pluck('websites.id')->all());
        $credentialScope = array_map('intval', $context->websiteIds());
        if (count($owned) !== count($requested) || count(array_intersect($requested, $credentialScope)) !== count($requested)) {
            throw ValidationException::withMessages(['website_ids' => ['Website scope exceeds the actor or credential boundary.']]);
        }
        if (count($requested) > 1 && ! $actor->checkPermissionTo('api_token_create_cross_site')) {
            throw ValidationException::withMessages(['website_ids' => ['Cross-site service tokens require additional permission.']]);
        }

        sort($requested);

        return $requested;
    }

    private function expiry(User $actor, mixed $choice): ?CarbonImmutable
    {
        if ($choice === 'permanent' || $choice === null) {
            if ($choice !== 'permanent') {
                throw ValidationException::withMessages(['expires_in_days' => ['An explicit expiry choice is required.']]);
            }
            if (! $actor->checkPermissionTo('api_token_create_permanent')) {
                throw ValidationException::withMessages(['expires_in_days' => ['Permanent service tokens require additional permission.']]);
            }

            return null;
        }

        $days = filter_var($choice, FILTER_VALIDATE_INT);
        if (! in_array($days, [30, 90, 365], true)) {
            throw ValidationException::withMessages(['expires_in_days' => ['Expiry must be 30, 90, 365, or permanent.']]);
        }

        return CarbonImmutable::now('UTC')->addDays($days);
    }

    private function assertOwned(User $actor, ApiServiceToken $token): void
    {
        if ((string) $token->user_id !== (string) $actor->getKey()) {
            throw ValidationException::withMessages(['token' => ['Service token not found.']]);
        }
    }

    /** @param array<int, int|string> $websiteIds @return array<string, mixed> */
    private function event(string $type, AuthenticationContext $context, string $targetId, array $websiteIds): array
    {
        return [
            'type' => $type,
            'severity' => 'warning',
            'outcome' => 'succeeded',
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => $context->actor()::class,
                'actor_id' => $context->actorId(),
                'target_type' => wncms()->getModelClass('api_service_token'),
                'target_id' => $targetId,
                'credential_type' => $context->credentialType(),
                'credential_id' => $context->credentialPublicId(),
                'session_id' => $context->sessionPublicId(),
                'website_ids' => $websiteIds,
            ],
        ];
    }

    /** @return array<int, string> */
    private function mutationConnections(): array
    {
        return $this->events->modelConnectionNames(['api_service_token']);
    }
}
