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

        if ($request->keyword) {
            $q->where('name', 'like', "%$request->keyword%");
        }

        if ($request->role) {
            $q->whereRelation('roles', 'name', $request->role);
        }

        $q->orderBy('name', 'asc');

        $permissions = $q->paginate($request->page_size ?? 50);

        return $this->view('backend.permissions.index', [
            'page_title' => wncms_model_word('permission', 'management'),
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        return $this->view('backend.permissions.create', [
            'page_title' => wncms_model_word('permission', 'management'),
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
                'name.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.name')])
            ]
        );

        $role_names = Role::whereIn('id', $request->roless)->pluck('name')->toArray();

        if (!empty($request->common_suffixess)) {

            foreach ($request->common_suffixess as $common_suffix) {
                $existing_permission = Permission::where('name', $request->name)->first();
                if ($existing_permission) {
                    // return back()->withInput()->withErrors(['message' => __('wncms::word.permission_already_exists')]);
                    continue;
                } else {
                    $permission = Permission::firstOrCreate([
                        'name' => (str($request->name)->endsWith('_') ? $request->name : $request->name . "_") . $common_suffix,
                    ]);

                    $permission->syncRoles($role_names);
                }
            }
        } else {

            $existing_permission = Permission::where('name', $request->name)->first();

            if ($existing_permission) {
                return back()->withInput()->withErrors(['message' => __('wncms::word.permission_already_exists')]);
            }

            $permission = Permission::firstOrCreate(['name' => $request->name]);

            $permission->syncRoles($role_names);
        }

        wncms()->cache()->tags(['permissions'])->flush();
        return redirect()->route('permissions.index');
    }

    public function edit(Permission $permission)
    {
        $roles = Role::all();
        return $this->view('backend.permissions.edit', [
            'page_title' => wncms_model_word('permission', 'management'),
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
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return back()->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_assign_roles(Request $request)
    {
        // dd($request->all());
        if (empty($request->permission_ids) || empty($request->role_ids)) return back()->withErrors(['message' => __('wncms::word.empty_inputs')]);

        $role_ids = [];
        foreach ($request->role_ids as $role_id => $value) {
            $role_ids[] = $role_id;
        }
        $roles = Role::whereIn('id', $role_ids)->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching(explode(",", $request->permission_ids));
        }

        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function bulk_remove_roles(Request $request)
    {
        // dd($request->all());
        if (empty($request->permission_ids) || empty($request->role_ids)) return back()->withErrors(['message' => __('wncms::word.empty_inputs')]);

        $role_ids = [];
        foreach ($request->role_ids as $role_id => $value) {
            $role_ids[] = $role_id;
        }
        $roles = Role::whereIn('id', $role_ids)->get();
        foreach ($roles as $role) {
            $role->permissions()->detach(explode(",", $request->permission_ids));
        }

        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function bulk_delete(Request $request)
    {
        if (!is_array($request->model_ids)) {
            $modelIds = explode(",", $request->model_ids);
        } else {
            $modelIds = $request->model_ids;
        }

        $count = Permission::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('clicks.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
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
