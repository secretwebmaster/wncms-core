<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;

use Wncms\Models\ContactForm;
use Wncms\Models\ContactFormOption;
use Illuminate\Http\Request;

class ContactFormController extends Controller
{
    public function index(Request $request)
    {
        $contact_forms = ContactForm::query()->get();
        return view('wncms::backend.contact_forms.index', [
            'page_title' => __('word.model_management', ['model_name' => __('word.contact_form')]),
            'contact_forms' => $contact_forms,
        ]);
    }

    public function create(ContactForm $contact_form = null)
    {
        $contact_form ??= new ContactForm;
        $options = ContactFormOption::all();
        return view('wncms::backend.contact_forms.create', [
            'page_title' => __('word.model_management', ['model_name' => __('word.contact_form')]),
            'contact_form' => $contact_form,
            'options' => $options,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $contact_form = ContactForm::create([
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

        wncms()->cache()->tags(['contact_forms'])->flush();

        return redirect()->route('contact_forms.edit', [
            'contact_form' => $contact_form,
        ])->withMessage(__('word.successfully_created'));
    }

    public function edit(ContactForm $contact_form)
    {
        $allOptions = ContactFormOption::all();
        $existingOptionOrder = $contact_form->options->pluck('pivot.order', 'id')->toArray();

        $options = $allOptions->map(function($item) use($existingOptionOrder){
            if(!empty($existingOptionOrder[$item->id])){
                $item->order = $existingOptionOrder[$item->id];
            }else{
                $item->order = 99999;
            }
            return $item;
        });

        return view('wncms::backend.contact_forms.edit', [
            'page_title' => __('word.model_management', ['model_name' => __('word.contact_form')]),
            'contact_form' => $contact_form,
            'options' => $options,
        ]);
    }

    public function update(Request $request, ContactForm $contact_form)
    {
        // dd($request->all());
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

        wncms()->cache()->tags(['contact_forms'])->flush();
        
        return redirect()->route('contact_forms.edit', [
            'contact_form' => $contact_form,
        ])->withMessage(__('word.successfully_updated'));
    }

    public function destroy(ContactForm $contact_form)
    {
        $contact_form->delete();
        return redirect()->route('contact_forms.index')->withMessage(__('word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        // dd($request->all());
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = ContactForm::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('contact_forms.index')->withMessage(__('word.successfully_deleted_count', ['count' => $count]));
    }
    
}
