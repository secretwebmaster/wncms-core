<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Record;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $records = Record::latest()->paginate(50);
        $websites = wn('website')->getList();
        return view('wncms::backend.records.index',[
            'records' => $records,
            'websites' => $websites,
            'types' => Record::TYPES,
            'orders' => Record::ORDERS,
        ]);
    }

    public function bulk_delete(Request $request)
    {
        info($request->all());

        Record::whereIn('id', $request->model_ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_deleted'),
        ]);
    }

}
