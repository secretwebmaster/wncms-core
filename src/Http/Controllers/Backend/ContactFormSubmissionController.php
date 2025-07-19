<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Exports\ContactFormSubmissionExport;
use Wncms\Jobs\ContactFormSubmissionNotification;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactFormSubmissionController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if($request->keyword){
            $q->where('content', 'like', "%$request->keyword%");
        }

        if($request->order && in_array($request->order, $this->modelClass::ORDERS)){
            $q->orderBy($request->order, $request->sort == 'asc' ? 'asc' : 'desc');
        }
        $q->orderBy('id', 'desc');
        
        $contact_form_submissions = $q->paginate($request->page_size ?? 20);

        $allKeys = collect();
        foreach ($contact_form_submissions as $contact_form_submission){
            $allKeys = $allKeys->merge(array_keys($contact_form_submission->content ?? []));
        }

        $allKeys = $allKeys->unique()->sort();

        return $this->view('backend.contact_form_submissions.index', [
            'page_title' => wncms_model_word('contact_form_submission', 'management'),
            'contact_form_submissions' => $contact_form_submissions,
            'allKeys' => $allKeys,
            'orders' => $this->modelClass::ORDERS,
        ]);
    }

    public function show($id)
    {
        $contact_form_submission = $this->modelClass::find($id);
        if (!$contact_form_submission) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }
        return $this->view('backend.contact_form_submissions.show', [
            'contact_form_submission' => $contact_form_submission,
        ]);
    }
    
    public function export(Request $request, $type)
    {
        // dd($request->all());
        $extension = $request->format ?? 'xlsx';
        $formats = [
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'tsv' => \Maatwebsite\Excel\Excel::TSV,
            'ods' => \Maatwebsite\Excel\Excel::ODS,
            'html' => \Maatwebsite\Excel\Excel::HTML,
            'mpdf' => \Maatwebsite\Excel\Excel::MPDF,
            'dompdf' => \Maatwebsite\Excel\Excel::DOMPDF,
            'tcpdf' => \Maatwebsite\Excel\Excel::TCPDF,
        ];
        $format = $formats[$extension] ?? \Maatwebsite\Excel\Excel::XLSX;
        $fileName = 'contact_form_data.' .  $extension;

        $q = $this->modelClass::query();
        
        if($type == 'selected'){
            $modelIds = explode(",", $request->modelIds);
            $q->whereIn('id', $modelIds);
            $contact_form_submissions = $q->get();
        }else{
            if($request->keyword){
                $q->where('content', 'like', "%$request->keyword%");
            }
    
            if($request->order && in_array($request->order, $this->modelClass::ORDERS)){
                $q->orderBy($request->order, $request->sort == 'asc' ? 'asc' : 'desc');
            }
            $q->orderBy('id', 'desc');
            
            if($type == 'current_page'){
                $contact_form_submissions = $q->paginate($request->page_size ?? 20)->items();
            }else{
                $contact_form_submissions = $q->get();
            }
        }

     

        $allKeys = collect();
        foreach ($contact_form_submissions as $contact_form_submission){
            $allKeys = $allKeys->merge(array_keys($contact_form_submission->content ?? []));
        }

        $allKeys = $allKeys->unique()->sort()->toArray();


        return Excel::download(new ContactFormSubmissionExport($allKeys, $contact_form_submissions), $fileName, $format);
    }
}
