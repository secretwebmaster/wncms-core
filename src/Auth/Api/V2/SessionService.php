<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Services\Security\SecurityEventService;

final class SessionService
{
    /**
     * Create the interactive-session lifecycle service.
     *
     * @param  \Wncms\Services\Security\SecurityEventService  $events
     */
    public function __construct(private SecurityEventService $events)
    {
    }

    /**
     * List an actor's sessions using only opaque IDs and safe metadata.
     *
     * @param  \Wncms\Models\User  $user
     * @param  string|null  $currentSessionId
     * @return array<int, array<string, mixed>>
     */
    public function listing(User $user, ?string $currentSessionId = null): array
    {
        return $user->apiSessions()
            ->latest('created_at')
            ->get()
            ->map(fn (ApiSession $session): array => $this->toSafeArray($session, $currentSessionId))
            ->all();
    }

    /**
     * Revoke one interactive session and all credentials in its family.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  string  $reason
     * @return void
     */
    public function revoke(ApiSession $session, string $reason): void
    {
        $this->events->withinTransaction(function () use ($session, $reason): void {
            $this->revokeRows($session, $reason);
        }, [
            'type' => 'auth.session.revoked',
            'severity' => 'warning',
            'outcome' => 'succeeded',
            'context' => $this->eventContext($session, $reason),
        ], null, $this->mutationConnectionNames());
    }

    /**
     * Revoke all interactive sessions for an actor, optionally preserving one row.
     *
     * Service tokens are intentionally stored separately and never touched.
     *
     * @param  \Wncms\Models\User  $user
     * @param  int|null  $exceptSessionId
     * @return int
     */
    public function revokeAll(User $user, ?int $exceptSessionId = null): int
    {
        return $this->events->withinTransaction(function () use ($user, $exceptSessionId): int {
            $sessionModel = wncms()->getModelClass('api_session');
            $query = $sessionModel::query()->where('user_id', $user->getKey())->whereNull('revoked_at');
            if ($exceptSessionId !== null) {
                $query->whereKeyNot($exceptSessionId);
            }

            $sessions = $query->get();
            foreach ($sessions as $session) {
                $this->revokeRows($session, 'logout_all');
            }

            return $sessions->count();
        }, [
            'type' => 'auth.logout_all.succeeded',
            'severity' => 'warning',
            'outcome' => 'succeeded',
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => $user::class,
                'actor_id' => $user->getKey(),
                'context' => ['reason' => 'logout_all'],
            ],
        ], null, $this->mutationConnectionNames());
    }

    /**
     * Idempotently log out one resolved interactive session.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @return void
     */
    public function logout(ApiSession $session): void
    {
        $this->events->withinTransaction(function () use ($session): void {
            $this->revokeRows($session, 'logout');
        }, [
            'type' => 'auth.logout.succeeded',
            'severity' => 'info',
            'outcome' => 'succeeded',
            'context' => $this->eventContext($session, 'logout'),
        ], null, $this->mutationConnectionNames());
    }

    /**
     * Return every connection participating in session-family mutations.
     *
     * @return array<int, string>
     */
    private function mutationConnectionNames(): array
    {
        return $this->events->modelConnectionNames([
            'api_session',
            'api_access_token',
            'api_refresh_token',
        ]);
    }

    /**
     * Revoke the session row and its access/refresh credentials without service tokens.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  string  $reason
     * @return void
     */
    private function revokeRows(ApiSession $session, string $reason): void
    {
        $now = CarbonImmutable::now();
        $sessionModel = wncms()->getModelClass('api_session');
        $sessionModel::query()->whereKey($session->getKey())->whereNull('revoked_at')->update([
            'revoked_at' => $now,
            'revocation_reason' => $reason,
            'updated_at' => $now,
        ]);

        foreach (['api_access_token', 'api_refresh_token'] as $modelKey) {
            $modelClass = wncms()->getModelClass($modelKey);
            $modelClass::query()->where('session_id', $session->getKey())->whereNull('revoked_at')->update([
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Return safe public session metadata.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  string|null  $currentSessionId
     * @return array<string, mixed>
     */
    private function toSafeArray(ApiSession $session, ?string $currentSessionId): array
    {
        return [
            'id' => (string) $session->session_id,
            'device_name' => $session->device_name,
            'refresh_transport' => (string) $session->refresh_transport,
            'remembered' => (bool) $session->remembered,
            'current' => $currentSessionId !== null && hash_equals($currentSessionId, (string) $session->session_id),
            'last_activity_at' => $session->last_activity_at?->toAtomString(),
            'expires_at' => $session->expires_at?->toAtomString(),
            'revoked_at' => $session->revoked_at?->toAtomString(),
            'created_at' => $session->created_at?->toAtomString(),
        ];
    }

    /**
     * Build one allowlisted mandatory session-event context.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  string  $reason
     * @return array<string, mixed>
     */
    private function eventContext(ApiSession $session, string $reason): array
    {
        return [
            'surface' => 'api_v2',
            'actor_type' => wncms()->getModelClass('user'),
            'actor_id' => $session->user_id,
            'session_id' => $session->session_id,
            'context' => ['reason' => $reason],
        ];
    }
}
