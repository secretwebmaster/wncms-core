<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class ContactFormOptionController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $q->orderBy('id', 'desc');

        $contact_form_options = $q->get();

        return $this->view('backend.contact_form_options.index', [
            'page_title' => wncms_model_word('contact_form_option', 'management'),
            'contact_form_options' => $contact_form_options,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $contact_form_option = $this->modelClass::find($id);
            if (!$contact_form_option) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $contact_form_option = new $this->modelClass;
        }

        return $this->view('backend.contact_form_options.create', [
            'page_title' => wncms_model_word('contact_form_option', 'management'),
            'contact_form_option' => $contact_form_option,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $contact_form_option = $this->modelClass::create([
            'name' => $request->name,
            'type' => $request->type,
            'display_name' => $request->display_name,
            'placeholder' => $request->placeholder,
            'default_value' => $request->default_value,
            'options' => $request->options,
        ]);

        $this->flush();
        $this->flush(['contact_form_options']);

        return redirect()->route('contact_form_options.edit', [
            'id' => $contact_form_option,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $contact_form_option = $this->modelClass::find($id);
        if (!$contact_form_option) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.contact_form_options.edit', [
            'page_title' => wncms_model_word('contact_form_option', 'management'),
            'contact_form_option' => $contact_form_option,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact_form_option = $this->modelClass::find($id);
        if (!$contact_form_option) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }
        // dd($request->all());
        $contact_form_option->update([
            'name' => $request->name,
            'type' => $request->type,
            'display_name' => $request->display_name,
            'placeholder' => $request->placeholder,
            'default_value' => $request->default_value,
            'options' => $request->options,
        ]);

        $this->flush();
        $this->flush(['contact_form_options']);

        return redirect()->route('contact_form_options.edit', [
            'id' => $contact_form_option,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
