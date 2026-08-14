<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Contracts\Wildcard;
use Spatie\Permission\Guard;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Auth\Api\V2\AuthenticationContext;

final class TargetOperationAuthorizer
{
    public function __construct(private ModelPermissionResolver $modelPermissions) {}

    /**
     * Return every named connection used by authoritative permission resolution.
     *
     * @return array<int, string>
     */
    public function connectionNames(AuthenticationContext $context, ApiOperationContract $operation): array
    {
        $actor = $context->actor();
        if (! method_exists($actor, 'getConnection')) {
            throw new \RuntimeException('Actor authorization connection cannot be resolved.');
        }
        $connections = [$actor->getConnection()->getName()];
        if ($operation->permission === null) {
            return $connections;
        }
        if (! method_exists($actor, 'permissions') || ! method_exists($actor, 'roles')) {
            throw new \RuntimeException('Actor permission relations cannot be resolved.');
        }
        $permissions = $actor->permissions();
        $roles = $actor->roles();
        if (! $permissions instanceof BelongsToMany || ! $roles instanceof BelongsToMany) {
            throw new \RuntimeException('Actor permission relations are unsupported.');
        }
        $rolePermissions = $roles->getRelated()->permissions();
        if (! $rolePermissions instanceof BelongsToMany) {
            throw new \RuntimeException('Role permission relation is unsupported.');
        }

        return array_values(array_unique(array_merge($connections, [
            $permissions->getRelated()->getConnection()->getName(),
            $permissions->newPivotStatement()->getConnection()->getName(),
            $roles->getRelated()->getConnection()->getName(),
            $roles->newPivotStatement()->getConnection()->getName(),
            $rolePermissions->getRelated()->getConnection()->getName(),
            $rolePermissions->newPivotStatement()->getConnection()->getName(),
        ])));
    }

    /**
     * Authorize credential and ability without resolving target state.
     *
     * @throws \Wncms\Api\V2\Risk\RiskContextException
     */
    public function authorizePreTarget(AuthenticationContext $context, ApiOperationContract $operation): void
    {
        $this->authorizeCredentialAndAbility($context, $operation);
        if ($operation->permission === null) {
            return;
        }
        if ($operation->permissionMode !== 'static') {
            throw new RiskContextException('authorization.permission_denied', 403);
        }

        if (! $this->permissionGranted($context, $operation->permission)) {
            throw new RiskContextException('authorization.permission_denied', 403);
        }
    }

    /**
     * Authorize a fresh resolved operation, including trusted model-template permissions.
     */
    public function authorizeResolved(AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext): void
    {
        $this->authorizeCredentialAndAbility($context, $operation);
        if ($operation->permission === null) {
            return;
        }
        $permission = $operation->permission;
        if ($operation->permissionMode === 'model_template') {
            $suffix = str_starts_with($permission, '{model}_') ? substr($permission, 8) : '';
            $resolved = $suffix !== ''
                ? $this->modelPermissions->resolve($riskContext->normalizedInput['model'] ?? null, $suffix)
                : null;
            $permission = is_array($resolved) ? (string) ($resolved['permission'] ?? '') : '';
        } elseif ($operation->permissionMode !== 'static') {
            $permission = '';
        }
        if ($permission === '' || ! $this->permissionGranted($context, $permission)) {
            throw new RiskContextException('authorization.permission_denied', 403);
        }
    }

    /** Authorize immutable credential type and ability bindings. */
    private function authorizeCredentialAndAbility(AuthenticationContext $context, ApiOperationContract $operation): void
    {
        if (! in_array($context->credentialType(), $operation->acceptedCredentialTypes, true)) {
            throw new RiskContextException('risk.credential_type_denied', 403);
        }
        if ($operation->ability !== null && ! $context->hasAbility($operation->ability) && ! $context->hasAbility('*')) {
            throw new RiskContextException('authorization.ability_denied', 403);
        }
    }

    /**
     * Authorize one operation against a locked authoritative database snapshot.
     *
     * @throws \Wncms\Api\V2\Risk\RiskContextException
     */
    public function authorize(AuthenticationContext $context, ApiOperationContract $operation): void
    {
        $this->authorizePreTarget($context, $operation);
    }

    /**
     * Resolve direct and role permission grants without Spatie relation cache.
     */
    public function permissionGranted(AuthenticationContext $context, string $permission): bool
    {
        $actor = $context->actor();
        $freshActor = $context->actorId() !== null
            ? $actor->newQuery()->whereKey($context->actorId())->lockForUpdate()->first()
            : null;
        if ($freshActor === null || ! method_exists($freshActor, 'permissions') || ! method_exists($freshActor, 'roles')) {
            return false;
        }

        $permissions = $freshActor->permissions();
        $roles = $freshActor->roles();
        if (! $permissions instanceof BelongsToMany || ! $roles instanceof BelongsToMany) {
            return false;
        }
        $directIds = $permissions->newPivotQuery()
            ->orderBy($permissions->getRelatedPivotKeyName())
            ->lockForUpdate()
            ->pluck($permissions->getRelatedPivotKeyName())
            ->all();
        $roleIds = $roles->newPivotQuery()
            ->orderBy($roles->getRelatedPivotKeyName())
            ->lockForUpdate()
            ->pluck($roles->getRelatedPivotKeyName())
            ->all();

        $roleModel = $roles->getRelated();
        $roleKey = $roleModel->getKeyName();
        $roleRows = $roleIds === []
            ? collect()
            : $roles->getQuery()
                ->whereKey($roleIds)
                ->orderBy($roleModel->qualifyColumn($roleKey))
                ->lockForUpdate()
                ->get();
        $rolePermissionIds = [];
        if ($roleRows->isNotEmpty()) {
            $rolePermissions = $roleRows->first()->permissions();
            if (! $rolePermissions instanceof BelongsToMany) {
                return false;
            }
            $rolePermissionIds = $rolePermissions->newPivotStatement()
                ->whereIn($rolePermissions->getForeignPivotKeyName(), $roleRows->modelKeys())
                ->orderBy($rolePermissions->getForeignPivotKeyName())
                ->orderBy($rolePermissions->getRelatedPivotKeyName())
                ->lockForUpdate()
                ->pluck($rolePermissions->getRelatedPivotKeyName())
                ->all();
        }

        $permissionIds = array_values(array_unique(array_merge($directIds, $rolePermissionIds)));
        $permissionModel = $permissions->getRelated();
        $guardName = Guard::getDefaultName($freshActor);
        $permissionRows = $permissionIds === []
            ? collect()
            : $permissionModel->newQuery()
                ->whereKey($permissionIds)
                ->where('guard_name', $guardName)
                ->orderBy($permissionModel->getKeyName())
                ->lockForUpdate()
                ->get();

        if (! config('permission.enable_wildcard_permission')) {
            return $permissionRows->contains(static fn ($row): bool => (string) $row->name === $permission);
        }

        $permissionRowsById = $permissionRows->keyBy($permissionModel->getKeyName());
        $freshActor->setRelation('permissions', $permissionRowsById->only($directIds)->values());
        foreach ($roleRows as $roleRow) {
            $rolePermissionIdsForRow = $rolePermissions->newPivotStatement()
                ->where($rolePermissions->getForeignPivotKeyName(), $roleRow->getKey())
                ->pluck($rolePermissions->getRelatedPivotKeyName())
                ->all();
            $roleRow->setRelation('permissions', $permissionRowsById->only($rolePermissionIdsForRow)->values());
        }
        $freshActor->setRelation('roles', $roleRows);

        $wildcardClass = config('permission.wildcard_permission', \Spatie\Permission\WildcardPermission::class);
        if (! is_string($wildcardClass) || ! class_exists($wildcardClass) || ! is_subclass_of($wildcardClass, Wildcard::class)) {
            throw new \RuntimeException('Configured wildcard permission resolver is invalid.');
        }
        $wildcard = new $wildcardClass($freshActor);

        return $wildcard->implies($permission, $guardName, $wildcard->getIndex());
    }
}
