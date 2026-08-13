<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Wncms\Models\ApiSession;
use Wncms\Services\Security\SecurityEventService;

final class StepUpService
{
    private const INVALIDATING_EVENTS = [
        'auth.password.changed',
        'auth.password.reset_succeeded',
        'auth.session.revoked',
        'auth.logout_all.succeeded',
    ];

    public function __construct(
        private TokenHasher $hasher,
        private SecurityEventService $events,
    ) {}

    /**
     * Issue one hash-only proof for exact allowed purposes.
     *
     * @param  array<int, string>  $purposes
     */
    public function issue(ApiSession $session, array $purposes): string
    {
        $purposes = $this->purposes($purposes);
        if (count($purposes) !== 1 || $session->revoked_at !== null || ($session->expires_at !== null && ! $session->expires_at->isFuture())) {
            throw new StepUpException('risk.step_up_invalid', 401);
        }

        $material = $this->hasher->issue('wncms_su');
        $expiresAt = CarbonImmutable::now('UTC')->addSeconds(AuthSecurityConfig::fromRuntime()->stepUpLifetimeSeconds());
        $proofModel = wncms()->getModelClass('api_step_up_proof');

        $this->events->withinTransaction(function () use ($proofModel, $session, $purposes, $material, $expiresAt): void {
            $proofModel::create([
                'proof_id' => $material['public_id'],
                'proof_hash' => $material['hash'],
                'user_id' => $session->user_id,
                'session_id' => $session->id,
                'purposes' => $purposes,
                'security_event_id' => $this->latestInvalidatingEventId((int) $session->user_id),
                'expires_at' => $expiresAt,
            ]);
        }, [
            'type' => 'auth.step_up.succeeded',
            'severity' => 'info',
            'outcome' => 'succeeded',
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => wncms()->getModelClass('user'),
                'actor_id' => $session->user_id,
                'session_id' => $session->session_id,
            ],
        ], null, $this->events->modelConnectionNames(['api_step_up_proof', 'api_session']));

        return $material['plain_text'];
    }

    /**
     * Atomically consume a proof bound to the actor, interactive session, and exact purpose.
     *
     * @throws \Wncms\Auth\Api\V2\StepUpException
     */
    public function consume(AuthenticationContext $context, string $proof, string $purpose): void
    {
        if ($context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS || $context->sessionPublicId() === null) {
            $this->recordFailure($context, 'risk.credential_type_denied');
            throw new StepUpException('risk.credential_type_denied', 403);
        }

        $publicId = $this->publicId($proof);
        if ($publicId === null) {
            $this->recordFailure($context, 'risk.step_up_invalid');
            throw new StepUpException('risk.step_up_invalid', 401);
        }

        $proofModel = wncms()->getModelClass('api_step_up_proof');
        $sessionModel = wncms()->getModelClass('api_session');

        try {
            $this->events->withinTransaction(function () use ($proofModel, $sessionModel, $context, $publicId, $proof, $purpose): void {
                $row = $proofModel::query()->where('proof_id', $publicId)->lockForUpdate()->first();
                $session = $sessionModel::query()->where('session_id', $context->sessionPublicId())->lockForUpdate()->first();
                if ($row === null || $session === null || ! $this->hasher->matches($proof, (string) $row->proof_hash)
                    || (string) $row->user_id !== (string) $context->actorId()
                    || (int) $row->session_id !== (int) $session->id
                    || ! in_array($purpose, (array) $row->purposes, true)
                    || $row->consumed_at !== null || $session->revoked_at !== null
                    || $this->latestInvalidatingEventId((int) $row->user_id) > (int) $row->security_event_id) {
                    throw new StepUpException('risk.step_up_invalid', 401);
                }
                if (! $row->expires_at->isFuture()) {
                    throw new StepUpException('risk.step_up_expired', 401);
                }

                $updated = $proofModel::query()->whereKey($row->getKey())->whereNull('consumed_at')->update([
                    'consumed_at' => CarbonImmutable::now('UTC'),
                    'updated_at' => CarbonImmutable::now('UTC'),
                ]);
                if ($updated !== 1) {
                    throw new StepUpException('risk.step_up_invalid', 401);
                }
            }, $this->event($context, 'auth.step_up.succeeded', 'succeeded'), null, $this->events->modelConnectionNames(['api_step_up_proof', 'api_session']));
        } catch (StepUpException $exception) {
            $this->recordFailure($context, $exception->errorCode);
            throw $exception;
        }
    }

    /**
     * Atomically reserve one valid proof before protected downstream execution.
     */
    public function reserve(AuthenticationContext $context, string $proof, string $purpose): string
    {
        if ($context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS || $context->sessionPublicId() === null) {
            $this->recordFailure($context, 'risk.credential_type_denied');
            throw new StepUpException('risk.credential_type_denied', 403);
        }
        $publicId = $this->publicId($proof);
        if ($publicId === null) {
            $this->recordFailure($context, 'risk.step_up_invalid');
            throw new StepUpException('risk.step_up_invalid', 401);
        }

        $proofModel = wncms()->getModelClass('api_step_up_proof');
        $sessionModel = wncms()->getModelClass('api_session');
        $connections = $this->events->modelConnectionNames(['api_step_up_proof', 'api_session', 'api_security_event']);
        if (count($connections) !== 1) {
            throw new \RuntimeException('Security mutation and event connections must match.');
        }
        $row = $proofModel::query()->where('proof_id', $publicId)->first();
        $session = $sessionModel::query()->where('session_id', $context->sessionPublicId())->first();
        try {
            $this->assertUsable($row, $session, $context, $proof, $purpose);
        } catch (StepUpException $exception) {
            $this->recordFailure($context, $exception->errorCode);
            throw $exception;
        }
        $reservationId = (string) Str::uuid();
        $now = CarbonImmutable::now('UTC');
        $updated = $proofModel::query()
            ->whereKey($row->getKey())
            ->whereNull('consumed_at')
            ->whereNull('reservation_id')
            ->update([
                'reservation_id' => $reservationId,
                'reserved_at' => $now,
                'updated_at' => $now,
            ]);
        if ($updated !== 1) {
            $this->recordFailure($context, 'risk.step_up_invalid');
            throw new StepUpException('risk.step_up_invalid', 401);
        }

        return $reservationId;
    }

    /**
     * Reserve a proof for one explicitly selected purpose declared by an operation.
     *
     * @param  array<int, string>  $allowedPurposes
     */
    public function reserveAny(AuthenticationContext $context, string $proof, array $allowedPurposes, string $selectedPurpose): string
    {
        if ($selectedPurpose === '' || ! in_array($selectedPurpose, $allowedPurposes, true)) {
            $this->recordFailure($context, 'risk.step_up_invalid');
            throw new StepUpException('risk.step_up_invalid', 401);
        }

        return $this->reserve($context, $proof, $selectedPurpose);
    }

    /**
     * Persist a mandatory failed reauthentication event.
     */
    public function reject(AuthenticationContext $context, string $reason): void
    {
        $this->recordFailure($context, $reason);
    }

    /**
     * Release an exact proof reservation after downstream rejection.
     */
    public function releaseReservation(string $reservationId): void
    {
        $proofModel = wncms()->getModelClass('api_step_up_proof');
        $proofModel::query()->where('reservation_id', $reservationId)->whereNull('consumed_at')->update([
            'reservation_id' => null,
            'reserved_at' => null,
            'updated_at' => CarbonImmutable::now('UTC'),
        ]);
    }

    /**
     * Consume an exact proof reservation with its mandatory event.
     */
    public function confirmReservation(string $reservationId, AuthenticationContext $context): void
    {
        $proofModel = wncms()->getModelClass('api_step_up_proof');
        $this->events->withinTransaction(function () use ($proofModel, $reservationId): void {
            $updated = $proofModel::query()->where('reservation_id', $reservationId)->whereNull('consumed_at')->update([
                'consumed_at' => CarbonImmutable::now('UTC'),
                'reservation_id' => null,
                'reserved_at' => null,
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
            if ($updated !== 1) {
                throw new StepUpException('risk.step_up_invalid', 401);
            }
        }, $this->event($context, 'auth.step_up.succeeded', 'succeeded'), null, $this->events->modelConnectionNames(['api_step_up_proof', 'api_session']));
    }

    private function latestInvalidatingEventId(int $actorId): int
    {
        $eventModel = wncms()->getModelClass('api_security_event');

        return (int) $eventModel::query()
            ->where('actor_id', $actorId)
            ->whereIn('event_type', self::INVALIDATING_EVENTS)
            ->max('id');
    }

    private function assertUsable(mixed $row, mixed $session, AuthenticationContext $context, string $proof, string $purpose): void
    {
        if ($row === null || $session === null || ! $this->hasher->matches($proof, (string) $row->proof_hash)
            || (string) $row->user_id !== (string) $context->actorId()
            || (int) $row->session_id !== (int) $session->id
            || ! in_array($purpose, (array) $row->purposes, true)
            || $row->consumed_at !== null || $session->revoked_at !== null
            || $this->latestInvalidatingEventId((int) $row->user_id) > (int) $row->security_event_id) {
            throw new StepUpException('risk.step_up_invalid', 401);
        }
        if (! $row->expires_at->isFuture()) {
            throw new StepUpException('risk.step_up_expired', 401);
        }
    }

    /** @return array<int, string> */
    private function purposes(array $purposes): array
    {
        $purposes = array_values(array_unique(array_filter(array_map(
            static fn ($purpose): string => is_string($purpose) ? trim($purpose) : '',
            $purposes,
        ))));
        sort($purposes);

        return $purposes;
    }

    private function publicId(string $proof): ?string
    {
        return preg_match('/^wncms_su_([0-9A-HJKMNP-TV-Z]{26})\.[A-Za-z0-9_-]+$/D', $proof, $matches) === 1
            ? $matches[1]
            : null;
    }

    private function recordFailure(AuthenticationContext $context, string $reason): void
    {
        $event = $this->event($context, 'auth.step_up.failed', 'denied');
        $event['context']['error_code'] = $reason;
        $this->events->withinTransaction(static fn (): null => null, $event, null, $this->events->modelConnectionNames(['api_step_up_proof', 'api_session']));
    }

    /** @return array<string, mixed> */
    private function event(AuthenticationContext $context, string $type, string $outcome): array
    {
        return [
            'type' => $type,
            'severity' => $outcome === 'succeeded' ? 'info' : 'warning',
            'outcome' => $outcome,
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => $context->actor()::class,
                'actor_id' => $context->actorId(),
                'credential_type' => $context->credentialType(),
                'credential_id' => $context->credentialPublicId(),
                'session_id' => $context->sessionPublicId(),
            ],
        ];
    }
}
