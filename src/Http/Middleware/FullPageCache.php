<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FullPageCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //This middleware should not be used in any backend or api route
        // TODO: set in system setting
        $domainsWithCache = wncms()->website()->getDomainsWithCache();

        if(in_array(wncms()->website()->get()?->domain, $domainsWithCache)){
            $cacheKey = $request->fullUrl();
            $cacheTag = ['full_pages'];
            $cachedResponse = wncms()->cache()->tags($cacheTag)->get($cacheKey);
            // wncms()->cache()->tags($cacheTag)->forget($cacheKey);

            if(!empty($cachedResponse)){
                return new Response($cachedResponse);
            }

            $response = $next($request);
            
            if($response->status() == 200){
                wncms()->cache()->tags($cacheTag)->put($cacheKey, $response->getContent(), gss('data_cache_time', 3600));
            }

            return $response;
        }
        
        return $next($request);

    }
}
