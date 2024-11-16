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
        // info($request->all());
        if(gss('disalbe_core_update')){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.core_update_disabled'),
            ]);
        }

        // set system update status to 1
        uss('updating_core', 1);

        // get package name to update
        $pakage = $request->package;
        $version = $request->version;

        // call composer command to update specific package

        // vertify the update

        // update version number

        // set system update status to 0
        uss('updating_core', 0);

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated'),
        ]);
    }

    public function progress(Request $request)
    {
        if($request->itemId == 'core'){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_fetched_updating_progress'),
                'progress' => gss('updating_core', 0, false),
            ]);
        }
    }
}
