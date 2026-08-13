<?php

namespace Wncms\Api\V2\Data;

final class ApiOperationContract
{
    /**
     * Create an API operation contract.
     *
     * @param  array<int, string>  $filters
     * @param  array<int, string>  $sorts
     * @param  array<int, string>  $includes
     * @param  array<int, string>  $fields
     * @param  array<int, string>  $acceptedCredentialTypes
     * @param  array<int, string>  $stepUpPurposes
     * @param  array<int, string>  $domainModelKeys
     * @param  array<int, string>  $transactionalOutboxModelKeys
     * @param  array<int, string>  $relationshipBoundaries
     * @return void
     */
    public function __construct(
        public readonly string $id,
        public readonly string $domain,
        public readonly string $surface,
        public readonly string $method,
        public readonly string $path,
        public readonly string $routeName,
        public readonly ?string $permission,
        public readonly ?string $ability,
        public readonly bool $websiteScoped,
        public readonly string $risk,
        public readonly string $implementation,
        public readonly ApiSchema $request,
        public readonly ApiSchema $response,
        public readonly array $filters = [],
        public readonly array $sorts = [],
        public readonly array $includes = [],
        public readonly array $fields = [],
        public readonly bool $idempotent = false,
        public readonly string $permissionMode = 'static',
        public readonly string $securityRisk = 'normal',
        public readonly array $acceptedCredentialTypes = ['interactive_access', 'service_token'],
        public readonly bool $requiresStepUp = false,
        public readonly array $stepUpPurposes = [],
        public readonly bool $actionPlanEligible = false,
        public readonly array $domainModelKeys = [],
        public readonly array $transactionalOutboxModelKeys = [],
        public readonly string $sideEffectKind = 'read',
        public readonly string $canonicalizer = 'schema',
        public readonly string $targetResolver = 'none',
        public readonly array $relationshipBoundaries = [],
    ) {}

    /**
     * Export the operation contract.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'surface' => $this->surface,
            'method' => $this->method,
            'path' => $this->path,
            'route_name' => $this->routeName,
            'permission' => $this->permission,
            'permission_mode' => $this->permissionMode,
            'ability' => $this->ability,
            'website_scoped' => $this->websiteScoped,
            'risk' => $this->risk,
            'implementation' => $this->implementation,
            'request_schema' => $this->request->jsonSerialize(),
            'response_schema' => $this->response->jsonSerialize(),
            'filters' => $this->filters,
            'sorts' => $this->sorts,
            'includes' => $this->includes,
            'fields' => $this->fields,
            'idempotent' => $this->idempotent,
            'security_risk' => $this->securityRisk,
            'accepted_credential_types' => $this->acceptedCredentialTypes,
            'requires_step_up' => $this->requiresStepUp,
            'step_up_purposes' => $this->stepUpPurposes,
            'action_plan_eligible' => $this->actionPlanEligible,
            'domain_model_keys' => $this->domainModelKeys,
            'transactional_outbox_model_keys' => $this->transactionalOutboxModelKeys,
            'side_effect_kind' => $this->sideEffectKind,
            'canonicalizer' => $this->canonicalizer,
            'target_resolver' => $this->targetResolver,
            'relationship_boundaries' => $this->relationshipBoundaries,
        ];
    }
}
