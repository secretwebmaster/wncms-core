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
        $installation_mark = storage_path("installed");
        
        if (file_exists($installation_mark)) {
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
