<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Jobs\ContactFormSubmissionNotification;
use Wncms\Models\ContactFormSubmission;
use Illuminate\Http\Request;
use Wncms\Facades\Wncms;

class ContactFormSubmissionController extends FrontendController
{
    public function submit_ajax(Request $request)
    {
        // info($request->all());
        parse_str($request->formData, $formData);
        // info($formData);

        $contactFormId = $formData['contact_form_id'] ?? null;
        unset($formData['_token']);
        unset($formData['contact_form_id']);

        $contactFormSubmission = ContactFormSubmission::create([
            'website_id' => $this->website?->id,
            'contact_form_id' => $contactFormId,
            'content' => $formData,
            // 'content' => $request->except(['_token', 'contact_form_id'])
        ]);

        if(gss('enable_smtp') && gss('superadmin_email')){
            ContactFormSubmissionNotification::dispatch($contactFormSubmission);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_submitted'),
            'btn_text' => __('wncms::word.submitted'),
            'restoreBtn' => false,
        ]);
    }
}
