<?php

use Wncms\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (!function_exists('wncms')) {
    function wncms(): \Wncms\Services\Wncms
    {
        static $instance = null;

        if (!$instance) {
            if (function_exists('app')) {
                $instance = app(\Wncms\Services\Wncms::class);
            } else {
                $instance = new \Wncms\Services\Wncms();
            }
        }

        return $instance;
    }
}

if (!function_exists('gss')) {
    function gss($key, $fallback = null, $fromCache = true)
    {
        try {
            return wncms()->setting()->get($key, $fallback, $fromCache);
        } catch (\Exception $e) {
            if (wncms_is_installed()) {
                logger()->error("Call gss error. key: $key");
                return $fallback;
            }
        }
    }
}

if (!function_exists('uss')) {
    function uss($key, $value)
    {
        return wncms()->setting()->update($key, $value);
    }
}

if (!function_exists('gto')) {
    function gto($key = null, $fallback = '', $locale = null, $fallbackWhenEmpty = true)
    {
        $website = wncms()->website()->get();
        if (!$website) return $fallback;

        $scope = 'theme';
        $group = $website->theme;
        $locale ??= app()->getLocale();

        $cacheKey = "theme_options_{$locale}_" . wncms()->getDomain();
        $cacheTags = ['websites'];
        $cacheTime = gss('theme_options_cache_time', 86400);

        $themeptions = wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($website, $scope, $group, $locale) {
            return $website->getOptions($scope, $group)->pluck('value', 'key')->toArray();
        });

        if ($key === null) {
            return $themeptions;
        }

        if ($fallbackWhenEmpty && array_key_exists($key, $themeptions) && empty($themeptions[$key])) {
            return $fallback;
        }

        return array_key_exists($key, $themeptions) ? $themeptions[$key] : $fallback;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin($user_id = null)
    {
        $adminRoles = ['superadmin', 'admin'];
        if (!empty($user_id)) {
            $user = wn('user')->get($user_id);
            if ($user) {
                return $user->hasRole($adminRoles);
            } else {
                return false;
            }
        }

        return auth()->user()?->hasRole($adminRoles) ? true : false;
    }
}

if (!function_exists('wncms_model_word')) {
    function wncms_model_word(string $model_name, ?string $action = null): string
    {
        return wncms()->getModelWord($model_name, $action);
    }
}


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
// if (!function_exists('wncms_get_version')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_get_version\\s*\\(" . --glob '!helpers/wncms.php'
//     Result before migration: 2 matches.
//     - resources/views/backend/pages/form-items.blade.php:47
//     - resources/views/backend/websites/theme_options.blade.php:55
//     Action: updated to wncms()->addVersion('js').
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_get_version\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use core method: wncms()->getVersion($debugType)
//     - Preferred for asset URLs: asset('...') . wncms()->addVersion('js'|'css')
//
//     if (!function_exists('wncms_get_version')) {
//         function wncms_get_version($debug_type = null)
//         {
//             if (!empty($debug_type)) {
//                 if ($debug_type == 'js') return gss('core_version') . env('APP_VERSION') . (env('JS_DEBUG') ? time() : '');
//                 if ($debug_type == 'css') return gss('core_version') . env('APP_VERSION') . (env('CSS_DEBUG') ? time() : '');
//             }
//             $app_debug =  env('APP_DEBUG') ? time() : '';
//             return gss('core_version') . $app_debug;
//         }
//     }
// }

// if (!function_exists('wncms_get_model_names')) {
//
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_get_model_names\\s*\\(" . --glob '!helpers/wncms.php'
//     Result before migration: 3 matches.
//     - src/Http/Controllers/Backend/SettingController.php:102
//     - src/Http/Controllers/Backend/SettingController.php:138
//     - resources/views/backend/parts/sidebar/model.blade.php:11
//     Action: updated to wncms()->getModelNames().
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_get_model_names\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use core method: wncms()->getModelNames()
//
//     if (!function_exists('wncms_get_model_names')) {
//         function wncms_get_model_names()
//         {
//             // Use recursive glob to get all PHP files in subdirectories
//             $appModels = collect(File::allFiles(app_path('Models')))
//                 ->map(function ($file) {
//                     // Get relative path and convert to namespace
//                     $relativePath = Str::replaceFirst(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getPathname());
//                     $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
//                     $modelName = 'App\\Models\\' . Str::replace('.php', '', $namespacePath);
//
//                     return $modelName;
//                 });
//
//             // 先取 App\Models 的 basename
//             $appModelBasenames = $appModels->map(fn($model) => class_basename($model))->unique();
//
//             $packageModels = collect(File::allFiles(dirname(__DIR__) . '/src/Models'))
//                 ->map(function ($file) {
//                     $relativePath = Str::replaceFirst(dirname(__DIR__) . '/src/Models' . DIRECTORY_SEPARATOR, '', $file->getPathname());
//                     $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
//                     return 'Wncms\\Models\\' . Str::replace('.php', '', $namespacePath);
//                 })
//                 ->filter(function ($modelName) use ($appModelBasenames) {
//                     // 如果 App\Models 裡已經有同名的 model，就略過
//                     return !$appModelBasenames->contains(class_basename($modelName));
//                 });
//
//             $files = $appModels->merge($packageModels);
//
//             $collection = $files->map(function ($modelName) {
//                 if (!class_exists($modelName)) {
//                     return null;
//                 }
//
//                 $ref = new \ReflectionClass($modelName);
//
//                 // Skip abstract models
//                 if ($ref->isAbstract()) {
//                     return null;
//                 }
//
//                 $model = new $modelName;
//
//                 return [
//                     'model_name' => class_basename($modelName),
//                     'model_key' => property_exists($model, 'modelKey') ? $model::$modelKey : null,
//                     'model_name_with_namespace' => $modelName,
//                     'priority' => property_exists($model, 'menuPriority') ? $model->menuPriority : 0,
//                     'routes' => defined($modelName . "::ROUTES") ? $modelName::ROUTES : null,
//                 ];
//             })->filter();
//
//             return $collection;
//         }
//     }
// }

// if (!function_exists('wncms_route_exists')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_route_exists\\s*\\(" src resources -g '!vendor'
//     Result before migration: 12 matches.
//     Action: updated to Laravel helper route()->has(...).
//
//     External usage search evidence (other packages only):
//     rg -n "\\bwncms_route_exists\\s*\\(" /www/wwwroot/package.wncms.cc/packages --glob '!/www/wwwroot/package.wncms.cc/packages/secretwebmaster/wncms-core/**'
//     Result before migration: 3 matches (wncms-contact-forms).
//     Action: updated to route()->has(...).
//
//     Migration syntax:
//     - Use Laravel helper: route()->has($name)
//
//     if (!function_exists('wncms_route_exists')) {
//         function wncms_route_exists($name)
//         {
//             return Route::has($name);
//         }
//     }
// }

// if (!function_exists('wncms_route_is')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_route_is\\s*\\(" resources/views/backend/parts/sidebar/member.blade.php resources/views/backend/users/parts/nav.blade.php
//     Result before migration: 9 matches.
//     Action: replaced with request()->routeIs(...) ternary expressions.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_route_is\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use Laravel request helper: request()->routeIs($pattern) ? $trueValue : $falseValue
//
//     if (!function_exists('wncms_route_is')) {
//         function wncms_route_is($route_names, $true_value = true, $false_value = false)
//         {
//             $route_names = (array)$route_names;
//
//             foreach ($route_names as $route_name) {
//                 if (request()->routeIs($route_name)) {
//                     return $true_value;
//                 }
//             }
//             return $false_value;
//         }
//     }
// }

// if (!function_exists('wncms_view')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding helpers):
//     rg -n "\\bwncms_view\\s*\\(" . --glob '!helpers/*'
//     Result: 0 matches.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_view\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use Laravel helpers directly: view($view, $params), view()->exists($view)
//
//     if (!function_exists('wncms_view')) {
//         function wncms_view($view_name, $params, $fallback_view = null)
//         {
//             if (view()->exists($view_name)) {
//                 return view($view_name, $params);
//             } elseif (view()->exists($fallback_view)) {
//                 return view($fallback_view, $params);
//             } else {
//                 abort(404);
//             }
//         }
//     }
// }

// if (!function_exists('wncms_clear_cache')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding helpers):
//     rg -n "\\bwncms_clear_cache\\s*\\(" . --glob '!helpers/*'
//     Result: 1 match (comment only).
//     - src/Providers/SettingsServiceProvider.php:34
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_clear_cache\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use cache manager directly: wncms()->cache()->forget($key, $tags)
//
//     if (!function_exists('wncms_clear_cache')) {
//         /**
//          * 清理緩存
//          * @since 1.0.0
//          * @version 1.0.0
//          * @param string|null $key
//          * @param array|string|null $tag
//          * @return boolean 成功清除 = true，不成功則 false
//          */
//         function wncms_clear_cache(string|null $cacheKey = null, array|string|null $cacheTags = null)
//         {
//             if (empty($cacheTags)) {
//                 if (empty($cacheKey)) return cache()->flush();
//                 return cache()->forget($cacheKey);
//             }
//
//             $cacheTags = is_array($cacheTags) ? $cacheTags : [$cacheTags];
//             if (empty($cacheKey)) return wncms()->cache()->tags([$cacheTags])->flush();
//             return wncms()->cache()->tags($cacheTags)->forget($cacheKey);
//         }
//     }
// }

// if (!function_exists('wncms_get_fontawesome_class')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_get_fontawesome_class\\s*\\(" src/Http/Controllers/Backend/MenuController.php
//     Result before migration: 1 match (plus 1 commented line).
//     Action: replaced with inline string normalization in MenuController.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_get_fontawesome_class\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     if (!function_exists('wncms_get_fontawesome_class')) {
//         function wncms_get_fontawesome_class($string)
//         {
//             $class = str_replace('<i class="', "", $string);
//             $class = str_replace('"></i>', "", $class);
//             return $class;
//         }
//     }
// }

if (!function_exists('wncms_is_installed')) {
    function wncms_is_installed()
    {
        if (function_exists('app') && app()->environment('testing')) {
            $testingOverride = config('wncms.testing_is_installed');
            if (!is_null($testingOverride)) {
                return (bool) $testingOverride;
            }
        }

        $filename = storage_path("installed");
        return file_exists($filename);
    }
}

// if (!function_exists('wncms_info')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_info\\s*\\(" . --glob '!helpers/wncms.php'
//     Result: 0 matches.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_info\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use Laravel logger directly: info($data)
//
//     if (!function_exists('wncms_info')) {
//         function wncms_info($data, $title = null)
//         {
//             if ($title) {
//                 info("================== $title start =================");
//             }
//             info($data);
//
//             if ($title) {
//                 info("================== $title end =================");
//             }
//         }
//     }
// }

// if (!function_exists('wncms_to_unicode')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_to_unicode\\s*\\(" . --glob '!helpers/wncms.php'
//     Result: 0 matches.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_to_unicode\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     if (!function_exists('wncms_to_unicode')) {
//         function wncms_to_unicode($string)
//         {
//             $str = mb_convert_encoding($string, 'UCS-2', 'UTF-8');
//             $arrstr = str_split($str, 2);
//             $unistr = '';
//             foreach ($arrstr as $n) {
//                 $dec = hexdec(bin2hex($n));
//                 $unistr .= '&#' . $dec . ';';
//             }
//             return $unistr;
//         }
//     }
// }

/**
 * 加載CSS文件，附加版本號，版本更新時會一併刷新
 * @link https://wncms.cc
 * @since 1.0.0
 * @version 3.0.0
 * @return string
 * @example wncms_css('themes/default/css/style.css')
 */
// if (!function_exists('wncms_css')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_css\\s*\\(" . --glob '!helpers/wncms.php'
//     Result: 0 matches.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_css\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use direct expression: asset($path) . wncms()->addVersion('css')
//
//     if (!function_exists('wncms_css')) {
//         function wncms_css($path)
//         {
//             return asset($path) . wncms()->addVersion('css');
//         }
//     }
// }

/**
 * 加載JS文件，附加版本號，版本更新時會一併刷新
 * @link https://wncms.cc
 * @since 1.0.0
 * @version 3.0.0
 * @return string
 * @example wncms_js('themes/default/css/main.js')
 */
// if (!function_exists('wncms_js')) {
//     Deprecated helper disabled in cleanup phase.
//     External usage search evidence (core repo, excluding this file):
//     rg -n "\\bwncms_js\\s*\\(" . --glob '!helpers/wncms.php'
//     Result: 0 matches.
//
//     External usage search evidence (other packages only):
//     cd /www/wwwroot/package.wncms.cc
//     rg -n "\\bwncms_js\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     Result: 0 matches.
//
//     Migration syntax:
//     - Use direct expression: asset($path) . wncms()->addVersion('js')
//
//     if (!function_exists('wncms_js')) {
//         function wncms_js($path)
//         {
//             return asset($path) . wncms()->addVersion('js');
//         }
//     }
// }




/**
 * ------------------------------------------------------------------------
 * Array helpers
 * ------------------------------------------------------------------------
 */
// if (!function_exists('wncms_array_strpos')) {
//     // This will return $key, so you should check with !== false
//     function wncms_array_strpos(string $string, array $array)
//     {
//         foreach ($array as $key => $value) {
//
//             if (strpos($string, $value) !== false) {
//                 return $key;
//             }
//         }
//         return false;
//     }
// }

// if (!function_exists('wncms_array_has_any')) {
//     //this will check if $array_2 contain any one of the value of $array_1 and return the key
//     function wncms_array_has_any(array $array_1, array $array_2)
//     {
//
//         foreach ($array_1 as $key => $value) {
//             if (in_array($value, $array_2)) {
//                 return $key;
//             }
//         }
//
//         return false;
//     }
// }

// if (!function_exists('wncms_array_has_keys')) {
//     // This will return $key, so you should check with !== false
//     function array_has_keys($array, $keys = [])
//     {
//         $count = count($keys);
//         $exist = 0;
//         foreach ($keys as $key) {
//             if (!empty($array[$key])) {
//                 $exist++;
//             }
//         }
//         return $count == $exist;
//     }
// }


/**
 * ------------------------------------------------------------------------
 * Url helpers
 * ------------------------------------------------------------------------
 */
// if (!function_exists('wncms_add_http')) {
//     function wncms_add_http($link)
//     {
//         $scheme = parse_url($link, PHP_URL_SCHEME);
//         if (empty($scheme))
//             $link = 'http://' . ltrim($link, '/');
//         return $link;
//     }
// }

// if (!function_exists('wncms_add_https')) {
//     function wncms_add_https($link)
//     {
//         $scheme = parse_url($link, PHP_URL_SCHEME);
//         if (empty($scheme))
//             $link = 'https://' . ltrim($link, '/');
//         return $link;
//     }
// }

// if (!function_exists('wncms_add_dynamic_http')) {
//     function wncms_add_dynamic_http($link)
//     {
//         $scheme = parse_url($link, PHP_URL_SCHEME);
//         if (empty($scheme))
//             $link = '//' . ltrim($link, '/');
//         return $link;
//     }
// }

// if (!function_exists('wncms_remove_http')) {
//     function wncms_remove_http($link)
//     {
//         $link = str_replace('https://', '', $link);
//         $link = str_replace('http://', '', $link);
//         $link = str_replace('www.', '', $link);
//         return $link;
//     }
// }

// if (!function_exists('wncms_get_referer')) {
//     function wncms_get_referer()
//     {
//         return parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
//     }
// }

// if (!function_exists('wncms_generate_url_with_query')) {
//     function wncms_generate_url_with_query($url, $params)
//     {
//         $query = http_build_query($params);
//         if (strpos($url, '?') !== false) {
//             return !empty($params) ? ($url . '&' . $query) : $url;
//         } else {
//             return !empty($params) ? ($url . '?' . $query) : $url;
//         }
//     }
// }

// if (!function_exists('getSeoImageType')) {
//     function getSeoImageType($imagePath)
//     {
//         try {
//             if (file_exists($imagePath)) {
//                 // Use getimagesize to fetch the image MIME type
//                 $imageInfo = getimagesize($imagePath);
//
//                 if (is_array($imageInfo) && isset($imageInfo['mime'])) {
//                     return $imageInfo['mime'];
//                 }
//             }
//         } catch (\Exception $e) {
//             // Handle any exceptions here, e.g., log the error
//             // Log::error('Error while fetching image type: ' . $e->getMessage());
//         }
//
//         // If an error occurs or the image doesn't exist, return a default MIME type
//         return 'image/jpeg'; // You can change this to any default value you prefer
//     }
// }
