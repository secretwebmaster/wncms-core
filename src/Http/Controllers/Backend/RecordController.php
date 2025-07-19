<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class RecordController extends BackendController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $q->orderBy('id', 'desc');
        
        $records = $q->paginate(50);

        return $this->view('backend.records.index',[
            'page_title' => wncms_model_word('record', 'management'),
            'records' => $records,
            'types' => $this->modelClass::TYPES,
            'orders' => $this->modelClass::ORDERS,
        ]);
    }
}
