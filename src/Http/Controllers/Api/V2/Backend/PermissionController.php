<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends ApiV2Controller
{
    /**
     * List global permissions with their assigned role count.
     */
    public function index(Request $request)
    {
        try {
            $this->authorizeResourceAction('index');
            $permissionClass = $this->permissionClass();
            $keyword = trim((string) $request->input('keyword', ''));
            $query = $permissionClass::query()->withCount('roles')->orderByDesc('id');
            if ($keyword !== '') {
                $query->where('name', 'like', '%'.$keyword.'%');
            }
            $paginator = $query->paginate($this->normalizePerPage($request));

            return $this->ok($paginator->items(), 'success', Response::HTTP_OK, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Show one global permission and its assigned roles.
     */
    public function show(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('show');
            $permission = $this->permissionClass()::query()->with('roles:id,name')->find($id);

            return $permission
                ? $this->ok($permission)
                : $this->error('model_not_found', Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Create one global permission.
     */
    public function store(Request $request)
    {
        try {
            $this->authorizeResourceAction('store');
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'guard_name' => ['nullable', 'string', 'max:255'],
            ]);
            $permissionClass = $this->permissionClass();
            $guard = trim((string) ($validated['guard_name'] ?? 'web')) ?: 'web';
            if ($permissionClass::query()->where('name', $validated['name'])->where('guard_name', $guard)->exists()) {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'name' => ['The permission name has already been taken.'],
                ]);
            }
            $permission = $permissionClass::query()->create([
                'name' => trim($validated['name']),
                'guard_name' => $guard,
            ]);
            $this->flushPermissionCache();

            return $this->ok($permission, 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Update one global permission.
     */
    public function update(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('update');
            $permissionClass = $this->permissionClass();
            $permission = $permissionClass::query()->find($id);
            if (! $permission) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'guard_name' => ['nullable', 'string', 'max:255'],
            ]);
            $guard = trim((string) ($validated['guard_name'] ?? $permission->guard_name)) ?: 'web';
            $duplicate = $permissionClass::query()
                ->where('name', trim($validated['name']))
                ->where('guard_name', $guard)
                ->whereKeyNot($permission->getKey())
                ->exists();
            if ($duplicate) {
                return $this->error('validation.failed', Response::HTTP_UNPROCESSABLE_ENTITY, [
                    'name' => ['The permission name has already been taken.'],
                ]);
            }
            $permission->update(['name' => trim($validated['name']), 'guard_name' => $guard]);
            $this->flushPermissionCache();

            return $this->ok($permission->fresh('roles:id,name'), 'successfully_updated');
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Delete one global permission.
     */
    public function destroy(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('destroy');
            $permission = $this->permissionClass()::query()->find($id);
            if (! $permission) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }
            $permission->delete();
            $this->flushPermissionCache();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Delete an explicit set of global permissions atomically.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $this->authorizeResourceAction('bulk_delete');
            $validated = $request->validate(['model_ids' => ['required', 'array', 'min:1'], 'model_ids.*' => ['integer', 'min:1']]);
            $ids = collect($validated['model_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $permissionClass = $this->permissionClass();
            $deleted = DB::connection((new $permissionClass)->getConnectionName())->transaction(function () use ($permissionClass, $ids): int {
                $permissions = $permissionClass::query()->whereKey($ids)->lockForUpdate()->get();
                if ($permissions->count() !== $ids->count()) {
                    abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Every requested permission must exist.');
                }

                return $permissionClass::query()->whereKey($ids)->delete();
            });
            $this->flushPermissionCache();

            return $this->ok(['deleted' => $deleted], 'successfully_deleted');
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Resolve the configured Spatie permission model.
     */
    protected function permissionClass(): string
    {
        return (string) config('permission.models.permission');
    }

    /**
     * Enforce the configured WNCMS action permission.
     */
    protected function authorizeResourceAction(string $action): void
    {
        $permission = config("wncms-backend-api-v2.resources.permissions.permissions.{$action}");
        if (! empty($permission)) {
            abort_unless(auth()->user()?->can($permission), Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Invalidate both WNCMS and Spatie permission caches.
     */
    protected function flushPermissionCache(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        wncms()->cache()->tags(['permissions', 'roles'])->flush();
    }
}
