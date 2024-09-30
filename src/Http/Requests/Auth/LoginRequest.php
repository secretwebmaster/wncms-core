<?php

namespace Wncms\Http\Requests\Auth;

use Wncms\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // info($this);
        return [
            'email' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Custom Message
     */
    public function messages()
    {
        return [
            'email.reuired' => __('word.field_is_required', ['field_name' => __('word.email')]),
            'email.string' => __('word.field_should_be_string', ['field_name' => __('word.email')]),
            'password.required' => __('word.field_is_required', ['field_name' => __('word.password')]),
            'password.string' => __('word.field_should_be_string', ['field_name' => __('word.password')]),
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $requestingUser = User::where('email', $this->email)->first();

        //找不到會員
        if(!$requestingUser){
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
                'password' => __('auth.failed'),
            ]);
        }

        //檢查是否管理員嘗試登入
        $isAdmin = $requestingUser->hasRole(['admin', 'superadmin']);

        if(!$isAdmin){

            //獲取當前網站ID
            $currentWebsiteId = wncms()->website()->getCurrent()?->id;

            //未創建網站時，只有管理員可以登入
            if(!$currentWebsiteId){
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                    'password' => __('auth.failed'),
                ]);
            }

            //已建立網站，檢查用戶可否管理當前網站

            //用戶沒有權限
            if(!$requestingUser->websites()->where('websites.id', $currentWebsiteId)->exists()){

                //但找到其他網站中，使用了相同的Email註冊
                if($requestingUser->websites()->exists()){

                    //如果允許同步帳號
                    if(gss('allow_merge_account')){
                        throw ValidationException::withMessages([
                            'email' => __('word.found_account_on_other_websites_of_this_group_apply_merging'),
                        ]);
                    }

                    //不可同步，提示Email已於其他網迴註冊
                    throw ValidationException::withMessages([
                        'email' => __('word.email_has_already_been_used_on_other_websites'),
                    ]);

                }
    
            }

        }

        //是管理員，或者有管理網站的權限
        if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        //成功登入，清除次數限制
        RateLimiter::clear($this->throttleKey());

        auth()->user()->update(['last_login_at' => now()]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('email')).'|'.$this->ip();
    }
}
