<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\ContactFormOption;
use Illuminate\Http\Request;

class ContactFormOptionController extends Controller
{
    public function index(Request $request)
    {
        $contact_form_options = ContactFormOption::query()->get();
        return view('wncms::backend.contact_form_options.index', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.contact_form_option')]),
            'contact_form_options' => $contact_form_options,
        ]);
    }

    public function create(ContactFormOption $contact_form_option = null)
    {
        $contact_form_option ??= new ContactFormOption;
        return view('wncms::backend.contact_form_options.create', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.contact_form_option')]),
            'contact_form_option' => $contact_form_option,
            'types' => ContactFormOption::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $contact_form_option = ContactFormOption::create([
            'name' => $request->name,
            'type' => $request->type,
            'display_name' => $request->display_name,
            'placeholder' => $request->placeholder,
            'default_value' => $request->default_value,
            'options' => $request->options,
        ]);

        wncms()->cache()->tags(['contact_forms'])->flush();
        wncms()->cache()->tags(['contact_form_options'])->flush();

        return redirect()->route('contact_form_options.edit', [
            'contact_form_option' => $contact_form_option,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(ContactFormOption $contact_form_option)
    {
        // dd($contact_form_option);
        return view('wncms::backend.contact_form_options.edit', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.contact_form_option')]),
            'contact_form_option' => $contact_form_option,
            'types' => ContactFormOption::TYPES,
        ]);
    }

    public function update(Request $request, ContactFormOption $contact_form_option)
    {
        // dd($request->all());
        $contact_form_option->update([
            'name' => $request->name,
            'type' => $request->type,
            'display_name' => $request->display_name,
            'placeholder' => $request->placeholder,
            'default_value' => $request->default_value,
            'options' => $request->options,
        ]);

        wncms()->cache()->tags(['contact_forms'])->flush();
        wncms()->cache()->tags(['contact_form_options'])->flush();
        
        return redirect()->route('contact_form_options.edit', [
            'contact_form_option' => $contact_form_option,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(ContactFormOption $contact_form_option)
    {
        $contact_form_option->delete();
        return redirect()->route('contact_form_options.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        ContactFormOption::whereIn('id', explode(",", $request->model_ids))->delete();
        return redirect()->route('contact_form_options.index')->withMessage(__('wncms::word.successfully_deleted'));
    }
}
