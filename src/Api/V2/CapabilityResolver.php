<?php

namespace Wncms\Api\V2;

use Illuminate\Contracts\Auth\Authenticatable;
use Wncms\Api\V2\Data\ApiOperationContract;

final class CapabilityResolver
{
    /**
     * Create the runtime capability resolver.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     */
    public function __construct(private readonly ApiContractRegistry $registry)
    {
    }

    /**
     * Resolve the installed API contract visible to an authenticated actor.
     *
     * Permission-protected operations are omitted when denied. Authorized
     * website-scoped operations remain visible with a stable disabled reason
     * when the current website context is unavailable.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array{schema_version: string, domains: array<string, array<string, mixed>>}
     */
    public function resolve(Authenticatable $user): array
    {
        $domains = [];
        $hasWebsiteContext = (bool) wncms()->website()->get();

        foreach ($this->registry->domains() as $key => $domain) {
            $domains[$key] = [
                'key' => $domain->key,
                'label' => $domain->label,
                'operations' => [],
            ];
        }

        foreach ($this->registry->operations() as $id => $operation) {
            if (! $this->isPermitted($user, $operation)) {
                continue;
            }

            $disabledReasons = [];
            if ($operation->websiteScoped && ! $hasWebsiteContext) {
                $disabledReasons[] = 'website.context_missing';
            }

            $domains[$operation->domain]['operations'][$id] = $this->operationData(
                $operation,
                $disabledReasons
            );
        }

        return [
            'schema_version' => (string) config('wncms-api-v2.schema_version', '2.0.0'),
            'domains' => $domains,
        ];
    }

    /**
     * Determine whether the authenticated actor may discover an operation.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return bool
     */
    private function isPermitted(Authenticatable $user, ApiOperationContract $operation): bool
    {
        if ($operation->permission === null || trim($operation->permission) === '') {
            return true;
        }

        return method_exists($user, 'can') && $user->can($operation->permission);
    }

    /**
     * Export one operation with its runtime availability state.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<int, string>  $disabledReasons
     * @return array<string, mixed>
     */
    private function operationData(ApiOperationContract $operation, array $disabledReasons): array
    {
        return [
            'method' => $operation->method,
            'path' => $operation->path,
            'permission' => $operation->permission,
            'ability' => $operation->ability,
            'website_scoped' => $operation->websiteScoped,
            'risk' => $operation->risk,
            'implementation' => $operation->implementation,
            'idempotent' => $operation->idempotent,
            'filters' => $operation->filters,
            'sorts' => $operation->sorts,
            'includes' => $operation->includes,
            'fields' => $operation->fields,
            'available' => $disabledReasons === [],
            'disabled_reasons' => $disabledReasons,
            'request_schema' => $operation->request->toArray(),
            'response_schema' => $operation->response->toArray(),
        ];
    }
}
