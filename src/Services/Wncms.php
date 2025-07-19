<?php

namespace Wncms\Services;

use Wncms\Models\Website;
use Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @method \Wncms\Services\Managers\AdvertisementManager advertisement()
 * @method \Wncms\Services\Managers\AnalyticsManager analytics()
 * @method \Wncms\Services\Managers\BannerManager banner()
 * @method \Wncms\Services\Managers\CacheManager cache()
 * @method \Wncms\Services\Managers\ContactFormManager contactForm()
 * @method \Wncms\Services\Managers\CustomManager custom()
 * @method \Wncms\Services\Managers\MenuManager menu()
 * @method \Wncms\Services\Managers\ModelManager model()
 * @method \Wncms\Services\Managers\PageManager page()
 * @method \Wncms\Services\Managers\PostManager post()
 * @method \Wncms\Services\Managers\SettingManager systemSetting()
 * @method \Wncms\Services\Managers\TagManager tag()
 * @method \Wncms\Services\Managers\UserManager user()
 * @method \Wncms\Services\Managers\VideoManager video()
 * @method \Wncms\Services\Managers\WebsiteManager website()
 */
class Wncms
{
    public array $customProperties = [];
    public array $helpers = [];
    protected array $modelClassCache = [];

    /**
     * Get the path to the package folder
     * @param string $path
     * @return string
     */
    public function getPackagePath($path = '')
    {
        return realpath(__DIR__ . '/../' . $path);
    }

    /**
     * Get the path to the package folder
     * @param string $path
     * @return string
     */
    public function getPackageRootPath($path = '')
    {
        return realpath(__DIR__ . '/../../' . $path);
    }

    /**
     * Get current system version
     * @param string|null $debugType Suppo
     * @return string
     */
    public function getVersion($debugType = null)
    {
        if (!empty($debugType)) {

            $app_version = env('APP_VERSION') ? ("" .  env('APP_VERSION')) : '';

            if ($debugType == 'js') return gss('core_version') . $app_version . (env('JS_DEBUG') ? '.' . time() : '');
            if ($debugType == 'css') return gss('core_version') . $app_version . (env('CSS_DEBUG') ? '.' . time() : '');
        }
        $app_debug =  env('APP_DEBUG') ? time() : '';
        return gss('core_version') . $app_debug .  env('APP_VERSION');
    }

    /**
     * Append current system version
     * @param string|null $debugType Suppo
     * @return string
     */
    public function addVersion($debugType = null)
    {
        return '?v=' . $this->getVersion($debugType);
    }

    /**
     * Get domain from string
     * @param string|null $url A valid url
     * @return string
     */
    public function getDomain($url = null)
    {
        if (!$url) return str_replace('www.', '', request()->getHost());
        return !empty(parse_url($url)['host'] ?? parse_url($url)['path'])
            ? str_replace('www.', '', parse_url($url)['host'] ?? parse_url($url)['path'])
            : null;
    }

    /**
     * Check if the current url is active
     * @param string $url
     * @param string $activeClass
     * @param string|null $inActiveClass
     */
    public function isActiveUrl($url, $activeClass = 'active', $inActiveClass = null)
    {
        //trim slash if not at homepage
        if ($url != '/') {
            $url = trim($url, '/');
        }

        $activeConditions = [
            request()->url(),
            request()->path(),
            url()->current(),
        ];

        return in_array($url, $activeConditions) ? $activeClass : $inActiveClass;
    }

    /**
     * Check if the current route name matches any in the given array.
     *
     * @param array|string $routes
     * @param string $activeClass
     * @param string|null $inActiveClass
     * @return string|null
     */
    public function isActiveRoutes($routes, $activeClass = 'active', $inActiveClass = null)
    {
        $currentRoute = \Route::currentRouteName();

        if (!is_array($routes)) {
            $routes = [$routes];
        }

        return in_array($currentRoute, $routes) ? $activeClass : $inActiveClass;
    }


    /**
     * fnmatch for array
     * @param array $patterns
     * @param string $path
     */
    public function array_fnmatch($patterns, $path)
    {
        foreach ($patterns as $pattern) {
            if (fnmatch("*/" . $pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get domain from string
     * @param string|null $string String containing url that you would like to extract 
     * @return string
     */
    public function getDomainFromString($string, $includePort = true, $preserveWWW = false)
    {
        //remove blank
        $string = trim($string);
        $array = explode(" ", $string);

        foreach ($array as $stringToParse) {

            // $pattern = '/https?:\/\/\S+/';
            $pattern = '/\b[a-zA-Z0-9-]+\.[a-zA-Z0-9-:.]+\b/';

            preg_match($pattern, $stringToParse, $matches);

            if (!empty($matches[0])) {

                $url = $matches[0];

                $urlData = parse_url($url);

                if (!empty($urlData['host']) && !empty($urlData['port']) && $includePort) {
                    $result = $urlData['host'] . ":" . $urlData['port'];
                };

                if (!empty($urlData['host']) && (empty($urlData['port']) || empty($includePort))) {
                    $result = $urlData['host'];
                };

                if (!empty($urlData['path'])) {
                    $result = $urlData['path'];
                }

                //output result
                if (!empty($result)) {
                    return $preserveWWW ? $result : str_replace("www.", '', $result);
                }
            }
        }

        return null;
    }

    /**
     * 獲取當前用戶語言
     * @return string
     */
    public function getLocale(): string
    {
        return LaravelLocalization::getCurrentLocale();
    }

    /**
     * 檢查當前語言是否預設語言
     * @return string
     */
    public function isDefaultLocale(): string
    {
        return LaravelLocalization::getCurrentLocale() == LaravelLocalization::getDefaultLocale();
    }

    /**
     * 獲取當前用戶語言名稱
     * @return string
     */
    public function getLocaleName(): string
    {
        return LaravelLocalization::getCurrentLocaleNative();
    }

    /**
     * 獲取當前用戶語言列表
     * @return array
     */
    public function getLocaleList()
    {
        return LaravelLocalization::getSupportedLocales();
    }

    /**
     * Get route by parameters
     * @param string $name Route name
     * @param array $params Route parameters
     * @param boolean $isFullPath Return full path or not
     * @param string|null $domain Domain name
     * @return string
     */
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

    /**
     * Paginate collection with limit
     * @param LengthAwarePaginator $collection Collection to paginate
     * @param integer $pageSize Page size
     * @param integer $limit Limit of total items
     * @param integer $currentPage Current page
     * @param string $pageName Page name
     * @return LengthAwarePaginator
     * 
     * TODO: Monitor performance
     */
    public function paginateWithLimit(LengthAwarePaginator $collection, $pageSize = null, $limit = null, $currentPage = null, $pageName = 'page')
    {
        if (empty($pageSize)) return $collection;

        $currentPage ??= LengthAwarePaginator::resolveCurrentPage($pageName);

        //if total collection item exceed the limit, set the limit as total. Otherwise use original total item count as total
        if (!empty($limit) && $collection->total() > $limit) {
            $total = $limit;
        } else {
            $total = $collection->total();
        }

        //if accessing pages exceed allowed pages, empty the collection
        if ($currentPage > ceil($limit / $pageSize)) {
            $items = collect([]);
        } else {
            //if on last page, take the remaining items
            if ($currentPage == ceil($limit / $pageSize)) {
                // Calculate remaining items
                $start = ($currentPage - 1) * $pageSize;
                $remainingItems = $total - $start;

                // Take limited count of items for the last page
                $items = $collection->take($remainingItems);
            } else {
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
     * Get unique slug of a model table 
     * @param string $table Table name
     * @param string $column Column name. Defaul value is "slug"
     * @param string $length Length of slug to be generate. Default value is 8 
     * @param string|null $case "upper" = Upper case, "lower" = Lower case, null = Mixed
     * @return string
     */
    public function getUniqueSlug($table, $column = 'slug', $length = 8, $case = 'lower', $prefix = '')
    {
        do {
            $slug = str()->random($length);

            if ($case == 'upper') {
                $slug = $prefix . strtoupper($slug);
            } elseif ($case == 'lower') {
                $slug = $prefix . strtolower($slug);
            } else {
                $slug = $prefix . $slug;
            }

            $duplicated = DB::table($table)->where($column, $slug)->exists();
        } while ($duplicated);

        return $slug;
    }

    /**
     * Get all arguments of a function
     * @param string|Closure $func Function name or closure
     * @param array $func_get_args Function arguments
     * @return array
     */
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
     * Check if selected
     * @param App/Models/Website $_website
     * @return boolean
     */
    public function isSelectedWebsite($_website)
    {
        if (
            //當前篩選器
            $_website->id == request()->website ||

            //Dashboard Session
            (!request()->has('website') && $_website->id == session('selected_website_id')) ||

            //當前域名
            (!request()->has('website') && empty(session('selected_website_id')) && $this->website()->get()?->id == $_website->id)
        ) {
            return true;
        }
    }

    /**
     * 檢查數值是否tagify數據
     * 
     * @param string $string 	- 參數描述
     * @return boolean
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
     * 獲取所有Wncms\Models 下的模組
     * 
     * @param string|null $cacheKey 	- 參數描述
     * @return Collection
     */
    public function getModelNames()
    {
        $path = app_path('Models') . '/*.php';
        $collection = collect(glob($path))->map(function ($file) {
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

    /**
     * Resolve and instantiate a model by key.
     *
     * @param string $key Logical model key (e.g., 'post', 'tag', 'link')
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel(string $key): Model
    {
        $class = $this->getModelClass($key);
        return new $class;
    }

    /**
     * Resolve the fully qualified model class name by key.
     *
     * Resolution priority:
     * 1. config('wncms.models.{key}')
     * 2. App\Models\{StudlyKey}
     * 3. Wncms\Models\{StudlyKey}
     *
     * @param string $key
     * @return string
     *
     * @throws \RuntimeException If no matching model class is found.
     */
    public function getModelClass(string $key): string
    {
        if (isset($this->modelClassCache[$key])) {
            return $this->modelClassCache[$key];
        }

        $studlyKey = str($key)->studly();
        
        $configModel = config("wncms.models.{$key}");
        if ($configModel && class_exists($configModel)) {
            return $this->modelClassCache[$key] = $configModel;
        }

        $appModel = "App\\Models\\{$studlyKey}";
        if (class_exists($appModel)) {
            return $this->modelClassCache[$key] = $appModel;
        }

        $wncmsModel = "Wncms\\Models\\{$studlyKey}";
        if (class_exists($wncmsModel)) {
            return $this->modelClassCache[$key] = $wncmsModel;
        }

        throw new \RuntimeException("Model class not found for key [{$key}].");
    }


    /**
     * Check if the website is licensed
     * @param Website $website
     * @return Website|Redirect
     * 
     * TODO: Not implemented yet
     */
    public function checkLicense(Website $website)
    {
        $url = "https://api.wncms.cc/api/v1/license/check";
        $cacheKey = "website_check";
        $cacheTag = ["websites"];

        $result = wncms()->cache()->tags($cacheTag)->remember($cacheKey, 60 * 60 * 24, function () use ($url, $website) {
            $response = Http::post($url, [
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
     * Return a view if it exists, or fall back to another view or route.
     *
     * @param string $name
     * @param array $params
     * @param string|null $fallback
     * @param string|null $fallbackRoute
     */
    public function view(string $name, array $params = [], ?string $fallback = null, ?string $fallbackRoute = null)
    {
        if (view()->exists($name)) {
            return view($name, $params);
        }

        if ($fallback && view()->exists($fallback)) {
            return view($fallback, $params);
        }

        if ($fallbackRoute && route($fallbackRoute, [], false)) {
            return redirect()->route($fallbackRoute);
        }

        wncms()->log("View not found: {$name}");

        return redirect()->route('frontend.pages.home');
    }


    /**
     * 調用其他Manager Class的方法
     * 
     * @param string|null $helper
     *      預設值: -
     *      描述: 調用Wncms\Manager目錄下的模組
     *      命名規則: snake_case的model名稱，例如 post，collect_source，系統自動轉換為Post CollectSource
     * 
     * @param array|string|null $args 參數
     *      預設值: null
     *      描述: 傳入Manager方法的參數
     *      例子: wncms()->posts()->getPost(12, true) 中的 $id, $isPublished
     * 
     * @return mixed The result of the helper method call.
     */
    public function __call($helper, $args)
    {
        if (array_key_exists($helper, $this->helpers)) {
            return $this->helpers[$helper];
        }

        // load custom manager
        $class = 'App\Services\Managers\\' . ucfirst(str($helper)->camel()) . "Manager";
        if (class_exists($class)) {
            return new $class($this, ...$args);
        }

        $class = 'Wncms\Services\Managers\\' . ucfirst(str($helper)->camel()) . "Manager";
        if (class_exists($class)) {
            return new $class($this, ...$args);

            // $this->helpers[$helper] = new $class($this, ...$args);
            // return $this->helpers[$helper];
        }

        throw new \RuntimeException("Unable to resolve class or method for \$helper {$helper}. Please check your configuration or naming.");
    }

    /**
     * Call __get magic method if the property doesn't exist
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        // Check if the property exists in the $data array
        if (array_key_exists($name, $this->customProperties)) {
            return $this->customProperties[$name];
        } else {
            return null; // Return null if the property doesn't exist.
        }
    }

    /**
     * Call __set magic method if the property doesn't exist
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->customProperties[$name] = $value;
    }

    public function log(string $message, string $level = 'debug'): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) continue;

            if (!str_contains($frame['file'], '/vendor/')) {
                $file = $frame['file'];
                $line = $frame['line'] ?? '?';
                \Log::log($level, "{$message} ({$file}:{$line})");
                return;
            }
        }

        \Log::log($level, "{$message} (unknown file)");
    }
}
