<?php

namespace Wncms\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Wncms\Models\Link;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function click(Request $request)
    {
        // info($request->all());
        $link = Link::find($request->id);
        if($link){
            $link->increment('clicks');
            return response()->json(['status' => 'success']);
        }
    }
}