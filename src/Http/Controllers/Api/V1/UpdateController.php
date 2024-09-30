<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Jobs\UpdateCore;
use Wncms\Jobs\UpdateTheme;
use Artisan;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function update(Request $request)
    {
        if(gss('disalbe_core_update')){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.core_update_disabled'),
            ]);
        }

        Artisan::call('queue:restart');
        sleep(5);
        
        // info($request->all());
        if($request->itemType == 'core'){
            info("set updating core to 1");
            uss('updating_core', 1);
            UpdateCore::dispatch();
        }

        if($request->itemType == 'theme'){
            UpdateTheme::dispatch($request->themeId);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_dispatched_job'),
        ]);
    }

    public function progress(Request $request)
    {
        if($request->itemType == 'core'){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_fetched_updating_progress'),
                'progress' => gss('updating_core', 0, false),
            ]);
        }
    }
}
