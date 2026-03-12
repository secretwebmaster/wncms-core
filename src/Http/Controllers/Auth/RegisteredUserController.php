<?php

namespace Wncms\Http\Controllers\Auth;

use Wncms\Http\Controllers\Controller;
use Wncms\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Abort the request when registration is disabled.
     *
     * Stops registration-related actions before the controller continues with
     * view rendering or account creation.
     *
     * @return void
     */
    protected function abortIfRegistrationDisabled(): void
    {
        if (gss('disable_registration')) {
            abort(403, __('wncms::word.disable_registration'));
        }
    }

    /**
     * Resolve the configured user model instance.
     *
     * Uses the configured `wncms.default_user_model` class and returns a new
     * model instance for registration operations.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getUserClass()
    {
        $model = config('wncms.default_user_model', \Wncms\Models\User::class);
        return new $model;
    }
    
    /**
     * Display the registration view.
     *
     * Resolves the active website theme first and falls back to the default
     * WNCMS registration view when no theme override exists.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->abortIfRegistrationDisabled();

        $website = wncms()->website()->get();
        if($website && view()->exists("frontend.themes.{$website?->theme}.auth.register")){
            return view("wncms::frontend.themes.{$website?->theme}.auth.register");
        }
        return view('wncms::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Validates the request, creates the user, assigns the default role,
     * attaches the current website, and logs the user in.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $this->abortIfRegistrationDisabled();

        // info($request->all());

        $request->validate(
            [
                // 'first_name' => 'required|string|max:255',
                // 'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            [
                'email.unique' => __('wncms::word.email_has_been_used'),
                'email.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.email')]),
                'password.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.password')]),
                'password.confirmed' => __('wncms::word.password_confirmation_is_not_the_same'),
                'password.min' => __('wncms::word.password_should_has_at_least_8_characters'),
            ]
        );

        $userModel = $this->getUserClass();

        $user = $userModel::create([
            // 'first_name' => $request->first_name,
            // 'last_name' => $request->last_name,
            'username' => "user_" . str_replace(".", "", microtime(true)),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('member');
        $website = wncms()->website()->getCurrent(false);
        $user->websites()->attach($website->id);
        if(gss('enable_smtp') && gss('send_user_welcom_email')){
            event(new Registered($user));
        }

        Auth::login($user);

        if($request->is_ajax){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
                'redirect' => RouteServiceProvider::DASHBOARD,
            ]);
        }

        return redirect()->route('dashboard');

        // return redirect(RouteServiceProvider::HOME);
    }

    /**
     * Handle an incoming api registration request.
     *
     * Validates the API payload and returns the created user model response
     * with a newly generated API token hash.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function apiStore(Request $request)
    {
        $this->abortIfRegistrationDisabled();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $userModel = $this->getUserClass();

        $token = Str::random(60);
        $user = $userModel::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'api_token' => hash('sha256', $token),
        ]);

        return response($user);
    }
}
