<?php

namespace Wncms\Http\Controllers;

use Wncms\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeController extends Controller
{
    public function index(){

        $themes = [];
        // Get the themes from resource/views/themes
        $themes = collect(glob(resource_path('views/frontend/theme/*')))
            ->filter(function($theme){
                return is_dir($theme);
            })
            ->map(function($theme){
                $themeId = basename($theme);
                $themePath = $this->getThemePath($themeId) . "/system/" . "config.php";
                if (file_exists($themePath)) {

                    $config = include $themePath;
                    if(empty($config['info']) || empty($config['info']['id'])){
                        return [
                            'id' => $themeId,
                            'name' => $themeId,
                            'isValid' => false,
                        ];
                    }
                    $info = $config['info'];

                    // foreach info item, if is array, get the value for the current locale
                    foreach($info as $key => $value){
                        if(is_array($value)){
                            $info[$key] = $value[app()->getLocale()] ??  collect($info)->first();
                        }
                    }

                    $info['isValid'] = true;
                    // dd($info);
                    return $info;
                }else{
                    return [
                        'id' => $themeId,
                        'name' => $themeId,
                        'isValid' => false,
                    ];
                }
                
            });
        
        // Get the active theme
        $activatedThemeIds = array_keys(config('theme'));

        return view('wncms::backend.themes.index', [
            'themes' => $themes,
            'activatedThemeIds' => $activatedThemeIds,
            'page_title' => __('wncms::word.theme_list'),
        ]);
    }

    public function activate($themeId){

        $themePath = resource_path("views/frontend/theme/{$themeId}");

        $validationResult =  $this->validateThemeFiles($themePath);
        if($validationResult != 'passed'){
            return response()->json([
                'status' => 'error',
                'message' => 'The theme does not have the required theme file structure'
            ]);
        }

        $themeId = $this->getThemeIdFromConfig($themePath);
        if(!$themeId){
            return response()->json([
                'status' => 'error',
                'message' => 'The theme does not have the required config file structure'
            ]);
        }

        // copy content of resources/views/frontend/theme/{$themeId}/system/config.php to config/theme/{$themeId}.php
        $sourceConfigPath = $themePath . "/system/" . "config.php";
        $destinationConfigPath = base_path("config/theme/{$themeId}.php");
        $this->copyThemeFile($sourceConfigPath, $destinationConfigPath);

        // copy assets from resources/views/frontend/theme/{$themeId}/assets/to config/theme/{$themeId}/
        $sourceAssetsPath = $themePath . "/assets";
        $destinationAssetsPath = public_path("theme/{$themeId}");
        $this->copyThemeFolder($sourceAssetsPath, $destinationAssetsPath);

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.theme_activated_successfully'),
            'reload' => true
        ]);
    }

    public function deactivate($themeId){

        if(in_array($themeId,['default', 'starter'])){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.cannot_delete_default_themes'),
            ]);
        }

        // remove config file
        $destinationConfigPath = base_path("config/theme/{$themeId}.php");
        File::delete($destinationConfigPath);

        // remove assets
        $destinationAssetsPath = public_path("theme/{$themeId}");
        File::deleteDirectory($destinationAssetsPath);

        // update website model theme to default
        Website::where('theme', $themeId)->update(['theme' => 'default']);

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.theme_deactivated_successfully'),
            'reload' => true
        ]);
    }

    public function upload(Request $request)
    {
        // dd($request->all());

        // Validate the request
        $request->validate(
            [
                'theme_file' => 'required|file|mimes:zip'
            ],
            [
                'theme_file.required' => __('wncms::word.please_select_a_theme_file'),
                'theme_file.mimes' => __('wncms::word.please_select_a_valid_theme_file')
            ]
        );

        // save the file in temp folder
        $themeFile = $request->file('theme_file');
        $themeFileName = $themeFile->getClientOriginalName();
        $themeFile->storeAs('temp/theme', $themeFileName);

        // unzip the file
        $zipFilePath = storage_path('app/temp/theme/' . $themeFileName);
        $nameWithoutExtension = pathinfo($themeFileName, PATHINFO_FILENAME);
        $extractPath = storage_path("app/temp/theme/$nameWithoutExtension");

        // unzip the file
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            unlink($zipFilePath);
        } else {
            // Handle the error
            dd("Error unzipping the file");
        }

        // get the root theme path
        $rootThemePath = $this->getRootThemePath($extractPath);
       
        // check if the theme has the required file structure
        $validationResult = $this->validateThemeFiles($rootThemePath);
        
        switch($validationResult){
            case 1001:
                dd("Error 1001");
                return redirect()->back()->with('error_message', 'The theme does not have the required file structure');
            case 1002:
                dd("Error 1002");
                return redirect()->back()->with('error_message', 'The theme does not have the required file structure');
            case 1003:
                dd("Error 1003");
                return redirect()->back()->with('error_message', 'The theme does not have the required file structure');
        }
        
        // check if the theme already exists
        $themeId = $this->getThemeIdFromConfig($rootThemePath);
        $themePath = resource_path("views/frontend/theme/{$themeId}");

        if(file_exists($themePath)){

            // compare them version
            $configPath = $rootThemePath . "/system/config.php";
            $config = include $configPath;
            $newVersion = $config['info']['version'] ?? 0;

            $configPath = $themePath . "/system/config.php";
            $config = include $configPath;
            $currentVersion = $config['info']['version'] ?? 0;
        
            if (version_compare($newVersion, $currentVersion, '<')) {
                return redirect()->back()->with('error_message', __('wncms::word.the_same_theme_with_higher_version_already_exists'));
            } elseif (version_compare($newVersion, $currentVersion, '==')) {
                return redirect()->back()->with('error_message', __('wncms::word.the_same_theme_with_the_same_version_already_exists'));
            }
        }

        // Create the directory if it does not exist
        File::makeDirectory($themePath, 0755, true, true);

        // Move the theme to the themes folder. Overide if it already exists
        File::copyDirectory($rootThemePath, $themePath);

        // Delete the temp folder
        File::deleteDirectory($extractPath);

        return redirect()->route('themes.index')->with([
            'message', __('wncms::word.theme_uploaded_successfully'),
            'themeId' => $themeId
        ]);
    }

    public function delete($theme){
        // Delete the theme
        // Storage::delete('themes/' . $theme);
        // return redirect()->back()->with('success', 'Theme deleted successfully');
    }

    public function preview($theme){
        // Update the active theme in the settings
        // Setting::set('active_theme', $theme);
        // Setting::save();
        // return redirect()->back()->with('success', 'Theme previewed successfully');
    }

    public function settings(){
        // Get the settings
        // $settings = Setting::all();
        // return view('wncms::backend.themes.settings', [
        //     'settings' => $settings
        // ]);
    }

    public function updateSetting(Request $request){
        // Update the setting
        // Setting::set($request->key, $request->value);
        // Setting::save();
        // return redirect()->back()->with('success', 'Setting updated successfully');
    }

    public function updateSettings(Request $request){
        // Update the settings
        // foreach($request->all() as $key => $value){
        //     Setting::set($key, $value);
        // }
        // Setting::save();
        // return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function resetSettings(){
        // Reset the settings
        // Setting::reset();
        // return redirect()->back()->with('success', 'Settings reset successfully');
    }

    public function export(){
        // Export the settings
        // Setting::export();
        // return redirect()->back()->with('success', 'Settings exported successfully');
    }

    public function import(Request $request){
        // Validate the request
        // $request->validate([
        //     'file' => 'required|file|mimes:json'
        // ]);

        // $file = $request->file('file');
        // Setting::import($file);
        // return redirect()->back()->with('success', 'Settings imported successfully');
    }

    public function validateThemeFiles($rootThemePath)
    {
        // files contain pages/home.blade.php
        $homePagePath = $rootThemePath . '/pages/home.blade.php';
        if(!file_exists($homePagePath)){
            return 1001;
        }

        // files contain system/config.php
        $configPath = $rootThemePath . '/system/config.php';
        if(!file_exists($configPath)){
            return 1002;
        }

        //scan for malwares
        $config = file_get_contents($configPath);
        $foundMalwares = preg_match('/system\(|exec\(|shell_exec\(|eval\(/', $config);
        if($foundMalwares){
            return 1003;
        }

        return 'passed';
    }
    
    public function getThemePath($themeId){
        return resource_path("views/frontend/theme/{$themeId}");
    }

    public function getThemeIdFromConfig($path){
        $configPath = $path . "/system/" . "config.php";
        if (file_exists($configPath)) {
            $config = include $configPath;
            return $config['info']['id'] ?? false;
        }

        return false;
    }

    public function copyThemeFile($source, $destination){
        File::copy($source, $destination);
    }

    public function copyThemeFolder($source, $destination){
        File::copyDirectory($source, $destination);
    }

    public function getRootThemePath($extractPath){

        // if dir has only one folder, return that folder
        $folders = collect(glob($extractPath . '/*'))
            ->filter(function($folder){
                return is_dir($folder);
            });
        
        if($folders->count() == 1){
            $extractPath = $folders->first();
        }
            
        return $extractPath;
    }
}
