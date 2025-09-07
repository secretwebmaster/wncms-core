<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;
use Wncms\Jobs\FixPermissionErrorJob;

class ToolController extends Controller
{
    public function index()
    {
        return view('wncms::backend.tools.index', [
            'page_title' => __('wncms::word.tools'),
        ]);
    }
}
