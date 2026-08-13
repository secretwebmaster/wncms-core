<?php

namespace Wncms\Services\Automation;

use Illuminate\Support\Str;
use Wncms\Models\MutationAudit;

class MutationAuditService
{
    /**
     * Determine whether mutation audit persistence is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool) config('wncms.mutation_audit.enabled', false);
    }

    /**
     * Build the audit payload that a dry-run mutation would write later.
     *
     * @param array $plan
     * @param array $meta
     * @return array
     */
    public function previewFromPlan(array $plan, array $meta = []): array
    {
        $validation = (array) ($plan['validation'] ?? []);
        $validationErrors = (array) ($validation['errors'] ?? []);
        $guard = (array) ($plan['guard'] ?? []);
        $guardErrors = (array) ($guard['errors'] ?? []);
        $isSuccess = empty($validationErrors) && (($guard['status'] ?? 'pass') !== 'fail');
        $modelKey = (string) ($plan['model_key'] ?? '');
        $target = (array) ($plan['target'] ?? []);
        $resultCode = $isSuccess ? 202 : $this->failureCode($guard, $validationErrors);
        $message = $isSuccess
            ? ((bool) ($plan['dry_run'] ?? true) ? 'Mutation dry-run plan generated.' : 'Mutation plan generated.')
            : 'Mutation validation or guard check failed.';

        return [
            'enabled' => $this->enabled(),
            'will_write' => $this->enabled()
                && !(bool) ($plan['dry_run'] ?? true)
                && (bool) ($plan['will_write'] ?? false)
                && $isSuccess,
            'table' => (new MutationAudit())->getTable(),
            'attributes' => [
                'run_id' => (string) ($meta['run_id'] ?? Str::uuid()),
                'surface' => (string) ($meta['surface'] ?? 'service'),
                'actor_type' => $meta['actor_type'] ?? null,
                'actor_id' => isset($meta['actor_id']) ? (int) $meta['actor_id'] : null,
                'domain' => (string) ($meta['domain'] ?? Str::plural($modelKey ?: 'model')),
                'action' => (string) ($plan['operation'] ?? ''),
                'model_key' => $modelKey === '' ? null : $modelKey,
                'model_type' => $this->modelClass($modelKey),
                'model_id' => isset($target['id']) ? (int) $target['id'] : null,
                'website_ids' => (array) ($plan['relationships']['website_ids'] ?? []),
                'permission' => $plan['safety']['permission'] ?? null,
                'input_summary' => $this->inputSummary($plan),
                'result_code' => $resultCode,
                'result_status' => $isSuccess ? 'success' : 'fail',
                'message' => $message,
                'error_summary' => array_filter([
                    'validation' => $validationErrors,
                    'guard' => $guardErrors,
                ]),
                'context' => $this->contextSummary($plan, $meta),
            ],
        ];
    }

    /**
     * Persist an audit record from a mutation plan.
     *
     * @param array $plan
     * @param array $overrides
     * @return \Wncms\Models\MutationAudit|null
     */
    public function writeFromPlan(array $plan, array $overrides = []): ?MutationAudit
    {
        if (!$this->enabled()) {
            return null;
        }

        $attributes = (array) ($plan['audit']['attributes'] ?? $this->previewFromPlan($plan)['attributes']);

        return MutationAudit::create($this->redact(array_merge($attributes, $overrides)));
    }

    /**
     * Build stable audit response metadata.
     *
     * @param  \Wncms\Models\MutationAudit|null  $audit
     * @return array{enabled: bool, id: int|null}
     */
    public function reference(?MutationAudit $audit = null): array
    {
        return [
            'enabled' => $this->enabled(),
            'id' => $audit === null ? null : (int) $audit->getKey(),
        ];
    }

    /**
     * Build a redacted mutation input summary for audit storage.
     *
     * @param array $plan
     * @return array
     */
    protected function inputSummary(array $plan): array
    {
        return $this->redact([
            'dry_run' => (bool) ($plan['dry_run'] ?? true),
            'will_write' => (bool) ($plan['will_write'] ?? false),
            'target' => (array) ($plan['target'] ?? []),
            'attributes' => (array) ($plan['attributes'] ?? []),
            'changes' => (array) ($plan['changes'] ?? []),
            'relationships' => (array) ($plan['relationships'] ?? []),
        ]);
    }

    /**
     * Build a compact audit context payload.
     *
     * @param array $plan
     * @param array $meta
     * @return array
     */
    protected function contextSummary(array $plan, array $meta): array
    {
        return [
            'meta' => $this->redact($meta),
            'cache' => (array) ($plan['cache'] ?? []),
            'hooks' => (array) ($plan['hooks'] ?? []),
            'notes' => (array) ($plan['notes'] ?? []),
            'guard' => (array) ($plan['guard'] ?? []),
            'write_mode' => $plan['safety']['write_mode'] ?? null,
        ];
    }

    /**
     * Resolve the failure code for an audit preview.
     *
     * @param array $guard
     * @param array $validationErrors
     * @return int
     */
    protected function failureCode(array $guard, array $validationErrors): int
    {
        if (($guard['status'] ?? 'pass') === 'fail') {
            return (int) ($guard['code'] ?? 403);
        }

        return empty($validationErrors) ? 400 : 422;
    }

    /**
     * Resolve a WNCMS model class from a model key.
     *
     * @param string $modelKey
     * @return string|null
     */
    protected function modelClass(string $modelKey): ?string
    {
        if ($modelKey === '') {
            return null;
        }

        try {
            return wncms()->getModelClass($modelKey);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Redact sensitive values from nested audit payloads.
     *
     * @param mixed $value
     * @param string|null $key
     * @return mixed
     */
    public function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return '[redacted]';
        }

        if (!is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $childKey => $childValue) {
            $redacted[$childKey] = $this->redact($childValue, is_string($childKey) ? $childKey : null);
        }

        return $redacted;
    }

    /**
     * Determine whether an audit key should be redacted.
     *
     * @param string $key
     * @return bool
     */
    protected function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach (['password', 'token', 'secret', 'proof', 'confirmation', 'authorization', 'cookie', 'csrf', 'api_key', 'api-key'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
