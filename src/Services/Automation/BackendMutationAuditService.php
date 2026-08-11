<?php

namespace Wncms\Services\Automation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Wncms\Models\MutationAudit;

class BackendMutationAuditService
{
    /**
     * Create a backend mutation audit adapter.
     *
     * @param  \Wncms\Services\Automation\MutationAuditService  $mutationAuditService
     */
    public function __construct(protected MutationAuditService $mutationAuditService)
    {
    }

    /**
     * Determine whether backend mutation audit persistence is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->mutationAuditService->enabled();
    }

    /**
     * Capture normalized model attributes and caller-supplied relationships.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $relationships
     * @return array
     */
    public function snapshot(Model $model, array $relationships = []): array
    {
        return [
            'attributes' => $model->attributesToArray(),
            'relationships' => $relationships,
        ];
    }

    /**
     * Persist a successful backend UI mutation from supplied snapshots.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $domain
     * @param  string  $action
     * @param  string  $permission
     * @param  array  $before
     * @param  array  $after
     * @param  array  $websiteIds
     * @param  array  $relationshipChanges
     * @param  string|null  $runId
     * @param  string  $message
     * @return \Wncms\Models\MutationAudit|null
     */
    public function write(
        Model $model,
        string $domain,
        string $action,
        string $permission,
        array $before,
        array $after,
        array $websiteIds = [],
        array $relationshipChanges = [],
        ?string $runId = null,
        string $message = 'Backend mutation completed.'
    ): ?MutationAudit {
        if (!$this->enabled()) {
            return null;
        }

        $actor = auth()->user();
        $modelKey = method_exists($model, 'getModelKey')
            ? (string) $model::getModelKey()
            : (string) Str::of(class_basename($model))->singular()->snake();
        $plan = [
            'operation' => $action,
            'model_key' => $modelKey,
            'target' => ['id' => (int) $model->getKey()],
            'attributes' => (array) ($after['attributes'] ?? []),
            'changes' => [
                'before' => $before,
                'after' => $after,
                'relationships' => $relationshipChanges,
            ],
            'relationships' => ['website_ids' => array_values($websiteIds)],
            'safety' => [
                'permission' => $permission,
                'write_mode' => 'guarded',
            ],
            'validation' => ['status' => 'pass', 'errors' => []],
            'guard' => [
                'status' => 'pass',
                'errors' => [],
                'actor' => [
                    'type' => $actor === null ? null : 'user',
                    'id' => $actor?->getKey(),
                ],
            ],
            'cache' => [],
            'hooks' => [],
            'dry_run' => false,
            'will_write' => true,
        ];
        $plan['audit'] = $this->mutationAuditService->previewFromPlan($plan, [
            'surface' => 'ui',
            'domain' => $domain,
            'run_id' => $runId ?? (string) Str::uuid(),
            'actor_type' => $actor === null ? null : 'user',
            'actor_id' => $actor?->getKey(),
        ]);

        return $this->mutationAuditService->writeFromPlan($plan, [
            'model_id' => (int) $model->getKey(),
            'result_code' => 200,
            'result_status' => 'success',
            'message' => $message,
        ]);
    }
}
