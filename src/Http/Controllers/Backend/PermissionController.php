<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    protected $common_suffixes = [
        'list',
        'index',
        'show',
        'create',
        'clone',
        'bulk_create',
        'edit',
        'bulk_edit',
        'delete',
        'bulk_delete',
    ];


    public function index(Request $request)
    {
        $roles = Role::all();
        $q = Permission::query();
        
        if($request->keyword){
            $q->where('name', 'like', "%$request->keyword%");
        }
        
        if($request->role){
            $q->whereRelation('roles','name', $request->role);
        }

        $q->orderBy('name', 'asc');
        
        $permissions = $q->paginate($request->page_size ?? 50);

        return view('backend.permissions.index', [
            'page_title' => __('word.model_management', ['model_name' => __('word.permission')]),
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        return view('backend.permissions.create', [
            'page_title' => __('word.model_management', ['model_name' => __('word.permission')]),
            'roles' => $roles,
            'common_suffixes' => $this->common_suffixes,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate(
            [
                'name' => 'required',
            ],
            [
                'name.required' => __('word.field_is_required', ['field_name' => __('word.name')])
            ]
        );

        $role_names = Role::whereIn('id', $request->roless)->pluck('name')->toArray();

        if(!empty($request->common_suffixess)){

            foreach($request->common_suffixess as $common_suffix){
                $existing_permission = Permission::where('name', $request->name)->first();
                if($existing_permission){
                    return back()->withInput()->withErrors(['message' => __('word.permission_already_exists')]);
                }
            }

            foreach($request->common_suffixess as $common_suffix){
                $permission = Permission::firstOrCreate([
                    'name' => (str($request->name)->endsWith('_') ? $request->name : $request->name . "_") . $common_suffix,
                ]);
    
                $permission->syncRoles($role_names);
            }

        }else{

            $existing_permission = Permission::where('name', $request->name)->first();

            if($existing_permission){
                return back()->withInput()->withErrors(['message' => __('word.permission_already_exists')]);
            }

            $permission = Permission::firstOrCreate(['name' => $request->name]);

            $permission->syncRoles($role_names);

        }

        wncms()->cache()->tags(['permissions'])->flush();
        return redirect()->route('permissions.index');
        // return redirect()->route('permissions.edit', [
        //     'permission' => $permission,
        // ])->withMessage(__('word.successfully_created'));
    }

    public function edit(Permission $permission)
    {
        $roles = Role::all();
        return view('backend.permissions.edit', [
            'page_title' => __('word.model_management', ['model_name' => __('word.permission')]),
            'permission' => $permission,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        // dd($request->all());
        $permission->update([
            'name' => $request->name,
        ]);

        $role_names = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
        $permission->syncRoles($role_names);

        wncms()->cache()->tags(['permissions'])->flush();
        
        return redirect()->route('permissions.edit', [
            'permission' => $permission,
        ])->withMessage(__('word.successfully_updated'));
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->withMessage(__('word.successfully_deleted'));
    }

    public function bulk_assign_roles(Request $request)
    {
        // dd($request->all());
        if(empty($request->permission_ids) || empty($request->role_ids)) return back()->withErrors(['message' => __('word.empty_inputs')]);

        $role_ids = [];
        foreach($request->role_ids as $role_id => $value){
            $role_ids[] = $role_id;
        }
        $roles = Role::whereIn('id', $role_ids)->get();
        foreach($roles as $role){
            $role->permissions()->syncWithoutDetaching(explode(",", $request->permission_ids));
        }

        return back()->withMessage(__('word.successfully_updated'));

    }

    public function bulk_remove_roles(Request $request)
    {
        // dd($request->all());
        if(empty($request->permission_ids) || empty($request->role_ids)) return back()->withErrors(['message' => __('word.empty_inputs')]);

        $role_ids = [];
        foreach($request->role_ids as $role_id => $value){
            $role_ids[] = $role_id;
        }
        $roles = Role::whereIn('id', $role_ids)->get();
        foreach($roles as $role){
            $role->permissions()->detach(explode(",", $request->permission_ids));
        }

        return back()->withMessage(__('word.successfully_updated'));

    }
}
