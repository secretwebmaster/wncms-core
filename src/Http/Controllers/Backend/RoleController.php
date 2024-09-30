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
        return view('wncms::backend.roles.index', [
            'page_title' => __('word.model_management', ['model_name' => __('word.role')]),
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return view('wncms::backend.roles.create', [
            'page_title' => __('word.model_management', ['model_name' => __('word.role')]),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $existing = Role::where('name', $request->role_name)->first();
        if($existing){
            return back()->withErrors(['message' => __('word.role_already_exist', ['role_name' => $request->role_name])]);
        }
        $role = Role::create([
            'name' => $request->role_name,
        ]);

        wncms()->cache()->tags(['roles'])->flush();

        return redirect()->route('roles.edit', [
            'role' => $role,
        ])->withMessage(__('word.successfully_created'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('wncms::backend.roles.edit', [
            'page_title' => __('word.model_management', ['model_name' => __('word.role')]),
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
        ])->withMessage(__('word.successfully_updated'));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->withMessage(__('word.successfully_deleted'));
    }
}
