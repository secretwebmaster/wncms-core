<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        $q->orderBy('id', 'desc');

        $parameters = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.parameters.index', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameters' => $parameters,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $parameter = $this->modelClass::find($id);
            if (!$parameter) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $parameter = new $this->modelClass;
        }

        return $this->view('backend.parameters.create', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameter' => $parameter,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $parameter = $this->modelClass::create([
            'name' => $request->name,
            'key' => $request->key,
            'remark' => $request->remark,
        ]);
        $this->syncBackendMutationWebsites($parameter);

        $this->flush();

        return redirect()->route('parameters.edit', [
            'id' => $parameter,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $parameter = $this->modelClass::find($id);
        if (!$parameter) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.parameters.edit', [
            'page_title' =>  wncms_model_word('parameter', 'management'),
            'parameter' => $parameter,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $parameter = $this->modelClass::find($id);
        if (!$parameter) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $parameter->update([
            'name' => $request->name,
            'key' => $request->key,
            'remark' => $request->remark,
        ]);
        $this->syncBackendMutationWebsites($parameter);

        $this->flush();

        return redirect()->route('parameters.edit', [
            'id' => $parameter,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
