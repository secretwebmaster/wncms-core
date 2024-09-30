<?php

namespace Wncms\Http\Controllers\Auth;

use Wncms\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $token)
    {
        $website = wn('website')->get();
        if($website && view()->exists("frontend.theme.{$website?->theme}.auth.reset-password")){
            return view("wncms::frontend.theme.{$website?->theme}.auth.reset-password");
        }
        return view('wncms::auth.reset-password', compact('request', 'token'));
    }

    /**
     * Handle an incoming new password request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        info($request->all());
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ],
        [
            'password.min' => __('wncms::word.password_length_should_between', ['min' => 8, 'max' => 20]),
            'password.max' => __('wncms::word.password_length_should_between', ['min' => 8, 'max' => 20]),
            'password.confirmed' => __('wncms::word.password_confirmation_is_not_the_same'),
        ]
    );

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $result = $user->forceFill([
                    'password'       => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
                info("result");
                info($result);

                event(new PasswordReset($user));
            }
        );

        info($status);

        if($request->is_ajax){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'redirect' => route('login'),
            ]);
        }

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
