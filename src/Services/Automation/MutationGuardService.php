<?php

namespace Wncms\Services\Automation;

use Illuminate\Database\Eloquent\Model;

class MutationGuardService
{
    public function __construct(protected AutomationActorResolver $actorResolver)
    {
    }

    /**
     * Preview actor, permission, and website scope checks for a mutation plan.
     *
     * @param array $plan
     * @param array $options
     * @return array
     */
    public function preview(array $plan, array $options = []): array
    {
        $writeMode = (bool) ($options['write_mode'] ?? false);
        $actorResult = $this->actorResolver->resolve($options, $writeMode);
        $permission = (string) ($plan['safety']['permission'] ?? '');
        $websiteIds = (array) ($plan['relationships']['website_ids'] ?? []);
        $actor = $actorResult['model'] ?? null;
        $websiteScopeCheck = $this->websiteScopeCheck($actor instanceof Model ? $actor : null, $websiteIds);

        if (($actorResult['status'] ?? 'fail') === 'fail') {
            $errors = (array) ($actorResult['errors'] ?? []);
            if (($websiteScopeCheck['status'] ?? null) === 'fail') {
                $errors['website_ids'] = $websiteScopeCheck['denied_ids'];
            }

            return $this->result($actorResult['code'] ?? 401, 'fail', $writeMode, $actorResult, [
                'name' => $permission,
                'status' => 'blocked',
            ], $websiteScopeCheck, $errors);
        }

        if (!$actor instanceof Model) {
            $websiteFailed = ($websiteScopeCheck['status'] ?? null) === 'fail';

            return $this->result($websiteFailed ? 422 : 200, $websiteFailed ? 'fail' : 'pass', $writeMode, $actorResult, [
                'name' => $permission,
                'status' => 'skipped',
            ], $websiteScopeCheck, $websiteFailed ? [
                'website_ids' => $websiteScopeCheck['denied_ids'],
            ] : []);
        }

        $permissionCheck = $this->permissionCheck($actor, $permission);
        $errors = [];

        if (($permissionCheck['status'] ?? null) === 'fail') {
            $errors['permission'][] = $permission;
        }

        if (($websiteScopeCheck['status'] ?? null) === 'fail') {
            $errors['website_ids'] = $websiteScopeCheck['denied_ids'];
        }

        $code = empty($errors)
            ? 200
            : (($websiteScopeCheck['status'] ?? null) === 'fail'
                ? (int) ($websiteScopeCheck['code'] ?? 403)
                : 403);

        return $this->result($code, empty($errors) ? 'pass' : 'fail', $writeMode, $actorResult, $permissionCheck, $websiteScopeCheck, $errors);
    }

    /**
     * Check actor permission.
     *
     * @param \Illuminate\Database\Eloquent\Model $actor
     * @param string $permission
     * @return array
     */
    protected function permissionCheck(Model $actor, string $permission): array
    {
        if ($permission === '') {
            return [
                'name' => null,
                'status' => 'skipped',
            ];
        }

        if (method_exists($actor, 'hasPermissionTo')) {
            $allowed = $actor->hasPermissionTo($permission);
        } else {
            $allowed = method_exists($actor, 'can') && $actor->can($permission);
        }

        return [
            'name' => $permission,
            'status' => $allowed ? 'pass' : 'fail',
        ];
    }

    /**
     * Check actor website scope.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $actor
     * @param array $websiteIds
     * @return array
     */
    protected function websiteScopeCheck(?Model $actor, array $websiteIds): array
    {
        $websiteIds = array_values(array_unique(array_map('intval', $websiteIds)));
        $websiteIds = array_values(array_filter($websiteIds, fn(int $id) => $id > 0));

        if (empty($websiteIds)) {
            return [
                'code' => 200,
                'requested_ids' => [],
                'status' => 'skipped',
                'denied_ids' => [],
                'missing_ids' => [],
            ];
        }

        $websiteClass = wncms()->getModelClass('website');
        $keyName = (new $websiteClass())->getKeyName();
        $existingIds = $websiteClass::query()
            ->whereKey($websiteIds)
            ->pluck($keyName)
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
        $missingIds = array_values(array_diff($websiteIds, $existingIds));

        if (!empty($missingIds)) {
            return [
                'code' => 422,
                'requested_ids' => $websiteIds,
                'status' => 'fail',
                'denied_ids' => $missingIds,
                'missing_ids' => $missingIds,
            ];
        }

        if (!$actor instanceof Model) {
            return [
                'code' => 200,
                'requested_ids' => $websiteIds,
                'status' => 'unverified',
                'denied_ids' => [],
                'missing_ids' => [],
            ];
        }

        if ($this->isAdminActor($actor)) {
            return [
                'code' => 200,
                'requested_ids' => $websiteIds,
                'status' => 'pass',
                'denied_ids' => [],
                'missing_ids' => [],
            ];
        }

        if (!method_exists($actor, 'websites')) {
            return [
                'code' => 403,
                'requested_ids' => $websiteIds,
                'status' => 'fail',
                'denied_ids' => $websiteIds,
                'missing_ids' => [],
            ];
        }

        $allowedIds = $actor->websites()
            ->pluck('websites.id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $deniedIds = array_values(array_diff($websiteIds, $allowedIds));

        return [
            'code' => empty($deniedIds) ? 200 : 403,
            'requested_ids' => $websiteIds,
            'status' => empty($deniedIds) ? 'pass' : 'fail',
            'denied_ids' => $deniedIds,
            'missing_ids' => [],
        ];
    }

    /**
     * Determine whether the actor can bypass website scope checks.
     *
     * @param \Illuminate\Database\Eloquent\Model $actor
     * @return bool
     */
    protected function isAdminActor(Model $actor): bool
    {
        return method_exists($actor, 'hasRole') && $actor->hasRole(['admin', 'superadmin']);
    }

    /**
     * Build a guard preview result.
     *
     * @param int $code
     * @param string $status
     * @param bool $writeMode
     * @param array $actorResult
     * @param array $permission
     * @param array $websiteScope
     * @param array $errors
     * @return array
     */
    protected function result(int $code, string $status, bool $writeMode, array $actorResult, array $permission, array $websiteScope, array $errors): array
    {
        return [
            'code' => $code,
            'status' => $status,
            'write_mode' => $writeMode,
            'actor' => $actorResult['actor'] ?? null,
            'actor_source' => $actorResult['source'] ?? 'none',
            'permission' => $permission,
            'website_scope' => $websiteScope,
            'errors' => $errors,
        ];
    }
}
