<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends BackendController
{
    public function index(Request $request)
    {
        $q = Parameter::query();
        
        $parameters = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.parameters.index', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameters' => $parameters,
        ]);
    }

    public function create(?Parameter $parameter)
    {
        $parameter ??= new Parameter;

        return view('wncms::backend.parameters.create', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameter' => $parameter,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $parameter = Parameter::create([
            'name' => $request->name,
            'key' => $request->key,
            'remark' => $request->remark,
        ]);

        wncms()->cache()->flush(['parameters']);

        return redirect()->route('parameters.edit', [
            'parameter' => $parameter,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Parameter $parameter)
    {
        return view('wncms::backend.parameters.edit', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameter' => $parameter,
        ]);
    }

    public function update(Request $request, Parameter $parameter)
    {
        // dd($request->all());

        $parameter->update([
            'name' => $request->name,
            'key' => $request->key,
            'remark' => $request->remark,
        ]);

        wncms()->cache()->flush(['parameters']);
        
        return redirect()->route('parameters.edit', [
            'parameter' => $parameter,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Parameter $parameter)
    {
        $parameter->delete();
        return redirect()->route('parameters.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = Parameter::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('parameters.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
