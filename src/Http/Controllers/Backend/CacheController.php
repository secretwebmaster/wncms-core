<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CacheController extends Controller
{
    /**
     * Forget a cache key
     *
     */
    public function clear($key, $tags = '')
    {
        $cache = app('cache');

        if (!empty($tags)) {
            $tags = json_decode($tags, true);
            $cache = $cache->tags($tags);
        } else {
            unset($tags);
        }

        $success = $cache->forget($key);
        
        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.cache_flushed'),
                'reload' => true,
            ]);
        }
    }

    public function flush(Request $request, string|array $tags = null)
    {
        if(!empty($tags)){
            if(is_string($tags)){
                $tags = explode(",", $tags);
            }
            wncms()->cache()->tags($tags)->flush();
        }else{
            cache()->flush();
        }
       
        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.cache_flushed'),
                'reload' => true,
            ]);
        }
    }
}
