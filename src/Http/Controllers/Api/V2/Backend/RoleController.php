<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends ApiV2Controller
{
    protected function authorizeResourceAction(string $action): void
    {
        $permission = config("wncms-backend-api-v2.resources.roles.permissions.{$action}");
        if (!empty($permission)) {
            abort_unless(auth()->user()?->can($permission), Response::HTTP_FORBIDDEN);
        }
    }

    public function index(Request $request)
    {
        try {
            $this->authorizeResourceAction('index');

            $perPage = $this->normalizePerPage($request);
            $paginator = Role::query()
                ->withCount('permissions')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return $this->ok($paginator->items(), 'success', Response::HTTP_OK, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function show(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('show');

            $role = Role::query()->with('permissions:id,name')->find($id);
            if (!$role) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            return $this->ok($role);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->authorizeResourceAction('store');

            $roleName = (string) ($request->input('role_name') ?? $request->input('name') ?? '');
            $roleName = trim($roleName);
            if ($roleName === '') {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'role_name' => [__('validation.required', ['attribute' => 'role_name'])],
                ]);
            }

            if (Role::query()->where('name', $roleName)->exists()) {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'role_name' => [__('wncms::word.role_already_exist', ['role_name' => $roleName])],
                ]);
            }

            $role = Role::query()->create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissionIds = $this->normalizePermissionIds($request->input('permissions', []));
            if (!empty($permissionIds)) {
                $permissionNames = Permission::query()
                    ->whereIn('id', $permissionIds)
                    ->pluck('name')
                    ->toArray();
                $role->syncPermissions($permissionNames);
            }

            wncms()->cache()->tags(['roles', 'permissions'])->flush();

            return $this->ok($role->fresh('permissions:id,name'), 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function update(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('update');

            $role = Role::query()->find($id);
            if (!$role) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $roleName = (string) ($request->input('role_name') ?? $request->input('name') ?? $role->name);
            $roleName = trim($roleName);
            if ($roleName === '') {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'role_name' => [__('validation.required', ['attribute' => 'role_name'])],
                ]);
            }

            $exists = Role::query()
                ->where('name', $roleName)
                ->where('id', '!=', $role->id)
                ->exists();
            if ($exists) {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'role_name' => [__('wncms::word.role_already_exist', ['role_name' => $roleName])],
                ]);
            }

            $role->update(['name' => $roleName]);

            if ($request->has('permissions')) {
                $permissionIds = $this->normalizePermissionIds($request->input('permissions', []));
                $permissionNames = Permission::query()
                    ->whereIn('id', $permissionIds)
                    ->pluck('name')
                    ->toArray();
                $role->syncPermissions($permissionNames);
            }

            wncms()->cache()->tags(['roles', 'permissions'])->flush();

            return $this->ok($role->fresh('permissions:id,name'), 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function destroy(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('destroy');

            $role = Role::query()->find($id);
            if (!$role) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $role->delete();
            wncms()->cache()->tags(['roles', 'permissions'])->flush();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    protected function normalizePermissionIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(static fn($item) => (int) $item)
            ->filter(static fn($item) => $item > 0)
            ->values()
            ->all();
    }
}

