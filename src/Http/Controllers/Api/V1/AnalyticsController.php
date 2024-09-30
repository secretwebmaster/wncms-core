<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Jobs\RecordViews;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function record(Request $request)
    {
        // info($request->all());
        if($request->collection == 'click'){
            $cooldown = gss('click_count_cooldown', 60);
        }elseif($request->collection == 'view'){
            $cooldown = gss('view_count_cooldown', 1440);
        }else{
            $cooldown = gss('other_count_cooldown', 1);
        }

        RecordViews::dispatch($request->modelId, $request->modelType, $request->collection ?? 'view', $cooldown);
        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_record'),
        ]);
    }

    public function get(Request $request)
    {
        info($request->all());
        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_fetch'),
            'count' => $count ?? 0,
        ]);
    }
}
