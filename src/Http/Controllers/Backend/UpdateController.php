<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UpdateController extends Controller
{
    public $colors = [
        'add' => 'success',
        'fix' => 'primary',
        'improve' => 'info',
        'remove' => 'danger',
    ];

    public function index()
    {
        $result = $this->getUpdateData();
        // dd($result);
        return view('wncms::backend.admin.update', [
            'page_title' => __('word.system_update'),
            'colors' => $this->colors,
            'result' => $result,
        ]);
    }

    public function check()
    {
        $response = Http::post("https://corev4.wncms.cc/api/v2/versions/latest");

        $currentVersion = gss('version');

        $latestVersion = $response->json()['tag'] ?? null;
        $latestVersion = str_replace('v', '', $latestVersion);

        $result = version_compare($currentVersion, $latestVersion);

        if($result < 0){
            $result = [
                'status' => 'success',
                'message' => __('word.new_version_available_with_versions', ['current' => $currentVersion, 'latest' => $latestVersion]),
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'has_update' => true,
                'url' => route('updates'),
                'button_text' => __('word.update_now'),
            ];

        }else{
            $result = [
                'status' => 'success',
                'message' => __('word.already_the_latest_vcersion'),
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'has_update' => false,
            ];
        }

        return response()->json($result);
    }

    public function getUpdateData()
    {
        try{
            $coreResponse = Http::post("https://core.wncms.cc/api/v1/update/check", [
                'current_version' => gss('version'),
                'domain' => request()->url(),
            ]);
            
            return json_decode($coreResponse->body(), true);
 
        }catch(\Exception $e){
            logger()->error($e);
            return [];
            // dd('update fail, please contact customer support');
        }
    }

}
