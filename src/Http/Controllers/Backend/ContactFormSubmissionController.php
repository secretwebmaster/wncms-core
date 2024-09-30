<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Exports\ContactFormSubmissionExport;
use Wncms\Jobs\ContactFormSubmissionNotification;
use Wncms\Models\ContactFormSubmission;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactFormSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $q = ContactFormSubmission::query();

        if($request->keyword){
            $q->where('content', 'like', "%$request->keyword%");
        }

        if($request->order && in_array($request->order, ContactFormSubmission::ORDERS)){
            $q->orderBy($request->order, $request->sort == 'asc' ? 'asc' : 'desc');
        }
        $q->orderBy('id', 'desc');
        
        $contact_form_submissions = $q->paginate($request->page_size ?? 20);

        $allKeys = collect();
        foreach ($contact_form_submissions as $contact_form_submission){
            $allKeys = $allKeys->merge(array_keys($contact_form_submission->content ?? []));
        }

        $allKeys = $allKeys->unique()->sort();

        return view('wncms::backend.contact_form_submissions.index', [
            'page_title' => __('word.model_management', ['model_name' => __('word.contact_form_submission')]),
            'contact_form_submissions' => $contact_form_submissions,
            'allKeys' => $allKeys,
            'orders' => ContactFormSubmission::ORDERS,
        ]);
    }

    public function show(ContactFormSubmission $contact_form_submission)
    {
        return view('wncms::backend.contact_form_submissions.show', [
            'contact_form_submission' => $contact_form_submission,
        ]);
    }

    public function destroy(ContactFormSubmission $contact_form_submission)
    {
        $contact_form_submission->delete();
        return redirect()->route('contact_form_submissions.index')->withMessage(__('word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = ContactFormSubmission::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('contact_form_submissions.index')->withMessage(__('word.successfully_deleted_count', ['count' => $count]));
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

        $q = ContactFormSubmission::query();
        
        if($type == 'selected'){
            $modelIds = explode(",", $request->modelIds);
            $q->whereIn('id', $modelIds);
            $contact_form_submissions = $q->get();
        }else{
            if($request->keyword){
                $q->where('content', 'like', "%$request->keyword%");
            }
    
            if($request->order && in_array($request->order, ContactFormSubmission::ORDERS)){
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
