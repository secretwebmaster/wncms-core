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
        $themeView = "{$this->theme}::users.dashboard";
        $params = [];
        $defaultView = 'default::users.dashboard';

        // add event hook for plugins to modify dashboard view
        Event::dispatch('wncms.frontend.users.dashboard.resolve', [&$themeView, &$params, &$defaultView]);

        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Show the user login form.
     * 
     * @return \Illuminate\View\View
     */
    public function show_login()
    {
        $themeView = "{$this->theme}::users.login";
        $params = [];
        $defaultView = 'default::users.login';
        $loggedInRedirectRouteName = 'frontend.pages.home';

        // add event hook for plugins to modify login view
        Event::dispatch('wncms.frontend.users.login.resolve', [&$themeView, &$params, &$defaultView, &$loggedInRedirectRouteName]);

        if (auth()->check()) {
            return redirect()->route($loggedInRedirectRouteName);
        }

        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * handle the login form submission.
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'nullable|email|required_without:username',
            'username' => 'required_without:email',
            'password' => 'required',
        ];
        $messages = [
            'email.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.email')]),
            'email.email' => __('wncms::word.please_enter_a_valid_email'),
            'username.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.username')]),
            'password.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.password')]),
        ];

        Event::dispatch('wncms.frontend.users.login.before', [$request, &$rules, &$messages]);
        $request->validate($rules, $messages);

        // Determine which credential is provided
        $credentialKey = $request->filled('email') ? 'email' : 'username';
        $credentials = $request->only($credentialKey, 'password');
        // Perform authentication
        $user = $this->auth($credentials[$credentialKey], $credentials['password']);

        if ($user) {
            $redirectUrl = route('frontend.pages.home');

            // if user has intented url
            if ($request->has('intended')) {
                $redirectUrl = $request->intended;
            }

            // if theme has set rediret page after login
            if (gto('redirect_after_login') && !$request->has('intended')) {
                $redirectUrl = gto('redirect_after_login');
            }

            // if theme has dashboard page
            if (!gto('redirect_after_login') && !$request->has('intended') && view()->exists("{$this->theme}::users.dashboard")) {
                $redirectUrl = route('frontend.users.dashboard');
            }

            Event::dispatch('wncms.frontend.users.login.after', [$user, $request, &$redirectUrl]);

            return redirect($redirectUrl);
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
        $themeView = "{$this->theme}::users.register";
        $params = [];
        $defaultView = 'default::users.register';
        $disabledRegistrationRedirectRouteName = 'frontend.pages.home';

        // add event hook for plugins to modify registration view
        Event::dispatch('wncms.frontend.users.register.resolve', [&$themeView, &$params, &$defaultView, &$disabledRegistrationRedirectRouteName]);

        if (!$this->enabledRegistration()) {
            return redirect()->route($disabledRegistrationRedirectRouteName);
        }

        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Handle the registration form submission.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     * 
     * TODO: allow user either to register with email or username
     * TODO:: allow register via ajax request
     */
    public function register(Request $request)
    {
        // Pull configs at beginning
        $disabledRegistrationRedirectRouteName = 'frontend.pages.home';
        $sendWelcomeEmail = false;
        $defaultUserRoles = 'member';
        $redirectAfterRegister = null;
        $intendedUrl = session()->get('url.intended');
        
        // Add event hook for plugins to modify registration configs
        Event::dispatch('wncms.frontend.users.register.before', [
            &$disabledRegistrationRedirectRouteName,
            &$sendWelcomeEmail,
            &$defaultUserRoles,
            &$redirectAfterRegister,
            &$intendedUrl,
        ]);

        if (!$this->enabledRegistration()) {
            return redirect()->route($disabledRegistrationRedirectRouteName);
        }

        $request->validate(
            [
                'email' => 'nullable|email|required_without:username',
                'username' => 'required_without:email',
                'password' => 'required',
            ],
            [
                'email.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.email')]),
                'email.email' => __('wncms::word.please_enter_a_valid_email'),
                'username.required_without' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.username')]),
                'password.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.password')]),
            ]
        );

        // if username is not provided, use string before @ in email as username
        if (!$request->filled('username')) {
            $username = 'user_' . time() . rand(10, 99);
        } else {
            $username = $request->username;
        }

        // if email is not provided, use username plus current domain as email
        if (!$request->filled('email')) {
            $emailLocalPart = preg_replace('/[^a-zA-Z0-9._%+-]/', '', $username);
            if (empty($emailLocalPart)) {
                $emailLocalPart = 'user_' . time();
            }
            $email = $emailLocalPart . '@' . request()->getHost();
        } else {
            $email = $request->email;
        }

        $userModel = $this->getModelClass();

        // check if the user already exists
        $existingUser = $userModel::where('username', $username)->orWhere('email', $email)->first();
        if ($existingUser) {
            return redirect()->back()->withErrors(['message' => __('wncms::word.user_already_exists')]);
        }

        // Create user
        $user = $userModel::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'nickname' => $request->nickname,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($request->password),
        ]);

        // Add event hook for credit system (moved to package)
        Event::dispatch('wncms.frontend.users.register.credits.after', [$user]);

        // Assign default roles
        $defaultUserRoleOption = gto('default_user_roles', $defaultUserRoles);
        $roles = Role::whereIn('name', explode(',', $defaultUserRoleOption))->get();
        if ($roles->isEmpty()) {
            $roles = Role::where('name', 'member')->get();
        }
        $user->assignRole($roles);

        // Dispatch registered event
        Event::dispatch('wncms.frontend.users.register.after', [$user]);

        // Add event hook for welcome email
        Event::dispatch('wncms.frontend.users.register.welcome_email.after', [$user, $sendWelcomeEmail]);

        // Authenticate user
        $this->auth($username, $request->password);

        // Redirect priority: intended url > request intended > theme redirect > dashboard > home
        if ($intendedUrl) {
            return redirect($intendedUrl);
        }

        if ($request->filled('intended')) {
            return redirect($request->input('intended'));
        }

        if ($redirectAfterRegister) {
            return redirect($redirectAfterRegister);
        }

        if (gto('redirect_after_login')) {
            return redirect(gto('redirect_after_login'));
        }

        if (view()->exists("{$this->theme}::users.dashboard")) {
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
            Event::dispatch('wncms.frontend.users.auth.after', $user);
            return $user;
        }
    }

    /**
     * Logout the user.
     */
    public function logout()
    {
        // event before logout
        Event::dispatch('wncms.frontend.users.logout.before', auth()->user());

        auth()->logout();

        
        // if theme has logout page
        
        // else redirect to home

        // event after logout
        Event::dispatch('wncms.frontend.users.logout.after');

        return redirect()->route('frontend.pages.home');
    }

    /**
     * Show the user profile.
     */
    public function show_profile()
    {
        $themeView = "{$this->theme}::users.profile.show";
        $params = [
            'user' => auth()->user(),
        ];
        $defaultView = 'default::users.profile.show';

        Event::dispatch('wncms.frontend.users.profile.show.resolve', [&$themeView, &$params, &$defaultView]);
        
        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Show the edit profile form.
     */
    public function edit_profile()
    {
        $themeView = "{$this->theme}::users.profile.edit";
        $params = [
            'user' => auth()->user(),
        ];
        $defaultView = 'default::users.profile.edit';

        Event::dispatch('wncms.frontend.users.profile.edit.resolve', [&$themeView, &$params, &$defaultView]);
        
        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Handle the edit profile form submission.
     */
    public function update_profile(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'nickname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|max:20|confirmed',
        ];
        $messages = [
            'email.email' => __('wncms::word.email_is_invalid'),
            'email.unique' => __('wncms::word.email_is_already_taken'),
            'password.min' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
            'password.max' => __('wncms::word.password_length_should_between', ['min' => 6, 'max' => 20]),
            'password.confirmed' => __('wncms::word.password_confirmation_is_not_the_same'),
        ];

        // Validate the input
        $request->validate($rules, $messages);

        $attributes = [
            'nickname' => $request->nickname,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $attributes['password'] = bcrypt($request->password);
        }

        Event::dispatch('wncms.frontend.users.profile.update.before', [$user, $request, &$attributes]);
        $user->update($attributes);
        Event::dispatch('wncms.frontend.users.profile.update.after', [$user, $request]);

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
        $themeView = "{$this->theme}::users.password.forgot";
        $params = [];
        $defaultView = 'default::users.password.forgot';

        Event::dispatch('wncms.frontend.users.password.forgot.resolve', [&$themeView, &$params, &$defaultView]);

        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Handle the forgot password form submission.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle_password_forgot(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];
        $messages = [];

        Event::dispatch('wncms.frontend.users.password.forgot.before', [$request, &$rules, &$messages]);
        $request->validate($rules, $messages);

        $modelClass = $this->getModelClass();
        $user = $modelClass::where('email', $request->email)->first();

        if ($user) {
            
            $token = Password::createToken($user);
            $user->notify(new ResetPassword($token));

            $themeView = "{$this->theme}::users.password.sent";
            $params = ['email' => $request->email];
            $defaultView = 'default::users.password.sent';

            Event::dispatch('wncms.frontend.users.password.forgot.after', [$user, $request]);

            return $this->view($themeView, $params, $defaultView);
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
        $themeView = "{$this->theme}::users.password.reset";
        $params = [
            'token' => $request->token,
            'email' => $request->email,
        ];
        $defaultView = 'default::users.password.reset';

        Event::dispatch('wncms.frontend.users.password.reset.resolve', [&$themeView, &$params, &$defaultView]);
        
        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Handle the reset password form submission.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle_password_reset(Request $request)
    {
        $rules = [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ];
        $messages = [
            'token.required' => __('wncms::word.token.required'),
            'email.required' => __('wncms::word.email.required'),
            'email.email' => __('wncms::word.email.email'),
            'email.exists' => __('wncms::word.email.exists'),
            'password.required' => __('wncms::word.password.required'),
            'password.min' => __('wncms::word.password.min'),
            'password.confirmed' => __('wncms::word.password.confirmed'),
        ];

        Event::dispatch('wncms.frontend.users.password.reset.before', [$request, &$rules, &$messages]);
        $request->validate($rules, $messages);

        $resetUser = null;

        // Reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
                $resetUser = $user;
            }
        );

        Event::dispatch('wncms.frontend.users.password.reset.after', [$resetUser, $request, $status]);

        return $this->view(
            "{$this->theme}::users.password.completed",
            ['status' => $status],
            'wncms::frontend.themes.default.users.password.completed',
        );
    }

    /**
     * Show a custom user page based on URL segments.
     */
    public function page(Request $request)
    {
        // Get all segments after 'user'
        // Example: /user/custom/aaa/bbb → ['custom', 'aaa', 'bbb']
        $segments = array_slice(request()->segments(), 1);

        // Join into a single dot-notated path for Blade (custom.aaa.bbb)
        $page = implode('.', $segments);

        // Debug: see what Laravel resolves
        $themeView = "{$this->theme}::users.{$page}";
        $params = [];
        $defaultView = "default::users.{$page}";

        return $this->view($themeView, $params, $defaultView);
    }

    /**
     * Display posts by a specific user.
     */
    public function posts($username)
    {
        $modelClass = $this->getModelClass();
        $user = $modelClass::where('username', $username)->first();
        if (!$user) {
            return redirect()->route('frontend.pages.home');
        }

        $posts = wncms()->post()->getList([
            'user_id' => $user->id,
            'status' => 'published',
            'paginate' => 10,
        ]);

        $themeView = "{$this->theme}::users.posts";
        $params = [
            'posts' => $user ? $user->posts()->paginate(10) : collect([]),
            'user' => $user,
        ];
        $defaultView = 'default::users.posts';

        return $this->view($themeView, $params, $defaultView);
    }

    // check if registration enabled
    protected function enabledRegistration()
    {
        // system default: false = registration allowed? you said "false means no registration allowed"
        // so I'm assuming: disable_registration = true means DISABLE registration
        $systemDisabled = (bool) gss('disable_registration', true); // install default should be true to disable by default

        // theme override: null means "theme didn't provide this option"
        $themeDisabled = gto('disable_registration', null);

        return is_null($themeDisabled) ? !$systemDisabled : !(bool) $themeDisabled;
    }
}
