<?php

namespace Wncms\Services\Wncms;

use Wncms\Models\Website;
use Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LaravelLocalization;

/**
 * @method \Wncms\Services\Wncms\Helpers\AdvertisementHelper advertisement()
 * @method \Wncms\Services\Wncms\Helpers\AnalyticsHelper analytics()
 * @method \Wncms\Services\Wncms\Helpers\BannerHelper banner()
 * @method \Wncms\Services\Wncms\Helpers\CacheHelper cache()
 * @method \Wncms\Services\Wncms\Helpers\ContactFormHelper contactForm()
 * @method \Wncms\Services\Wncms\Helpers\CustomHelper custom()
 * @method \Wncms\Services\Wncms\Helpers\MenuHelper menu()
 * @method \Wncms\Services\Wncms\Helpers\ModelHelper model()
 * @method \Wncms\Services\Wncms\Helpers\PageHelper page()
 * @method \Wncms\Services\Wncms\Helpers\PostHelper post()
 * @method \Wncms\Services\Wncms\Helpers\SettingHelper systemSetting()
 * @method \Wncms\Services\Wncms\Helpers\TagHelper tag()
 * @method \Wncms\Services\Wncms\Helpers\UserHelper user()
 * @method \Wncms\Services\Wncms\Helpers\VideoHelper video()
 * @method \Wncms\Services\Wncms\Helpers\WebsiteHelper website()
 */
class Wncms
{
    public $customProperties = [];
    public $helpers = [];
    
    /**
     * ----------------------------------------------------------------------------------------------------
     * Get current system version
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $debugType Suppo
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function getVersion($debugType = null)
    {
        if (!empty($debugType)) {

            $app_version = env('APP_VERSION') ? ("" .  env('APP_VERSION')) : '';

            if ($debugType == 'js') return gss('version') . $app_version . (env('JS_DEBUG') ? '.' . time() : '');
            if ($debugType == 'css') return gss('version') . $app_version . (env('CSS_DEBUG') ? '.' . time() : '');
        }
        $app_debug =  env('APP_DEBUG') ? time() : '';
        return gss('version') . $app_debug .  env('APP_VERSION');
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Append current system version
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $debugType Suppo
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function addVersion($debugType = null)
    {
        return '?v=' . $this->getVersion($debugType);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get domain from string
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $url A valid url
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function getDomain($url = null)
    {
        if (!$url) return str_replace('www.', '', request()->getHost());
        return !empty(parse_url($url)['host'] ?? parse_url($url)['path'])
            ? str_replace('www.', '', parse_url($url)['host'] ?? parse_url($url)['path'])
            : null;
    }

    public function isActiveUrl($url, $activeClass = 'active', $inActiveClass = null)
    {
        //trim slash if not at homepage
        if($url != '/'){
            $url = trim($url, '/');
        }

        $activeConditions = [
            request()->url(),
            request()->path(),
            url()->current(),
        ];

        return in_array($url, $activeConditions) ? $activeClass : $inActiveClass;

    }

    public function array_fnmatch($patterns, $path)
    {
        foreach($patterns as $pattern){
            if(fnmatch("*/" . $pattern, $path)){
                return true;
            }
        }

        return false;
    }

    /**
     * !----------------------------------------------------------------------------------------------------
     * ! Handling Urls
     * !----------------------------------------------------------------------------------------------------
     */

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get domain from string
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.2
     * @param string|null $string String containing url that you would like to extract 
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function getDomainFromString($string, $includePort = true, $preserveWWW = false)
    {
        //remove blank
        $string = trim($string);
        $array = explode(" ", $string);

        foreach($array as $stringToParse){

            // $pattern = '/https?:\/\/\S+/';
            $pattern = '/\b[a-zA-Z0-9-]+\.[a-zA-Z0-9-:.]+\b/';

            preg_match($pattern, $stringToParse, $matches);

            if(!empty($matches[0])){
    
                $url = $matches[0];
    
                $urlData = parse_url($url);
    
                if(!empty($urlData['host']) && !empty($urlData['port']) && $includePort){
                    $result = $urlData['host'] . ":" . $urlData['port'];
                };
    
                if(!empty($urlData['host']) && (empty($urlData['port']) || empty($includePort))){
                    $result = $urlData['host'];
                };
    
                if(!empty($urlData['path'])){
                    $result = $urlData['path'];
                }

                //output result
                if(!empty($result)){
                    return $preserveWWW ? $result : str_replace("www.", '', $result);
                }
            }
        }
        
        return null;
    }

    /**
     * 獲取當前用戶語言
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @return string
     */

    public function getLocale(): string
    {
        return LaravelLocalization::getCurrentLocale();
    }

    /**
     * 檢查當前語言是否預設語言
     * @link https://wncms.cc
     * @since 4.0.0
     * @version 4.0.0
     * @return string
     */

     public function isDefaultLocale(): string
     {
         return LaravelLocalization::getCurrentLocale() == LaravelLocalization::getDefaultLocale();
     }

    /**
     * 獲取當前用戶語言名稱
     * @link https://wncms.cc
     * @since 3.1.15
     * @version 3.1.15
     * @return string
     */
    public function getLocaleName(): string
    {
        return LaravelLocalization::getCurrentLocaleNative();
    }

    /**
     * 獲取當前用戶語言列表
     * @link https://wncms.cc
     * @since 3.1.15
     * @version 3.1.15
     * @return array
     */
    public function getLocaleList()
    {
        return LaravelLocalization::getSupportedLocales();
    }

    public function getRoute($name, $params = [], $isFullPath = true, $domain = null)
    {
        if (Route::has($name)) {
            if (!empty($domain)) {
                return wncms_add_https($domain) . route($name, $params, false);
            } else {
                return route($name, $params, $isFullPath);
            }
        }
    }

    public function paginateWithLimit(LengthAwarePaginator $collection, $pageSize = null , $limit = null, $currentPage = null, $pageName = 'page')
    {
        if(empty($pageSize)) return $collection;

        $currentPage ??= LengthAwarePaginator::resolveCurrentPage($pageName);

        //if total collection item exceed the limit, set the limit as total. Otherwise use original total item count as total
        if(!empty($limit) && $collection->total() > $limit){
            $total = $limit;
        }else{
            $total = $collection->total();
        }

        //if accessing pages exceed allowed pages, empty the collection
        if($currentPage > ceil($limit / $pageSize)){
            $items = collect([]);
        }else{
            //if on last page, take the remaining items
            if($currentPage == ceil($limit / $pageSize)){
                // Calculate remaining items
                $start = ($currentPage - 1) * $pageSize;
                $remainingItems = $total - $start;

                // Take limited count of items for the last page
                $items = $collection->take($remainingItems);
            }else{
                $items = $collection->take($pageSize);
            }
        }

        return new LengthAwarePaginator(
            $items,
            $total,
            $pageSize,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get unique slug of a model table 
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param string $table Table name
     * @param string $column Column name. Defaul value is "slug"
     * @param string $length Length of slug to be generate. Default value is 8 
     * @param string|null $case "upper" = Upper case, "lower" = Lower case, null = Mixed
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function getUniqueSlug($table, $column = 'slug', $length = 8, $case = 'lower')
    {
        do {
            $slug = str()->random($length);

            if ($case == 'upper') {
                $slug = strtoupper($slug);
            } elseif ($case == 'lower') {
                $slug = strtolower($slug);
            }

            $duplicated = DB::table($table)->where($column, $slug)->exists();
        } while ($duplicated);

        return $slug;
    }

    public function getAllArgs($func, $func_get_args = [])
    {
        if ((is_string($func) && function_exists($func)) || $func instanceof Closure) {
            $ref = new \ReflectionFunction($func);
        } else if (is_string($func) && !call_user_func_array('method_exists', explode('::', $func))) {
            return $func_get_args;
        } else {
            $ref = new \ReflectionMethod($func);
        }

        foreach ($ref->getParameters() as $key => $param) {

            if (!isset($func_get_args[$key]) && $param->isDefaultValueAvailable()) {
                $func_get_args[$key] = $param->getDefaultValue();
            }
        }

        return $func_get_args;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 檢查傳入的網站變量是否當前所選擇的網站
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.1.12
     * @version 3.1.12
     * 
     * @param App/Models/Website $_website
     * @return boolean
     * ----------------------------------------------------------------------------------------------------
     */
    public function isSelectedWebsite($_website)
    {
        if(
            //當前篩選器
            $_website->id == request()->website ||

            //Dashboard Session
            (!request()->has('website') && $_website->id == session('selected_website_id')) ||

            //當前域名
            (!request()->has('website') && empty(session('selected_website_id')) && $this->website()->get()?->id == $_website->id)
        ){
            return true;
        }
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 檢查數值是否tagify數據
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.1.13
     * @version 3.1.13
     * 
     * @param string $string 	- 參數描述
     * @return boolean
     * ----------------------------------------------------------------------------------------------------
     */
    public static function isValidTagifyJson($string)
    {
        try {
            $data = json_decode($string, true);

            if (!is_array($data)) {
                return false;
            }

            foreach ($data as $item) {
                if (!is_array($item) || !array_key_exists('value', $item) || !array_key_exists('name', $item)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            // JSON decoding failed
            return false;
        }
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 獲取所有Wncms\Models 下的模組
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param string|null $cacheKey 	- 參數描述
     * @return Collection
     * ----------------------------------------------------------------------------------------------------
     */
    public function getModelNames()
    {
        $path = app_path('Models') . '/*.php';
        $collection = collect(glob($path))->map(function($file){
            $modelName = "\Wncms\Models\\" . basename($file, '.php');
            $model = new $modelName;
            return [
                'name' => basename($file, '.php'),
                'priority' => $model->menuPriority ?? 0,
                'routes' => defined(get_class($model) . "::ROUTES") ? $model::ROUTES : null,
            ];
        });

        return $collection;
    }

    public function checkLicense(Website $website)
    {
        $u = "aHR0cHM6Ly9saWNlbnNlLjNkYXlzZW8uY29tL2FwaS92MS93ZWJzaXRlL2NoZWNr";
        $cacheKey = "website_check";
        $cacheTag = ["websites"];

        $result = wncms()->cache()->tags($cacheTag)->remember($cacheKey, 60 * 60 * 24, function () use ($u, $website) {
            $response = Http::post(base64_decode($u), [
                'domain' => $website->domain,
                'license' => $website->license,
            ]);
            return json_decode($response->body(), true);
        });

        if (!empty($result['result'])) {
            return $website;
        } else {
            return redirect()->route('websites.create')->send();
        }
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 調用其他Helper Class的方法
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param string|null $helper
     *      預設值: -
     *      描述: 調用Wncms\Helper目錄下的模組
     *      命名規則: snake_case的model名稱，例如 post，collect_source，系統自動轉換為Post CollectSource
     * 
     * @param array|string|null $args 參數
     *      預設值: null
     *      描述: 傳入Helper方法的參數
     *      例子: wncms()->posts()->getPost(12, true) 中的 $id, $isPublished
     * 
     * @return mixed The result of the helper method call.
     * ----------------------------------------------------------------------------------------------------
     */
    public function __call($helper, $args)
    {
        if(array_key_exists($helper, $this->helpers)){
            return $this->helpers[$helper];
        }
        
        $class = 'Wncms\Services\Wncms\Helpers\\' . ucfirst(str($helper)->camel()) . "Helper";
        
        if (class_exists($class)) {
            return new $class($this, ...$args);

            // $this->helpers[$helper] = new $class($this, ...$args);
            // return $this->helpers[$helper];
        }

        throw new \RuntimeException("Class {$class} does not exist");
    }

    public function __get($name)
    {
        // Check if the property exists in the $data array
        if (array_key_exists($name, $this->customProperties)) {
            return $this->customProperties[$name];
        } else {
            return null; // Return null if the property doesn't exist.
        }
    }
    
    // Method to set properties
    public function __set($name, $value)
    {
        $this->customProperties[$name] = $value;
    }
}
