<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

class UserController extends FrontendController
{
    /**
     * Show the user dashboard.
     */
    public function dashboard()
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.dashboard",
            [],
            'wncms::frontend.theme.default.users.dashboard',
        );
    }

    /**
     * Show the user login form.
     * 
     * @return \Illuminate\View\View
     */
    public function show_login()
    {
        if (auth()->check()) {
            return redirect()->route('frontend.pages.home');
        }

        return $this->view(
            "frontend.theme.{$this->theme}.users.login",
            [],
            'wncms::frontend.theme.default.users.login',
        );
    }

    /**
     * handle the login form submission.
     */
    public function login(Request $request)
    {
        $request->validate(
            [
                'email' => 'required_without:username',
                'username' => 'required_without:email',
                'password' => 'required',
            ],
            [
                'email.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.email')]),
                'username.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.username')]),
                'password.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.password')]),
            ]
        );

        // Determine which credential is provided
        $credentialKey = $request->filled('email') ? 'email' : 'username';
        $credentials = $request->only($credentialKey, 'password');

        // Perform authentication
        $user = $this->auth($credentials[$credentialKey], $credentials['password']);

        if ($user) {
            // if user has intented url
            if ($request->has('intended')) {
                dd("intended");
                return redirect($request->intended);
            }

            // if theme has set rediret page after login
            if (gto('redirect_after_login')) {
                dd("redirect_after_login");
                return redirect(gto('redirect_after_login'));
            }

            // if theme has dashboard page
            if (view()->exists("frontend.theme.{$this->theme}.users.dashboard")) {
                return redirect()->route('frontend.users.dashboard');
            }

            return redirect()->route('frontend.pages.home');
        } else {
            return redirect()->back()->withErrors(['message' => __('wncms::word.invalid_credentials')]);
        }
    }

    /**
     * handle the login form submission.
     */
    public function login_ajax(Request $request)
    {
        $request->validate([
            'email' => 'required_without:username',
            'username' => 'required_without:email',
            'password' => 'required',
        ]);

        // Determine which credential is provided
        $credentialKey = $request->filled('email') ? 'email' : 'username';
        $credentials = $request->only($credentialKey, 'password');

        // Perform authentication
        $user = $this->auth($credentials[$credentialKey], $credentials['password']);

        if ($user) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error', 'message' => __('wncms::word.invalid_credentials')]);
        }
    }

    /**
     * Show the user registration form.
     * 
     * @return \Illuminate\View\View
     */
    public function show_register()
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.register",
            [],
            'wncms::frontend.theme.default.users.register',
        );
    }

    /**
     * Handle the registration form submission.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     * 
     * TODO: allow user either to register with email or username
     * TODO: allow admin to set if send welcome email
     */
    public function register(Request $request)
    {
        $request->validate(
            [
                'email' => 'required_without:username',
                'username' => 'required_without:email',
                'password' => 'required',
            ],
            [
                'email.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.email')]),
                'username.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.username')]),
                'password.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.password')]),
            ]
        );

        $sendWelcomeEmail = false;

        // if username is not provided, use string before @ in email as username
        if (!$request->filled('username')) {
            $username = 'user_' . time() . rand(10, 99);
        } else {
            $username = $request->username;
        }

        // if email is not provided, use username plus current domain as email
        if (!$request->filled('email')) {
            $email = $request->username . '@' . request()->getHttpHost();
        } else {
            $email = $request->email;
        }

        $userModel = $this->getModelClass();

        // check if the user already exists
        $existingUser = $userModel::where('username', $request->username)->orWhere('email', $request->email)->first();
        if ($existingUser) {
            return redirect()->back()->withErrors(['message' => __('wncms::word.user_already_exists')]);
        }

        // TODO: move the manager to make all registration process consistent
        $user = $userModel::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'nickname' => $request->nickname,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($request->password),
        ]);

        // set credits to 0
        $user->credits()->create(['type' => 'balance', 'amount' => 0]);
        $user->credits()->create(['type' => 'points', 'amount' => 0]);

        $defaultUserRoleOption = gto('default_user_roles', 'member');
        $defaultUserRoles = Role::whereIn('name', explode(',', $defaultUserRoleOption))->get();
        if ($defaultUserRoles->isEmpty()) {
            $defaultUserRoles = Role::where('name', 'member')->get();
        }
        $user->assignRole($defaultUserRoles);

        Event::dispatch('wncms.frontend.users.registered', $user);

        $this->auth($username, $request->password);

        // if user has intented url
        if ($request->has('intended')) {
            dd("intended");
            return redirect($request->intended);
        }

        // if theme has set rediret page after login
        if (gto('redirect_after_login')) {
            dd("redirect_after_login");
            return redirect(gto('redirect_after_login'));
        }

        // if theme has dashboard page
        if (view()->exists("frontend.theme.{$this->theme}.users.dashboard")) {
            return redirect()->route('frontend.users.dashboard');
        }

        return redirect()->route('frontend.pages.home');
    }

    /**
     * Authenticate the user.
     * 
     * @param string $username
     * @param string $password (From request)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function auth($username, $password)
    {
        $userModel = $this->getModelClass();
        $user = $userModel::where('username', $username)->orWhere('email', $username)->first();

        if ($user && Hash::check($password, $user->password)) {
            auth()->login($user);
            Event::dispatch('wncms.frontend.users.auth', $user);
            return $user;
        }
    }

    /**
     * Logout the user.
     */
    public function logout()
    {
        auth()->logout();

        // if theme has logout page

        // else redirect to home
        return redirect()->route('frontend.pages.home');
    }

    public function show_profile()
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.profile.show",
            [
                'user' => auth()->user(),
            ],
            'wncms::frontend.theme.default.users.profile.show',
        );
    }

    public function edit_profile()
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.profile.edit",
            [
                'user' => auth()->user(),
            ],
            'wncms::frontend.theme.default.users.profile.edit',
        );
    }

    public function update_profile(Request $request)
    {
        $user = auth()->user();

        // Validate the input
        $request->validate(
            [
                'nickname' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|min:6|max:20|confirmed',
            ],
            [
                'email.email' => __('wncms::word.email_is_invalid'),
                'email.unique' => __('wncms::word.email_is_already_taken'),
                'password.min' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
                'password.max' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
                'password.confirmed' => __('wncms::word.password_confirmation_is_not_the_same'),
            ]
        );

        // Update user details
        $user->nickname = $request->nickname;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()
            ->back()
            ->with('message', __('wncms::word.profile_updated_successfully'));

        // return redirect()
        //     ->route('frontend.users.profile')
        //     ->with('status', __('wncms::word.profile_updated_successfully'));
    }

    /**
     * Show the forgot password form.
     *
     * @return \Illuminate\View\View
     */
    public function show_password_forgot()
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.password.forgot",
            [],
            'wncms::frontend.theme.default.users.password.forgot',
        );
    }

    /**
     * Handle the forgot password form submission.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle_password_forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $modelClass = $this->getModelClass();
        $user = $modelClass::where('email', $request->email)->first();

        if ($user) {
            $token = Password::createToken($user);
            $user->notify(new ResetPassword($token));

            return $this->view(
                "frontend.theme.{$this->theme}.users.password.sent",
                ['email' => $request->email],
                'wncms::frontend.theme.default.users.password.sent',
            );
        }

        return back()->withErrors(['email' => __('wncms::word.reset_link_failed')]);
    }

    /**
     * Show the reset password form.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function show_password_reset(Request $request)
    {
        return $this->view(
            "frontend.theme.{$this->theme}.users.password.reset",
            [
                'token' => $request->token,
                'email' => $request->email,
            ],
            'wncms::frontend.theme.default.users.password.reset',
        );
    }

    /**
     * Handle the reset password form submission.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle_password_reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => __('wncms::word.token.required'),
            'email.required' => __('wncms::word.email.required'),
            'email.email' => __('wncms::word.email.email'),
            'email.exists' => __('wncms::word.email.exists'),
            'password.required' => __('wncms::word.password.required'),
            'password.min' => __('wncms::word.password.min'),
            'password.confirmed' => __('wncms::word.password.confirmed'),
        ]);

        // Reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        return $this->view(
            "frontend.theme.{$this->theme}.users.password.completed",
            ['status' => $status],
            'wncms::frontend.theme.default.users.password.completed',
        );
    }

    // other pages
    public function page()
    {
        // Get all segments after 'user'
        // Example: /user/custom/aaa/bbb → ['custom', 'aaa', 'bbb']
        $segments = array_slice(request()->segments(), 1);

        // Join into a single dot-notated path for Blade (custom.aaa.bbb)
        $page = implode('.', $segments);

        // Debug: see what Laravel resolves
        dd("frontend.theme.{$this->theme}.users.{$page}");

        return $this->view(
            "frontend.theme.{$this->theme}.users.{$page}",
            [],
            "frontend.theme.{$this->theme}.pages.home",
        );
    }
}
