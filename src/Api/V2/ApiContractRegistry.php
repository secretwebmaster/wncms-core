<?php

namespace Wncms\Api\V2;

use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Exceptions\ApiContractException;

final class ApiContractRegistry
{
    private const MODEL_PERMISSION_TEMPLATES = [
        'backend.models.update' => '{model}_edit',
        'backend.models.bulk_delete' => '{model}_bulk_delete',
        'backend.models.bulk_force_delete' => '{model}_bulk_delete',
    ];

    private array $domains = [];

    private array $operations = [];

    /**
     * Register a domain contract.
     *
     * @param  \Wncms\Api\V2\Data\ApiDomainContract  $domain
     * @return void
     *
     * @throws \Wncms\Api\V2\Exceptions\ApiContractException
     */
    public function registerDomain(ApiDomainContract $domain): void
    {
        if (isset($this->domains[$domain->key])) {
            throw new ApiContractException("API domain '{$domain->key}' is already registered.");
        }

        $this->domains[$domain->key] = $domain;
    }

    /**
     * Register an operation contract.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     *
     * @throws \Wncms\Api\V2\Exceptions\ApiContractException
     */
    public function registerOperation(ApiOperationContract $operation): void
    {
        if (! isset($this->domains[$operation->domain])) {
            throw new ApiContractException("API domain '{$operation->domain}' is not registered.");
        }

        if (isset($this->operations[$operation->id])) {
            throw new ApiContractException("API operation '{$operation->id}' is already registered.");
        }

        $this->validatePermissionContract($operation);

        $this->operations[$operation->id] = $operation;
    }

    /**
     * Validate the permission mode and exact model-template operation mapping.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     *
     * @throws \Wncms\Api\V2\Exceptions\ApiContractException
     */
    private function validatePermissionContract(ApiOperationContract $operation): void
    {
        if (! in_array($operation->permissionMode, ['static', 'model_template'], true)) {
            throw new ApiContractException(
                "API operation '{$operation->id}' declares an unsupported permission mode."
            );
        }

        $expected = self::MODEL_PERMISSION_TEMPLATES[$operation->id] ?? null;
        if ($operation->permissionMode === 'static') {
            if ($expected !== null) {
                throw new ApiContractException(
                    "API operation '{$operation->id}' must use its prescribed model permission template."
                );
            }

            if ($operation->permission !== null && str_contains($operation->permission, '{model}')) {
                throw new ApiContractException(
                    "API operation '{$operation->id}' cannot use a model template as a static permission."
                );
            }

            return;
        }

        if ($expected === null || $operation->permission !== $expected) {
            throw new ApiContractException(
                "API operation '{$operation->id}' declares an unsupported model permission template."
            );
        }
    }

    /**
     * Return registered domains in stable identifier order.
     *
     * @return array<string, \Wncms\Api\V2\Data\ApiDomainContract>
     */
    public function domains(): array
    {
        $domains = $this->domains;
        ksort($domains);

        return $domains;
    }

    /**
     * Return registered operations in stable identifier order.
     *
     * @return array<string, \Wncms\Api\V2\Data\ApiOperationContract>
     */
    public function operations(): array
    {
        $operations = $this->operations;
        ksort($operations);

        return $operations;
    }

    /**
     * Find an operation by its stable identifier.
     *
     * @param  string  $id
     * @return \Wncms\Api\V2\Data\ApiOperationContract|null
     */
    public function operation(string $id): ?ApiOperationContract
    {
        return $this->operations[$id] ?? null;
    }

    /**
     * Export the complete registry as immutable arrays.
     *
     * @return array{domains: array<string, array{key: string, label: string}>, operations: array<string, array<string, mixed>>}
     */
    public function toArray(): array
    {
        $domains = [];
        foreach ($this->domains() as $key => $domain) {
            $domains[$key] = $domain->toArray();
        }

        $operations = [];
        foreach ($this->operations() as $id => $operation) {
            $operations[$id] = $operation->toArray();
        }

        return [
            'domains' => $domains,
            'operations' => $operations,
        ];
    }
}
