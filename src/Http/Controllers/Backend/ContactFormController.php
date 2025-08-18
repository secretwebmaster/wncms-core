<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class ContactFormController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $q->orderBy('id', 'desc');

        $contact_forms = $q->paginate($request->page_size ?? 20);

        return $this->view('backend.contact_forms.index', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'contact_forms' => $contact_forms,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $contact_form = $this->modelClass::find($id);
            if (!$contact_form) {
                return back()->withInput()->with([
                    'code' => 1001,
                    'message' => __('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]),
                ]);
            }
        } else {
            $contact_form = new $this->modelClass;
        }

        $options = wncms()->getModelClass('contact_form_option')::all();

        return $this->view('backend.contact_forms.create', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'contact_form' => $contact_form,
            'options' => $options,
        ]);
    }

    public function store(Request $request)
    {
        $contact_form = $this->modelClass::create([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'remark' => $request->remark,
            'success_action' => $request->success_action,
            'fail_action' => $request->fail_action,
        ]);

        //order options
        $optionData = [];
        $order = 1;
        foreach($request->options as $optionInput){
            if(!empty($optionInput['option_id'])){
                $optionData[$optionInput['option_id']] = [
                    'order' => $order,
                    'is_required' => !empty($optionInput['option_is_required']) ? true : false,
                ];
            }
            $order++;
        }

        if(!empty($optionData)){
            $contact_form->options()->sync($optionData);
        }

        $this->flush();

        return redirect()->route($this->plural . '.edit', [
            'id' => $contact_form,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $contact_form = $this->modelClass::find($id);
        if (!$contact_form) {
            return back()->withInput()->with([
                'code' => 1001,
                'message' => __('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]),
            ]);
        }

        $allOptions = wncms()->getModelClass('contact_form_option')::all();
        $existingOptionOrder = $contact_form->options->pluck('pivot.order', 'id')->toArray();

        $options = $allOptions->map(function($item) use($existingOptionOrder){
            if(!empty($existingOptionOrder[$item->id])){
                $item->order = $existingOptionOrder[$item->id];
            }else{
                $item->order = 99999;
            }
            return $item;
        });

        return $this->view('backend.contact_forms.edit', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.contact_form')]),
            'contact_form' => $contact_form,
            'options' => $options,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact_form = $this->modelClass::find($id);
        if (!$contact_form) {
            return back()->withInput()->with([
                'code' => 1001,
                'message' => __('wncms::word.model_not_found', ['model_name' => __('wncms::word.contact_form')]),
            ]);
        }

        $contact_form->update([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'remark' => $request->remark,
            'success_action' => $request->success_action,
            'fail_action' => $request->fail_action,
        ]);

        //order options
        $optionData = [];
        $order = 1;
        foreach($request->options as $optionInput){
            if(!empty($optionInput['option_id'])){
                $optionData[$optionInput['option_id']] = [
                    'order' => $order,
                    'is_required' => !empty($optionInput['option_is_required']) ? true : false,
                ];
            }
            $order++;
        }

        if(!empty($optionData)){
            $contact_form->options()->sync($optionData);
        }

        $this->flush();
        
        return redirect()->route('contact_forms.edit', [
            'id' => $contact_form,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
