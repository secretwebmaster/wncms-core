<?php

namespace Wncms\Http\Controllers\Auth;

use Wncms\Http\Controllers\Controller;
use Wncms\Http\Requests\Auth\LoginRequest;
use Wncms\Models\User;
use Illuminate\Auth\Events\Registered;
use Wncms\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Socialite;

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
        if($website && view()->exists("frontend.theme.{$website?->theme}.users.login")){
            return view("frontend.theme.{$website?->theme}.users.login");
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
        return Socialite::driver('google')->redirect();
    }

    public function login_with_google_callback()
    {
        try{
            $data = Socialite::driver('google')->user();
            $user = User::where('email', $data->email)->first();

            if(!$user){
    
                $user = User::create([
                    'username' => $data->nickname ?? $data->name,
                    'first_name' => $data->user?->given_name ?? null,
                    'first_name' => $data->user?->family_name ?? null,
                    'email'      => $data->email,
                    'password'   => Hash::make(str()->random(16)),
                    'social_login_type' => 'google',
                    'social_login_id' => $data->id,
                    'email_verified_at' => now(),
                ]);
    
                if(!empty($data->avatar)){
                    $user->addMediaFromUrl($data->avatar)->toMediaCollection('avatar');
                }

                event(new Registered($user));

            }

            $user->update(['last_login_at' => now()]);
            Auth::login($user);
    
            return redirect()->route('dashboard');

        }catch(\Exception $e){
            logger()->error($e);
            return redirect()->route('register')->withErrors(['message' => __('wncms::word.something_went_wrong_please_retry')]);
        }
       

    }
}
