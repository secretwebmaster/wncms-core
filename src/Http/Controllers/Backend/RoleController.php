<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()->get();
        return $this->view('backend.roles.index', [
            'page_title' => wncms_model_word('role', 'management'),
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return $this->view('backend.roles.create', [
            'page_title' => wncms_model_word('role', 'management'),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $existing = Role::where('name', $request->role_name)->first();
        if ($existing) {
            return back()->withErrors(['message' => __('wncms::word.role_already_exist', ['role_name' => $request->role_name])]);
        }
        $role = Role::create([
            'name' => $request->role_name,
        ]);

        wncms()->cache()->tags(['roles'])->flush();

        return redirect()->route('roles.edit', [
            'role' => $role,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return $this->view('backend.roles.edit', [
            'page_title' => wncms_model_word('role', 'management'),
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        // dd($request->all());
        $role->update([
            'name' => $request->role_name,
        ]);

        $permission_names = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();

        $role->syncPermissions($permission_names);

        wncms()->cache()->tags(['roles'])->flush();

        return redirect()->route('roles.edit', [
            'role' => $role,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Fetch view
     */
    public function view(string $name, array $options = [])
    {
        if (view()->exists($name)) {
            return view($name, $options);
        }

        $defaultView = 'wncms::' . $name;
        if (view()->exists($defaultView)) {
            return view($defaultView, $options);
        }

        abort(404, "View [{$name}] not found.");
    }
}
