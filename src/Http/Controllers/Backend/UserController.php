<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

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

        if($request->show_credits){
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
        if ($id) {
            $user = $this->modelClass::find($id);
            if (!$user) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $user = new $this->modelClass;
        }

        $roles = Role::all();
        return $this->view('backend.users.create', [
            'roles' => $roles,
            'page_title' => __('wncms::word.user_management')
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate(
            [
                'username' => 'required',
                'email' => 'required|email',
                'password' => 'required|between:6,20|same:password_confirmation',
                'password_confirmation' => 'required',
            ],
            [
                'username.required' => __('wncms::word.username_is_required'),
                'email.required' => __('wncms::word.email_is_required'),
                'email.email' => __('wncms::word.please_enter_a_valid_email'),
                'password.required' => __('wncms::word.password_is_required'),
                'password.same' => __('wncms::word.password_confirmation_is_not_the_same'),
                'password.between' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
                'password_confirmation' => __('wncms::word.password_confirmation_is_required'),
            ]
        );

        $user = $this->modelClass::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

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

        return $this->view('backend.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'page_title' => __('wncms::word.user_management')
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->modelClass::findOrFail($id);
        if (!$user) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $request->validate(
            [
                'username' => 'required',
                'email' => 'required|email',
                'password' => 'nullable|between:6,20|confirmed'
            ],
            [
                'username.required' => __('wncms::word.field_is_required', ['field' => __('wncms::word.username')]),
                'email.required' => __('wncms::word.field_is_required', ['field' => __('wncms::word.email')]),
                'email.email' => __('wncms::word.please_enter_a_valid_email'),
                'password.required' => __('wncms::word.field_is_required', ['field' => __('wncms::word.password')]),
                'password.same' => __('wncms::word.password_confirmation_is_not_the_same'),
                'password_confirmation' => __('wncms::word.password_confirmation_is_required'),
            ]
        );

        $user->update([
            'email' => $request->email,
            'username' => $request->username,
        ]);

        if (!empty($request->password) && !empty($request->password_confirmation) && $request->password == $request->password_confirmation) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        if ($this->modelClass::role('admin')->count() == 1 && $user->hasRole('admin') && $request->role != 'admin') {
            return redirect()->back()->withErrors(['message' => __('wncms::word.cannot_change_role_of_last_admin')]);
        }
        // dd($request->all());
        $user->syncRoles($request->role);

        return redirect()->route('users.edit', $user)->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated')
        ]);
    }

    public function destroy($id)
    {
        $user = $this->modelClass::findOrFail($id);

        // Prevent deleting the last admin or superadmin
        if ($this->modelClass::role(['superadmin', 'admin'])->count() == 1 && $user->hasRole(['superadmin', 'admin'])) {
            return back()->withError('message', __('wncms::word.cannot_delete_last_admin'));
        }

        $user->delete();

        return redirect()->route('users.index')->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_deleted')
        ]);
    }

    public function show_user_profile(Request $request)
    {
        $user = auth()->user();
        return $this->view('backend.users.account.profile', [
            'page_title' => __('wncms::word.my_account'),
        ], [
            'user' => $user,
        ]);
    }

    public function update_user_profile(Request $request)
    {
        // dd($request->all());
        auth()->user()->update([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
        ]);

        if ($request->avatar) {
            auth()->user()->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        return redirect()->route('users.account.profile.show')->withMessage(__('wncms::word.successfully_updated'));
    }

    public function show_user_security(Request $request)
    {
        return $this->view('backend.users.account.security', [
            'page_title' => __('wncms::word.my_account'),
        ]);
    }

    public function update_user_password(Request $request)
    {
        // dd($request->all());
        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['message' => __('wncms::word.incorrect_password')]);
        }

        if ($request->new_password != $request->new_password_confirmation) {
            return back()->withErrors(['message' => __('wncms::word.password_confirmation_is_not_the_same')]);
        }

        auth()->user()->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function show_user_api(Request $request)
    {
        return $this->view('backend.users.account.api', [
            'page_title' => __('wncms::word.my_account'),
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
        // dd($request->all());
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if (Hash::check($request->password, auth()->user()->password)) {
            // dd(auth()->user());
            auth()->user()->update([
                'email' => $request->email
            ]);
        } else {
            return back()->withErrors(['message' => __('wncms::word.invalid_password')]);
        }

        return redirect()->route('users.account.security.show');
    }
}
