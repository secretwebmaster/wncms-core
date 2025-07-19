<?php

use Wncms\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


/**
 * ------------------------------------------------------------------------
 * WNCMS Helper Naming rules
 * % 本頁功能即將整合至 wncms() Helper﹛新頁面請勿使用，舊頁面盡快更新
 * TODO: Move all functions to wncms()->??
 * ------------------------------------------------------------------------
 * All Wncms core helpers should start with "wncms_" except alias helpers
 * All helpers that return data set should start with "wncms_get_"
 * All helpers that update data should start with "wncms_update_"
 * All helpers that check condition and return boolean should start with "wncms_is_"
 * All helpers that check relationship and return boolean start with "wncsm_has_"
 * Other helpers that handle specific action should be named as format "wncms_{action}"
 */


/**
 * ------------------------------------------------------------------------
 * System core helpers
 * ------------------------------------------------------------------------
 */
if (!function_exists('wncms_get_version')) {
    function wncms_get_version($debug_type = null)
    {
        if(!empty($debug_type)){
            if($debug_type == 'js') return gss('core_version') . env('APP_VERSION') . (env('JS_DEBUG') ? time() : '');
            if($debug_type == 'css') return gss('core_version') . env('APP_VERSION') . (env('CSS_DEBUG') ? time() : '');
        }
        $app_debug =  env('APP_DEBUG') ? time() : '';
        return gss('core_version') . $app_debug;
    }
}

if (!function_exists('wncms_get_model_names')) {

    function wncms_get_model_names()
    {
        // Use recursive glob to get all PHP files in subdirectories
        $appModels = collect(File::allFiles(app_path('Models')))
            ->map(function ($file) {
                // Get relative path and convert to namespace
                $relativePath = Str::replaceFirst(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $modelName = 'App\\Models\\' . Str::replace('.php', '', $namespacePath);

                return $modelName;
            });

        // 先取 App\Models 的 basename
        $appModelBasenames = $appModels->map(fn($model) => class_basename($model))->unique();

        $packageModels = collect(File::allFiles(dirname(__DIR__) . '/src/Models'))
            ->map(function ($file) {
                $relativePath = Str::replaceFirst(dirname(__DIR__) . '/src/Models' . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                return 'Wncms\\Models\\' . Str::replace('.php', '', $namespacePath);
            })
            ->filter(function ($modelName) use ($appModelBasenames) {
                // 如果 App\Models 裡已經有同名的 model，就略過
                return !$appModelBasenames->contains(class_basename($modelName));
            });

        $files = $appModels->merge($packageModels);

        $collection = $files->map(function ($modelName) {
            if (class_exists($modelName)) {
                $model = new $modelName;

                return [
                    'model_name' => class_basename($modelName),
                    'name_key' => defined(get_class($model) . '::NAME_KEY') ? $model::NAME_KEY : null,
                    'model_name_with_namespace' => $modelName,
                    'priority' => property_exists($model, 'menuPriority') ? $model->menuPriority : 0,
                    'routes' => defined($modelName . "::ROUTES") ? $modelName::ROUTES : null,
                ];
            }

            return null;
        })->filter();
        return $collection;
    }
}

if (!function_exists('wncms_route_exists')) {
    function wncms_route_exists($name)
    {
        return Route::has($name);
    }
}

if (!function_exists('wncms_route_is')) {
    function wncms_route_is($route_names, $true_value = true, $false_value = false)
    {
        $route_names = (array)$route_names;

        foreach($route_names as $route_name){
            if(request()->routeIs($route_name)){
                return $true_value;
            }
        }
        return $false_value;
    }
}

if (!function_exists('wncms_view')) {
    function wncms_view($view_name, $params, $fallback_view = null)
    {
        if(view()->exists($view_name)){
            return view($view_name, $params);
        }elseif(view()->exists($fallback_view)){
            return view($fallback_view, $params);
        }else{
            abort(404);
        }
    }
}

if (!function_exists('wncms_clear_cache')) {
    /**
     * 清理緩存
     * @since 1.0.0
     * @version 1.0.0
     * @param string|null $key 
     *      - 預設值: null
     *      - 用作緩存標識名稱
     *      - 命名規則為 "function_name_{$param1}_{$param2}"
     *      - 如果參數為array of string，使用implode將array轉為逗號分隔的string
     *      - 如無參數則直接使 "function_name" ，不需要底線結束
     * 
     * @param array|string|null $tag 
     *      - 預設值: null
     *      - Redis緩存標籤
     *      - 用model的plural作為tags name
     *      - 例如websites, videos, posts
     * @return boolean 成功清除 = true，不成功則 false
     */
    function wncms_clear_cache(string|null $cacheKey = null, array|string|null $cacheTags = null)
    {
        //沒有使用tag
        if (empty($cacheTags)) {
            if (empty($cacheKey)) return cache()->flush();
            return cache()->forget($cacheKey);
        }

        //有Tag
        $cacheTags = is_array($cacheTags) ? $cacheTags : [$cacheTags];
        if (empty($cacheKey)) return wncms()->cache()->tags([$cacheTags])->flush();
        return wncms()->cache()->tags($cacheTags)->forget($cacheKey);
    }
}

if (!function_exists('wncms_get_fontawesome_class')) {
    function wncms_get_fontawesome_class($string)
    {
        $class = str_replace('<i class="', "", $string);
        $class = str_replace('"></i>', "", $class);
        return $class;
    }
}

if (!function_exists('wncms_is_installed')) {
    function wncms_is_installed()
    {
        if (app()->environment('testing')) {
            return false;
        }

        $filename = storage_path("installed");
        return file_exists($filename);
    }
}

if (!function_exists('wncms_info')) {
    function wncms_info($data, $title = null)
    {
        if($title){
            info("================== $title start =================");
        }
        info($data);
        
        if($title){
            info("================== $title end =================");
        }
    }
}

if (!function_exists('wncms_to_unicode')) {
    function wncms_to_unicode($string)
    {
        $str = mb_convert_encoding($string, 'UCS-2', 'UTF-8');
        $arrstr = str_split($str, 2);
        $unistr = '';
        foreach ($arrstr as $n) {
            $dec = hexdec(bin2hex($n));
            $unistr .= '&#' . $dec . ';';
        }
        return $unistr;
    }
}

/**
 * 加載CSS文件，附加版本號，版本更新時會一併刷新
 * @link https://wncms.cc
 * @since 1.0.0
 * @version 3.0.0
 * @return string
 * @example wncms_css('theme/default/css/style.css')
 */
if (!function_exists('wncms_css')) {
    function wncms_css($path)
    {
        return asset($path) . "?v=" . wncms_get_version('css');
    }
}

/**
 * 加載JS文件，附加版本號，版本更新時會一併刷新
 * @link https://wncms.cc
 * @since 1.0.0
 * @version 3.0.0
 * @return string
 * @example wncms_js('theme/default/css/main.js')
 */
if (!function_exists('wncms_js')) {
    function wncms_js($path)
    {
        return asset($path) . "?v=" . wncms_get_version('js');
    }
}




/**
 * ------------------------------------------------------------------------
 * Array helpers
 * ------------------------------------------------------------------------
 */
if (!function_exists('wncms_array_strpos')) {
    // This will return $key, so you should check with !== false
    function wncms_array_strpos(string $string, array $array)
    {
        foreach ($array as $key => $value) {

            if (strpos($string, $value) !== false) {
                return $key;
            }
        }
        return false;
    }
}

if (!function_exists('wncms_array_has_any')) {
    //this will check if $array_2 contain any one of the value of $array_1 and return the key
    function wncms_array_has_any(array $array_1, array $array_2)
    {

        foreach ($array_1 as $key => $value) {
            if (in_array($value, $array_2)) {
                return $key;
            }
        }
        
        return false;
    }
}

if (!function_exists('wncms_array_has_keys')) {
    // This will return $key, so you should check with !== false
    function array_has_keys($array, $keys = [])
    {
        $count = count($keys);
        $exist = 0;
        foreach ($keys as $key) {
            if (!empty($array[$key])) {
                $exist++;
            }
        }
        return $count == $exist;
    }
}


/**
 * ------------------------------------------------------------------------
 * Url helpers
 * ------------------------------------------------------------------------
 */
if (!function_exists('wncms_add_http')) {
    function wncms_add_http($link)
    {
        $scheme = parse_url($link, PHP_URL_SCHEME);
        if (empty($scheme))
            $link = 'http://' . ltrim($link, '/');
        return $link;
    }
}

if (!function_exists('wncms_add_https')) {
    function wncms_add_https($link)
    {
        $scheme = parse_url($link, PHP_URL_SCHEME);
        if (empty($scheme))
            $link = 'https://' . ltrim($link, '/');
        return $link;
    }
}

if (!function_exists('wncms_add_dynamic_http')) {
    function wncms_add_dynamic_http($link)
    {
        $scheme = parse_url($link, PHP_URL_SCHEME);
        if (empty($scheme))
            $link = '//' . ltrim($link, '/');
        return $link;
    }
}

if (!function_exists('wncms_remove_http')) {
    function wncms_remove_http($link)
    {
        $link = str_replace('https://', '', $link);
        $link = str_replace('http://', '', $link);
        $link = str_replace('www.', '', $link);
        return $link;
    }
}

if (!function_exists('wncms_get_referer')) {
    function wncms_get_referer()
    {
        return parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    }
}

if (!function_exists('wncms_get_unique_slug')) {
    function wncms_get_unique_slug($table, $column, $length = 8, $case = null)
    {
        $slug = str()->random($length);
        if ($case == 'upper') {
            $slug = strtoupper($slug);
        } elseif ($case == 'lower') {
            $slug = strtolower($slug);
        }

        $duplicated = DB::table($table)->where($column, $slug)->first();
        if (!$duplicated) {
            return $slug;
        } else {
            // TODO: set retry limit to avoid infinite loop in short length slug
            return wncms_get_unique_slug($table, $column, $length, $case);
        }
    }
}

if (!function_exists('wncms_generate_url_with_query')) {
    function wncms_generate_url_with_query($url, $params)
    {
        $query = http_build_query($params);
        if (strpos($url, '?') !== false) {
            return !empty($params) ? ($url . '&' . $query) : $url;
        } else {
            return !empty($params) ? ($url . '?' . $query) : $url;
        }
    }
}

if (!function_exists('getSeoImageType')) {
    function getSeoImageType($imagePath) {
        try {
            if (file_exists($imagePath)) {
                // Use getimagesize to fetch the image MIME type
                $imageInfo = getimagesize($imagePath);
    
                if (is_array($imageInfo) && isset($imageInfo['mime'])) {
                    return $imageInfo['mime'];
                }
            }
        } catch (\Exception $e) {
            // Handle any exceptions here, e.g., log the error
            // Log::error('Error while fetching image type: ' . $e->getMessage());
        }
    
        // If an error occurs or the image doesn't exist, return a default MIME type
        return 'image/jpeg'; // You can change this to any default value you prefer
    }
}
