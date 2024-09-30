<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Controller;
use Wncms\Jobs\ContactFormSubmissionNotification;
use Wncms\Models\ContactFormSubmission;
use Illuminate\Http\Request;

class ContactFormSubmissionController extends Controller
{
    public function submit_ajax(Request $request)
    {
        // info($request->all());
        parse_str($request->formData, $formData);
        // info($formData);

        $contactFormId = $formData['contact_form_id'] ?? null;
        unset($formData['_token']);
        unset($formData['contact_form_id']);

        $website = wncms()->website()->getCurrent();

        $contactFormSubmission = ContactFormSubmission::create([
            'website_id' => $website?->id,
            'contact_form_id' => $contactFormId,
            'content' => $formData,
            // 'content' => $request->except(['_token', 'contact_form_id'])
        ]);

        if(gss('enable_smtp') && gss('superadmin_email')){
            ContactFormSubmissionNotification::dispatch($contactFormSubmission);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_submitted'),
            'btn_text' => __('word.submitted'),
            'restoreBtn' => false,
        ]);
    }
}