<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = function_exists('wncms_is_installed')
            ? wncms_is_installed()
            : file_exists(storage_path('installed'));

        if ($isInstalled) {
            if(request()->routeIs('installer.*')){
                return redirect()->route('login');
            }
        } else {
            if(!request()->routeIs('installer.*')){
                return redirect()->route('installer.welcome');
            }
        }

        return $next($request);
    }
}
