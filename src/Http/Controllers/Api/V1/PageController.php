<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\User;
use Wncms\Models\Website;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetched_page_index'),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetched_page_store'),
        ]);
    }

    public function show(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetched_page_show'),
        ]);
    }
}
