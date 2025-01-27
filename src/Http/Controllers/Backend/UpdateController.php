<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UpdateController extends Controller
{
    public function index()
    {
        $result = $this->getUpdateData();

        if(gss('disable_core_update') && !empty($result['data']['core'])){
            unset($result['data']['core']);
        }

        return view('wncms::backend.admin.update', [
            'page_title' => __('wncms::word.system_update'),
            'result' => $result,
        ]);
    }

    public function check()
    {
        $response = Http::get("https://api.wncms.cc/api/v1/update/latest");

        $currentVersion = gss('core_version', '1.0.0');

        $latestVersion = $response->json()['data']['version'];

        $result = version_compare($currentVersion, $latestVersion);

        if($result < 0){
            $result = [
                'status' => 'success',
                'message' => __('wncms::word.new_version_available_with_versions', ['current' => $currentVersion, 'latest' => $latestVersion]),
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'has_update' => true,
                'url' => route('updates'),
                'button_text' => __('wncms::word.update_now'),
            ];

        }else{
            $result = [
                'status' => 'success',
                'message' => __('wncms::word.already_the_latest_vcersion'),
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
            $coreResponse = Http::post("https://api.wncms.cc/api/v1/update/check", [
                'current_version' => gss('core_version'),
                'domain' => request()->getHost(),
                'products' => ['core', 'theme1', 'theme2', 'plugin1', 'plugin2'],
            ]);
            
            return json_decode($coreResponse->body(), true);
 
        }catch(\Exception $e){
            logger()->error($e);
            return [];
            // dd('update fail, please contact customer support');
        }
    }

}
