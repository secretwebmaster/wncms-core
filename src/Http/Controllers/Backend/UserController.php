<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class UserController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        Event::dispatch('wncms.backend.users.index.query.before', [$request, &$q]);
        $this->applyBackendListWebsiteScope($q);

        $q->with('roles');

        if (gss('multi_website')) {
            $q->with('websites');
        }

        if ($request->keyword) {
            $q->where(function ($query) use ($request) {
                $query->where('username', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->role) {
            $q->role($request->role);
        }

        $q->orderBy('id', 'desc');

        $users = $q->paginate(20);

        $roles = Role::all();

        if ($request->show_credits) {
            $creditTypes = wncms()->getModel('credit')::pluck('type')->unique()->toArray();
        }

        return $this->view('backend.users.index', [
            'page_title' => __('wncms::word.user_management'),
            'users' => $users,
            'roles' => $roles,
            'creditTypes' => $creditTypes ?? [],
        ]);
    }

    public function create($id = null)
    {
        //TODO clone a user
        // if ($id) {
        //     $user = $this->modelClass::find($id);
        //     if (!$user) {
        //         return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        //     }
        // } else {
        //     $user = new $this->modelClass;
        // }

        $roles = Role::all();
        $view = 'backend.users.create';
        $params = [
            'roles' => $roles,
            'page_title' => __('wncms::word.user_management')
        ];

        Event::dispatch('wncms.backend.users.create.resolve', [&$view, &$params]);

        return $this->view($view, $params);
    }

    public function store(Request $request)
    {
        $rules = [
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|between:6,20|same:password_confirmation',
            'password_confirmation' => 'required',
        ];
        $messages = [
            'username.required' => __('wncms::word.username_is_required'),
            'username.unique' => __('wncms::word.username_has_been_used'),
            'email.required' => __('wncms::word.email_is_required'),
            'email.email' => __('wncms::word.please_enter_a_valid_email'),
            'email.unique' => __('wncms::word.email_has_been_used'),
            'password.required' => __('wncms::word.password_is_required'),
            'password.same' => __('wncms::word.password_confirmation_is_not_the_same'),
            'password.between' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
            'password_confirmation.required' => __('wncms::word.password_confirmation_is_required'),
        ];

        Event::dispatch('wncms.backend.users.store.before', [$request, &$rules, &$messages]);
        $request->validate($rules, $messages);

        $attributes = [
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ];

        Event::dispatch('wncms.backend.users.store.attributes.before', [$request, &$attributes]);
        $user = $this->modelClass::create($attributes);
        Event::dispatch('wncms.backend.users.store.after', [$user, $request]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->withMessage(__('wncms::word.successfully_created'));
    }

    public function show($id)
    {
        dd('show user');
    }

    public function edit($id)
    {
        $user = $this->modelClass::find($id);
        if (!$user) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $roles = Role::all();

        $view = 'backend.users.edit';
        $params = [
            'user' => $user,
            'roles' => $roles,
            'page_title' => __('wncms::word.user_management')
        ];

        Event::dispatch('wncms.backend.users.edit.resolve', [&$view, &$params]);

        return $this->view($view, $params);
    }

    public function update(Request $request, $id)
    {
        $user = $this->modelClass::findOrFail($id);
        if (!$user) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $rules = [
            'username' => 'required|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|between:6,20|confirmed',
        ];
        $messages = [
            'username.required' => __('wncms::word.field_is_required', ['field' => __('wncms::word.username')]),
            'username.unique' => __('wncms::word.username_has_been_used'),
            'email.required' => __('wncms::word.field_is_required', ['field' => __('wncms::word.email')]),
            'email.email' => __('wncms::word.please_enter_a_valid_email'),
            'email.unique' => __('wncms::word.email_has_been_used'),
            'password.between' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
            'password.confirmed' => __('wncms::word.password_confirmation_is_not_the_same'),
            'password_confirmation.required' => __('wncms::word.password_confirmation_is_required'),
        ];

        Event::dispatch('wncms.backend.users.update.before', [$user, $request, &$rules, &$messages]);
        $request->validate($rules, $messages);

        $attributes = [
            'email' => $request->email,
            'username' => $request->username,
        ];

        Event::dispatch('wncms.backend.users.update.attributes.before', [$user, $request, &$attributes]);
        $user->update($attributes);

        if (!empty($request->password) && !empty($request->password_confirmation) && $request->password == $request->password_confirmation) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        if ($this->modelClass::role('admin')->count() == 1 && $user->hasRole('admin') && $request->role != 'admin') {
            return redirect()->back()->withErrors(['message' => __('wncms::word.cannot_change_role_of_last_admin')]);
        }
        $user->syncRoles($request->role);
        Event::dispatch('wncms.backend.users.update.after', [$user, $request]);

        return redirect()->route('users.edit', $user)->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated')
        ]);
    }

    public function destroy($id)
    {
        $user = $this->modelClass::findOrFail($id);

        if ($this->is_deleting_last_admin([$user])) {
            return back()->withErrors([
                'message' => __('wncms::word.cannot_delete_last_admin')
            ]);
        }

        $user->delete();

        return back()->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_deleted')
        ]);
    }

    public function bulk_delete(Request $request)
    {
        $ids = is_array($request->model_ids)
            ? $request->model_ids
            : explode(',', $request->model_ids);

        $users = $this->modelClass::whereIn('id', $ids)->get();

        if ($this->is_deleting_last_admin($users)) {
            return back()->withErrors([
                'message' => __('wncms::word.cannot_delete_last_admin')
            ]);
        }

        $deleted = 0;

        foreach ($users as $user) {
            $user->delete();
            $deleted++;
        }

        return $request->ajax()
            ? response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $deleted]),
            ])
            : back()->withMessage(
                __('wncms::word.successfully_deleted_count', ['count' => $deleted])
            );
    }

    protected function is_deleting_last_admin($users): bool
    {
        $users = collect($users);

        $adminRoles = ['superadmin', 'admin'];

        $adminsToDelete = $users->filter(
            fn($user) => $user->hasRole($adminRoles)
        )->count();

        $totalAdmins = $this->modelClass::role($adminRoles)->count();

        return $adminsToDelete >= $totalAdmins;
    }

    public function show_user_profile(Request $request)
    {
        $user = auth()->user();
        $view = 'backend.users.account.profile';
        $params = [
            'page_title' => __('wncms::word.my_account'),
            'user' => $user,
        ];

        Event::dispatch('wncms.backend.users.account.profile.resolve', [&$view, &$params]);

        return $this->view($view, $params);
    }

    public function update_user_profile(Request $request)
    {
        $user = auth()->user();
        $attributes = [
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
        ];

        Event::dispatch('wncms.backend.users.account.profile.update.before', [$user, $request, &$attributes]);
        $user->update($attributes);

        if ($request->filled('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        Event::dispatch('wncms.backend.users.account.profile.update.after', [$user, $request]);

        return redirect()
            ->route('users.account.profile.show')
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function show_user_security(Request $request)
    {
        return $this->view('backend.users.account.security', [
            'page_title' => __('wncms::word.my_account'),
        ]);
    }

    public function update_user_password(Request $request)
    {
        $user = auth()->user();
        Event::dispatch('wncms.backend.users.account.password.update.before', [$user, $request]);

        // dd($request->all());
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['message' => __('wncms::word.incorrect_password')]);
        }

        if ($request->new_password != $request->new_password_confirmation) {
            return back()->withErrors(['message' => __('wncms::word.password_confirmation_is_not_the_same')]);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        Event::dispatch('wncms.backend.users.account.password.update.after', [$user, $request]);

        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function show_user_api(Request $request)
    {
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subDays($i)->format('m-d');
        }

        // define types with min/max
        $types = [
            'words' => [500, 5000],
            'images' => [0, 50],
        ];

        $datasets = [];
        foreach ($types as $type => [$min, $max]) {
            $datasets[] = [
                'key' => $type,
                'label' => __('wncms::word.' . $type), // translated label
                'data' => array_map(fn() => rand($min, $max), $labels),
            ];
        }

        return $this->view('backend.users.account.api', [
            'page_title' => __('wncms::word.my_account'),
            'chartLabels' => $labels,
            'chartDatasets' => $datasets,
        ]);
    }

    public function update_user_api(Request $request)
    {
        return $this->view('backend.users.account.api', [
            'page_title' => __('wncms::word.my_account'),
        ]);
    }

    public function show_user_record(Request $request)
    {
        return $this->view('backend.users.record', [
            'page_title' => __('wncms::word.my_account'),
        ]);
    }

    public function update_user_email(Request $request)
    {
        $user = auth()->user();
        Event::dispatch('wncms.backend.users.account.email.update.before', [$user, $request]);

        // dd($request->all());
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if (Hash::check($request->password, $user->password)) {
            $user->update([
                'email' => $request->email
            ]);
        } else {
            return back()->withErrors(['message' => __('wncms::word.invalid_password')]);
        }

        Event::dispatch('wncms.backend.users.account.email.update.after', [$user, $request]);

        return redirect()->route('users.account.security.show');
    }
}
