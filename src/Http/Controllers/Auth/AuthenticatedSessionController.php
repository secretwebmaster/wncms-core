<?php

namespace Wncms\Http\Controllers\Auth;

use Wncms\Http\Controllers\Controller;
use Wncms\Http\Requests\Auth\LoginRequest;
use Wncms\Models\User;
use Wncms\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $website = wncms()->website()->get();
        $adminLoginView = "frontend.themes.{$website?->theme}.admin.login";
        if($website && view()->exists($adminLoginView)){
            return view($adminLoginView);
        }
        return view('wncms::auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Wncms\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function check(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        //return a redirect response the can be use in ajax or direct
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function ajax(LoginRequest $request)
    {
        try{
            $request->authenticate();
            $request->session()->regenerate();

            //return a redirect response the can be use in ajax or direct
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
                'redirect' => RouteServiceProvider::DASHBOARD,
            ]);
        }catch(\Exception $e){
            logger()->info("login failed: $request->email");
            // logger()->error($e);
            throw $e;
        }

    }

    /**
     * Handle an incoming api authentication request.
     *
     * @param  \Wncms\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function apiStore(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect']
            ]);
        }

        $user = User::where('email', $request->email)->first();
        return response($user);
    }

    /**
     * Verifies user token.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function apiVerifyToken(Request $request)
    {
        $request->validate([
            'api_token' => 'required'
        ]);

        $user = User::where('api_token', $request->api_token)->first();

        if(!$user){
            throw ValidationException::withMessages([
                'token' => ['Invalid token']
            ]);
        }
        return response($user);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function login_with_google()
    {
        if (request()->boolean('popup')) {
            session(['auth_google_popup_started' => true]);
        } else {
            session()->forget('auth_google_popup_started');
        }

        if (!$this->canUseGoogleLogin()) {
            return $this->redirectGoogleLoginError();
        }

        return Socialite::driver('google')->redirect();
    }

    public function login_with_google_callback()
    {
        if (session('settings_google_test_started')) {
            session()->forget('settings_google_test_started');

            try {
                $googleUser = Socialite::driver('google')->user();

                return redirect()
                    ->route('settings.index', ['tab' => 'social_login'])
                    ->with([
                        'message' => __('wncms::word.google_config_test_successful_for_email', ['email' => $googleUser->email ?: '-']),
                    ]);
            } catch (\Throwable $e) {
                logger()->error($e);

                return redirect()
                    ->route('settings.index', ['tab' => 'social_login'])
                    ->with([
                        'status' => 'fail',
                        'message' => __('wncms::word.google_config_test_failed_please_retry'),
                    ]);
            }
        }

        if (!$this->canUseGoogleLogin()) {
            return $this->redirectGoogleLoginError();
        }

        try {
            $data = Socialite::driver('google')->user();

            if (blank($data->email)) {
                return $this->redirectGoogleLoginError();
            }

            $user = User::where('email', $data->email)->first();

            if (!$user) {
                if (gss('disable_registration')) {
                    return redirect()->route('login')->withErrors(['message' => __('wncms::word.disable_registration')]);
                }

                $user = $this->createGoogleUser($data);
            }

            $this->syncUserToCurrentWebsite($user);

            $user->forceFill([
                'social_login_type' => 'google',
                'social_login_id' => (string) $data->id,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'last_login_at' => now(),
            ])->save();

            Auth::login($user);

            if (session('auth_google_popup_started')) {
                session()->forget('auth_google_popup_started');
                return $this->popupGoogleLoginResult('success', route('dashboard'));
            }

            return redirect()->route('dashboard');
        } catch (ValidationException $e) {
            if (session('auth_google_popup_started')) {
                session()->forget('auth_google_popup_started');
                return $this->popupGoogleLoginResult('fail', route('login'), collect($e->errors())->flatten()->first());
            }

            return redirect()->route('login')->withErrors($e->errors());
        } catch (\Throwable $e) {
            logger()->error($e);
            return $this->redirectGoogleLoginError();
        }
    }

    /**
     * Determine whether Google login is enabled and fully configured.
     *
     * @return bool
     */
    protected function canUseGoogleLogin(): bool
    {
        return gss('allow_google_login') && $this->hasGoogleLoginConfig();
    }

    /**
     * Check whether all required Google OAuth settings are present.
     *
     * @return bool
     */
    protected function hasGoogleLoginConfig(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    /**
     * Redirect back to the login page with a generic retry message.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectGoogleLoginError()
    {
        if (session('auth_google_popup_started')) {
            session()->forget('auth_google_popup_started');
            return $this->popupGoogleLoginResult('fail', route('login'), __('wncms::word.something_went_wrong_please_retry'));
        }

        return redirect()->route('login')->withErrors([
            'message' => __('wncms::word.something_went_wrong_please_retry'),
        ]);
    }

    /**
     * Render the Google popup result page that notifies the opener window.
     *
     * @param  string  $status
     * @param  string  $redirectUrl
     * @param  string|null  $message
     *
     * @return \Illuminate\View\View
     */
    protected function popupGoogleLoginResult(string $status, string $redirectUrl, ?string $message = null)
    {
        return view('wncms::auth.google-popup-result', [
            'status' => $status,
            'redirectUrl' => $redirectUrl,
            'message' => $message,
        ]);
    }

    /**
     * Create a new local user account from Google profile data.
     *
     * @param  object  $data
     *
     * @return \Wncms\Models\User
     */
    protected function createGoogleUser(object $data): User
    {
        $user = User::create([
            'username' => 'user_' . str_replace('.', '', microtime(true)) . Str::lower(Str::random(6)),
            'first_name' => data_get($data, 'user.given_name'),
            'last_name' => data_get($data, 'user.family_name'),
            'nickname' => $data->nickname ?? null,
            'email' => $data->email,
            'password' => Hash::make(Str::random(32)),
            'social_login_type' => 'google',
            'social_login_id' => (string) $data->id,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('member');
        $this->attachUserToCurrentWebsite($user);

        if (!empty($data->avatar)) {
            try {
                $user->addMediaFromUrl($data->avatar)->toMediaCollection('avatar');
            } catch (\Throwable $e) {
                logger()->warning('Unable to sync Google avatar: ' . $e->getMessage());
            }
        }

        return $user;
    }

    /**
     * Attach the user to the current website when one is active.
     *
     * @param  \Wncms\Models\User  $user
     *
     * @return void
     */
    protected function attachUserToCurrentWebsite(User $user): void
    {
        $website = wncms()->website()->getCurrent(false);

        if ($website && !$user->websites()->where('websites.id', $website->id)->exists()) {
            $user->websites()->syncWithoutDetaching([$website->id]);
        }
    }

    /**
     * Keep social login website access aligned with normal auth intent.
     *
     * @param  \Wncms\Models\User  $user
     *
     * @return void
     */
    protected function syncUserToCurrentWebsite(User $user): void
    {
        if ($user->hasRole(['admin', 'superadmin'])) {
            return;
        }

        $website = wncms()->website()->getCurrent(false);

        if (!$website) {
            return;
        }

        if ($user->websites()->where('websites.id', $website->id)->exists()) {
            return;
        }

        if (!$user->websites()->exists() || gss('allow_merge_account')) {
            $user->websites()->syncWithoutDetaching([$website->id]);
            return;
        }

        throw ValidationException::withMessages([
            'message' => __('wncms::word.email_has_already_been_used_on_other_websites'),
        ]);
    }
}
