<?php

namespace Wncms\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

class FrontendAuth extends Authenticate
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('frontend.users.login');
        }
    }
}