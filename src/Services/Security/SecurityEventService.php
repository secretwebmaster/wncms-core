<?php

namespace Wncms\Services\Security;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Wncms\Events\ApiSecurityEventRecorded;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Services\Automation\MutationAuditService;

final class SecurityEventService
{
    public const EVENT_TYPES = [
        'auth.login.succeeded', 'auth.login.failed', 'auth.login.throttled',
        'auth.refresh.succeeded', 'auth.refresh.failed', 'auth.refresh.reuse_detected',
        'auth.logout.succeeded', 'auth.logout_all.succeeded', 'auth.session.revoked',
        'auth.password.changed', 'auth.password.reset_requested', 'auth.password.reset_succeeded',
        'auth.email_change.requested', 'auth.email_change.confirmed', 'auth.email_verified',
        'auth.step_up.succeeded', 'auth.step_up.failed',
        'auth.service_token.created', 'auth.service_token.rotated', 'auth.service_token.revoked',
        'auth.legacy.accepted', 'auth.legacy.rejected', 'auth.legacy.cutoff_changed', 'auth.legacy.disabled',
        'risk.plan.created', 'risk.plan.confirmed', 'risk.plan.stale', 'risk.confirmation.reused',
        'security.csrf.denied', 'security.origin.denied', 'security.ability.denied',
        'security.permission.denied', 'security.website_scope.denied', 'security.blade.enabled',
        'security.blade.disabled', 'security.blade.policy_unavailable', 'security.retention.completed',
    ];

    protected const ALLOWED_FIELDS = [
        'surface', 'request_id', 'run_id', 'actor_type', 'actor_id', 'target_type', 'target_id',
        'credential_type', 'credential_id', 'session_id', 'website_ids', 'error_code', 'http_status',
        'mutation_audit_id', 'ip', 'login_identifier', 'user_agent', 'context',
    ];

    protected const ALLOWED_CONTEXT_SCHEMA = [
        'aggregate' => [
            'count' => true,
            'first_occurred_at' => true,
            'last_occurred_at' => true,
            'latest_request_id' => true,
        ],
        'device_name' => true,
        'operation' => true,
        'policy_state' => true,
        'reason' => true,
    ];

    /**
     * Create the mandatory security-event service.
     *
     * @param  \Wncms\Services\Security\SecurityCorrelationHasher  $correlationHasher
     * @param  \Wncms\Services\Automation\MutationAuditService  $redactor
     *
     * @return void
     */
    public function __construct(
        protected SecurityCorrelationHasher $correlationHasher,
        protected MutationAuditService $redactor
    ) {
    }

    /**
     * Record one allowlisted, redacted security event.
     *
     * @param  string  $type
     * @param  string  $severity
     * @param  string  $outcome
     * @param  array  $context
     *
     * @return \Wncms\Models\ApiSecurityEvent
     */
    public function record(string $type, string $severity, string $outcome, array $context = []): ApiSecurityEvent
    {
        $this->validateCatalogValue($type, $severity, $outcome);
        $attributes = $this->buildAttributes($type, $severity, $outcome, $context);
        return $this->persist($attributes);
    }

    /**
     * Commit a security mutation and its mandatory event atomically.
     *
     * @param  callable  $mutation
     * @param  array  $event
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function withinTransaction(callable $mutation, array $event): mixed
    {
        if (!$this->correlationHasher->isConfigured()) {
            throw new \RuntimeException('Security event correlation keys are unavailable.');
        }

        return DB::transaction(function () use ($mutation, $event): mixed {
            $result = $mutation();
            $this->record(
                (string) ($event['type'] ?? ''),
                (string) ($event['severity'] ?? ''),
                (string) ($event['outcome'] ?? ''),
                (array) ($event['context'] ?? [])
            );

            return $result;
        });
    }

    /**
     * Record or atomically increment a high-volume event aggregate.
     *
     * The correlation tuple is event catalog values, surface, HMAC correlations, and
     * configured key version. Aggregates deliberately update only count/timestamps.
     *
     * @param  string  $type
     * @param  string  $severity
     * @param  string  $outcome
     * @param  array  $context
     *
     * @return \Wncms\Models\ApiSecurityEvent
     */
    public function recordAggregate(string $type, string $severity, string $outcome, array $context = []): ApiSecurityEvent
    {
        if (!$this->correlationHasher->isConfigured()) {
            throw new \RuntimeException('Security event correlation keys are unavailable.');
        }

        return DB::transaction(function () use ($type, $severity, $outcome, $context): ApiSecurityEvent {
            $this->validateCatalogValue($type, $severity, $outcome);
            $attributes = $this->buildAttributes($type, $severity, $outcome, $context);
            $this->requireAggregateCorrelations($attributes);
            $event = ApiSecurityEvent::query()
                ->where('event_type', $type)
                ->where('severity', $severity)
                ->where('outcome', $outcome)
                ->where('surface', $attributes['surface'])
                ->where('ip_hash', $attributes['ip_hash'])
                ->where('login_identifier_hash', $attributes['login_identifier_hash'])
                ->where('user_agent_hash', $attributes['user_agent_hash'])
                ->where('correlation_key_version', $attributes['correlation_key_version'])
                ->lockForUpdate()
                ->first();

            if ($event === null) {
                $attributes['context'] = $this->aggregateContext($attributes['context'], 1, $attributes['occurred_at'], $attributes['request_id']);

                return $this->persist($attributes);
            }

            $aggregate = (array) (($event->context ?? [])['aggregate'] ?? []);
            $event->updateAggregate([
                'context' => $this->aggregateContext(
                    $event->context,
                    max(0, (int) ($aggregate['count'] ?? 0)) + 1,
                    $event->occurred_at,
                    $attributes['request_id'],
                    $attributes['occurred_at']
                ),
            ], SecurityEventMutationScope::aggregate());
            $event->refresh();
            $this->dispatch($event);

            return $event;
        });
    }

    /**
     * Build the persisted attributes from an allowlisted event context.
     *
     * @param  string  $type
     * @param  string  $severity
     * @param  string  $outcome
     * @param  array  $context
     *
     * @return array
     */
    protected function buildAttributes(string $type, string $severity, string $outcome, array $context): array
    {
        $allowed = array_intersect_key($context, array_flip(self::ALLOWED_FIELDS));
        $correlationVersion = null;

        foreach (['ip' => 'ip_hash', 'login_identifier' => 'login_identifier_hash', 'user_agent' => 'user_agent_hash'] as $input => $column) {
            if (!isset($allowed[$input]) || !is_string($allowed[$input]) || $allowed[$input] === '') {
                continue;
            }

            $allowed[$column] = match ($input) {
                'ip' => $this->correlationHasher->hashIp($allowed[$input]),
                'login_identifier' => $this->correlationHasher->hashLoginIdentifier($allowed[$input]),
                default => $this->correlationHasher->hashUserAgent($allowed[$input]),
            };
            $correlationVersion = $this->correlationHasher->keyVersion();
            unset($allowed[$input]);
        }

        return [
            'event_id' => (string) Str::uuid(),
            'occurred_at' => CarbonImmutable::now('UTC'),
            'event_type' => $type,
            'severity' => $severity,
            'outcome' => $outcome,
            'surface' => (string) ($allowed['surface'] ?? 'service'),
            'request_id' => $this->nullableString($allowed['request_id'] ?? null, 64),
            'run_id' => $this->nullableString($allowed['run_id'] ?? null, 64),
            'actor_type' => $this->nullableString($allowed['actor_type'] ?? null, 64),
            'actor_id' => $this->nullableInteger($allowed['actor_id'] ?? null),
            'target_type' => $this->nullableString($allowed['target_type'] ?? null, 64),
            'target_id' => $this->nullableInteger($allowed['target_id'] ?? null),
            'credential_type' => $this->nullableString($allowed['credential_type'] ?? null, 64),
            'credential_id' => $this->nullableString($allowed['credential_id'] ?? null, 64),
            'session_id' => $this->nullableString($allowed['session_id'] ?? null, 64),
            'website_ids' => $this->websiteIds($allowed['website_ids'] ?? []),
            'error_code' => $this->nullableString($allowed['error_code'] ?? null, 128),
            'http_status' => $this->nullableInteger($allowed['http_status'] ?? null),
            'ip_hash' => $allowed['ip_hash'] ?? null,
            'login_identifier_hash' => $allowed['login_identifier_hash'] ?? null,
            'user_agent_hash' => $allowed['user_agent_hash'] ?? null,
            'correlation_key_version' => $correlationVersion,
            'mutation_audit_id' => $this->nullableInteger($allowed['mutation_audit_id'] ?? null),
            'context' => $this->context($allowed['context'] ?? []),
        ];
    }

    /**
     * Validate stable catalog values before any mutation can commit.
     *
     * @param  string  $type
     * @param  string  $severity
     * @param  string  $outcome
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validateCatalogValue(string $type, string $severity, string $outcome): void
    {
        if (!in_array($type, self::EVENT_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported security event type.');
        }

        if (!in_array($severity, ['info', 'warning', 'critical'], true)) {
            throw new \InvalidArgumentException('Unsupported security event severity.');
        }

        if (!in_array($outcome, ['succeeded', 'denied', 'failed'], true)) {
            throw new \InvalidArgumentException('Unsupported security event outcome.');
        }
    }

    /**
     * Build the compact allowlisted context and preserve aggregate counters.
     *
     * @param  mixed  $context
     *
     * @return array|null
     */
    protected function context(mixed $context): ?array
    {
        if (!is_array($context)) {
            return null;
        }

        $allowed = $this->allowlistNested($context, self::ALLOWED_CONTEXT_SCHEMA);
        $redacted = $this->redactor->redact($allowed);

        return $redacted === [] ? null : $redacted;
    }

    /**
     * Normalize allowlisted website identifiers.
     *
     * @param  mixed  $websiteIds
     *
     * @return array|null
     */
    protected function websiteIds(mixed $websiteIds): ?array
    {
        if (!is_array($websiteIds)) {
            return null;
        }

        $ids = array_values(array_unique(array_filter(array_map(
            fn ($id): int => is_numeric($id) ? (int) $id : 0,
            $websiteIds
        ), fn (int $id): bool => $id > 0)));

        return $ids === [] ? null : $ids;
    }

    /**
     * Return a bounded nullable string.
     *
     * @param  mixed  $value
     * @param  int  $length
     *
     * @return string|null
     */
    protected function nullableString(mixed $value, int $length): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return Str::limit($value, $length, '');
    }

    /**
     * Return a positive nullable integer.
     *
     * @param  mixed  $value
     *
     * @return int|null
     */
    protected function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }

    /**
     * Persist one event and dispatch only its redacted observability payload.
     *
     * @param  array  $attributes
     *
     * @return \Wncms\Models\ApiSecurityEvent
     */
    protected function persist(array $attributes): ApiSecurityEvent
    {
        $event = ApiSecurityEvent::create($attributes);
        $this->dispatch($event);

        return $event;
    }

    /**
     * Dispatch the redacted Laravel event and structured log entry.
     *
     * @param  \Wncms\Models\ApiSecurityEvent  $event
     *
     * @return void
     */
    protected function dispatch(ApiSecurityEvent $event): void
    {
        $safePayload = $event->makeHidden(['ip_hash', 'login_identifier_hash', 'user_agent_hash']);

        Event::dispatch(new ApiSecurityEventRecorded($safePayload));
        Log::info('WNCMS security event recorded.', ['event' => $safePayload->toArray()]);
    }

    /**
     * Recursively keep only explicitly declared context keys.
     *
     * @param  array  $value
     * @param  array  $schema
     *
     * @return array
     */
    protected function allowlistNested(array $value, array $schema): array
    {
        $allowed = [];

        foreach ($schema as $key => $children) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            if ($children === true) {
                if (is_scalar($value[$key]) || $value[$key] === null) {
                    $allowed[$key] = $value[$key];
                }

                continue;
            }

            if (is_array($value[$key])) {
                $allowed[$key] = $this->allowlistNested($value[$key], $children);
            }
        }

        return $allowed;
    }

    /**
     * Reject aggregate requests without a complete correlation tuple.
     *
     * @param  array  $attributes
     *
     * @return void
     */
    protected function requireAggregateCorrelations(array $attributes): void
    {
        foreach (['ip_hash', 'login_identifier_hash', 'user_agent_hash', 'correlation_key_version'] as $field) {
            if (empty($attributes[$field])) {
                throw new \InvalidArgumentException('Security event aggregates require complete correlation fields.');
            }
        }
    }

    /**
     * Build allowlisted aggregate counter context.
     *
     * @param  array|null  $context
     * @param  int  $count
     * @param  \DateTimeInterface  $firstOccurredAt
     * @param  string|null  $requestId
     * @param  \DateTimeInterface|null  $lastOccurredAt
     *
     * @return array
     */
    protected function aggregateContext(?array $context, int $count, \DateTimeInterface $firstOccurredAt, ?string $requestId, ?\DateTimeInterface $lastOccurredAt = null): array
    {
        $context = $this->context($context ?? []) ?? [];
        $context['aggregate'] = [
            'count' => $count,
            'first_occurred_at' => CarbonImmutable::instance($firstOccurredAt)->toAtomString(),
            'last_occurred_at' => CarbonImmutable::instance($lastOccurredAt ?? $firstOccurredAt)->toAtomString(),
            'latest_request_id' => $requestId,
        ];

        return $context;
    }
}
