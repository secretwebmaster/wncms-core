<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Wncms\Facades\Wncms;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;

class UserController extends FrontendController
{
    /**
     * Get the model class that this controller works with.
     * Uses a setting from config/wncms.php and falls back to Post model if not set.
     */
    protected function getModelClass()
    {
        // Fetch the model class from the config file, or fall back to Post model
        return config('wncms.default_user_model', \Wncms\Models\User::class);
    }

    /**
     * Show the user dashboard.
     */
    public function dashboard()
    {
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.dashboard",
            params: [],
            fallback: 'wncms::frontend.theme.default.users.dashboard',
        );
    }

    /**
     * Show the user login form.
     * 
     * @return \Illuminate\View\View
     */
    public function show_login()
    {
        // check if aleady logged in
        if (auth()->check()) {
            return redirect()->route('frontend.pages.home');
        }

        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.login",
            params: [],
            fallback: 'wncms::frontend.theme.default.users.login',
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
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.register",
            params: [],
            fallback: 'wncms::frontend.theme.default.users.register',
        );
    }

    /**
     * Handle the registration form submission.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

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
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // set credits to 0
        $user->credits()->create(['type' => 'balance', 'amount' => 0]);
        $user->credits()->create(['type' => 'points', 'amount' => 0]);

        Event::dispatch('wncms.frontend.users.registered', $user);

        $this->auth($request->username, $request->password);

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
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.profile.show",
            params: [
                'user' => auth()->user(),
            ],
            fallback: 'wncms::frontend.theme.default.users.profile.show',
        );
    }

    public function edit_profile()
    {
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.profile.edit",
            params: [
                'user' => auth()->user(),
            ],
            fallback: 'wncms::frontend.theme.default.users.profile.edit',
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
                'password' => 'nullable|min:8|confirmed',
            ],
            [
                'email.email' => __('wncms::word.email_is_invalid'),
                'email.unique' => __('wncms::word.email_is_already_taken'),
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
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.password.forgot",
            params: [],
            fallback: 'wncms::frontend.theme.default.users.password.forgot',
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
        // dd($request->all());
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $modelClass = $this->getModelClass();
        $user = $modelClass::where('email', $request->email)->first();

        if ($user) {

            $token = Password::createToken($user);

            // Send the custom reset password notification
            $user->notify(new ResetPassword($token));
            return Wncms::view(
                name: "frontend.theme.{$this->theme}.users.password.sent",
                params: [
                    'email' => $request->email,
                ],
                fallback: 'wncms::frontend.theme.default.users.password.sent',
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
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.password.reset",
            params: [
                'token' => $request->token,
                'email' => $request->email,
            ],
            fallback: 'wncms::frontend.theme.default.users.password.reset',
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

        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.password.completed",
            params: [
                'status' => $status,
            ],
            fallback: 'wncms::frontend.theme.default.users.password.completed',
        );
    }

    /**
     * Show user subscriptions.
     */
    public function show_subscription()
    {
        $subscriptions = auth()->user()->subscriptions()->with(['plan', 'price'])->get();
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.users.subscriptions",
            params: [
                'subscriptions' => $subscriptions,
            ],
            fallback: 'wncms::frontend.theme.default.users.subscriptions',
        );
    }
}
