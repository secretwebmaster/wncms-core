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
        'security.auth_policy.changed',
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
     *
     * @return void
     */
    public function __construct(
        protected SecurityCorrelationHasher $correlationHasher,
        protected MutationAuditService $redactor
    ) {}

    /**
     * Record one allowlisted, redacted security event.
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
     *
     *
     * @throws \RuntimeException
     */
    public function withinTransaction(callable $mutation, array $event): mixed
    {
        if (! $this->correlationHasher->isConfigured()) {
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
     */
    public function recordAggregate(string $type, string $severity, string $outcome, array $context = []): ApiSecurityEvent
    {
        if (! $this->correlationHasher->isConfigured()) {
            throw new \RuntimeException('Security event correlation keys are unavailable.');
        }

        return DB::transaction(function () use ($type, $severity, $outcome, $context): ApiSecurityEvent {
            $this->validateCatalogValue($type, $severity, $outcome);
            $attributes = $this->buildAttributes($type, $severity, $outcome, $context);
            $this->requireAggregateCorrelations($attributes);
            $attributes['aggregate_key'] = $this->aggregateKey($attributes);
            $event = DB::table('api_security_events')
                ->where('aggregate_key', $attributes['aggregate_key'])
                ->lockForUpdate()
                ->first();

            if ($event === null) {
                $attributes['context'] = $this->aggregateContext($attributes['context'], 1, $attributes['occurred_at'], $attributes['request_id']);

                $inserted = DB::table('api_security_events')->insertOrIgnore($this->databaseAttributes($attributes));

                $event = DB::table('api_security_events')
                    ->where('aggregate_key', $attributes['aggregate_key'])
                    ->lockForUpdate()
                    ->first();

                if ($inserted === 1 && $event !== null) {
                    $created = ApiSecurityEvent::query()->where('aggregate_key', $attributes['aggregate_key'])->firstOrFail();
                    $this->dispatchAfterCommit($created);

                    return $created;
                }

                if ($event === null) {
                    throw new \RuntimeException('Security event aggregate could not be resolved after insert.');
                }
            }

            $existingContext = is_string($event->context) ? json_decode($event->context, true) : (array) $event->context;
            $aggregate = (array) ($existingContext['aggregate'] ?? []);
            $aggregateContext = $this->aggregateContext(
                $existingContext,
                max(0, (int) ($aggregate['count'] ?? 0)) + 1,
                new \DateTimeImmutable((string) $event->occurred_at),
                $attributes['request_id'],
                $attributes['occurred_at']
            );
            DB::table('api_security_events')->where('id', $event->id)->update([
                'context' => json_encode($aggregateContext, JSON_THROW_ON_ERROR),
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
            $updated = ApiSecurityEvent::query()->findOrFail($event->id);

            return $updated;
        });
    }

    /**
     * Build the persisted attributes from an allowlisted event context.
     */
    protected function buildAttributes(string $type, string $severity, string $outcome, array $context): array
    {
        $allowed = array_intersect_key($context, array_flip(self::ALLOWED_FIELDS));
        $correlationVersion = null;

        foreach (['ip' => 'ip_hash', 'login_identifier' => 'login_identifier_hash', 'user_agent' => 'user_agent_hash'] as $input => $column) {
            if (! isset($allowed[$input]) || ! is_string($allowed[$input]) || $allowed[$input] === '') {
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
     *
     *
     * @throws \InvalidArgumentException
     */
    protected function validateCatalogValue(string $type, string $severity, string $outcome): void
    {
        if (! in_array($type, self::EVENT_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported security event type.');
        }

        if (! in_array($severity, ['info', 'warning', 'critical'], true)) {
            throw new \InvalidArgumentException('Unsupported security event severity.');
        }

        if (! in_array($outcome, ['succeeded', 'denied', 'failed'], true)) {
            throw new \InvalidArgumentException('Unsupported security event outcome.');
        }
    }

    /**
     * Build the compact allowlisted context and preserve aggregate counters.
     */
    protected function context(mixed $context): ?array
    {
        if (! is_array($context)) {
            return null;
        }

        $allowed = $this->allowlistNested($context, self::ALLOWED_CONTEXT_SCHEMA);
        $redacted = $this->redactor->redact($allowed);

        return $redacted === [] ? null : $redacted;
    }

    /**
     * Normalize allowlisted website identifiers.
     */
    protected function websiteIds(mixed $websiteIds): ?array
    {
        if (! is_array($websiteIds)) {
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
     */
    protected function nullableString(mixed $value, int $length): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Str::limit($value, $length, '');
    }

    /**
     * Return a positive nullable integer.
     */
    protected function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }

    /**
     * Persist one event and dispatch only its redacted observability payload.
     */
    protected function persist(array $attributes): ApiSecurityEvent
    {
        $event = ApiSecurityEvent::create($attributes);
        $this->dispatchAfterCommit($event);

        return $event;
    }

    /**
     * Dispatch observability only after the outermost database transaction commits.
     *
     * The connection transaction manager executes immediately when no transaction is
     * active and discards the callback when any enclosing transaction rolls back.
     */
    protected function dispatchAfterCommit(ApiSecurityEvent $event): void
    {
        DB::connection()->afterCommit(function () use ($event): void {
            $this->dispatch($event);
        });
    }

    /**
     * Dispatch the redacted Laravel event and structured log entry.
     */
    protected function dispatch(ApiSecurityEvent $event): void
    {
        $safePayload = $event->makeHidden(['ip_hash', 'login_identifier_hash', 'user_agent_hash', 'aggregate_key']);

        Event::dispatch(new ApiSecurityEventRecorded($safePayload));
        Log::info('WNCMS security event recorded.', ['event' => $safePayload->toArray()]);
    }

    /**
     * Recursively keep only explicitly declared context keys.
     */
    protected function allowlistNested(array $value, array $schema): array
    {
        $allowed = [];

        foreach ($schema as $key => $children) {
            if (! array_key_exists($key, $value)) {
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

    /**
     * Build the stable database-enforced identity for one aggregate tuple.
     */
    protected function aggregateKey(array $attributes): string
    {
        return hash('sha256', implode("\n", [
            $attributes['event_type'],
            $attributes['severity'],
            $attributes['outcome'],
            $attributes['surface'],
            $attributes['ip_hash'],
            $attributes['login_identifier_hash'],
            $attributes['user_agent_hash'],
            $attributes['correlation_key_version'],
        ]));
    }

    /**
     * Serialize model attributes for the service-owned aggregate insert.
     */
    protected function databaseAttributes(array $attributes): array
    {
        foreach (['website_ids', 'context'] as $field) {
            if (is_array($attributes[$field] ?? null)) {
                $attributes[$field] = json_encode($attributes[$field], JSON_THROW_ON_ERROR);
            }
        }

        $attributes['created_at'] = CarbonImmutable::now('UTC');
        $attributes['updated_at'] = CarbonImmutable::now('UTC');

        return $attributes;
    }
}
