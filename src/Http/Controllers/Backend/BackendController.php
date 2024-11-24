<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Facades\Wncms;
use Wncms\Http\Controllers\Controller;

class BackendController extends Controller
{
    /**
     * Flush the cache.
     * 
     * @param string|array|null $tag
     * @return bool
     */
    public function flush(string|array|null $tag = null)
    {
       return Wncms::cache()->flush($tag);
    }
}
