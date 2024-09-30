<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\DomainAlias;
use Wncms\Models\Website;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Storage;

class WebsiteController extends Controller
{
    
    public function index(Request $request)
    {
        // TODO: Check
        // $collection = Website::query()->paginate(50);

        //! Website data
        if(isAdmin()){
            $q = Website::query();
        }else{
            $q = auth()->user()->websites();
        }
        
        if($request->keyword){
            $q->where(function($subq) use($request){
                $subq->where('domain', 'like', "%$request->keyword%")
                ->orWhere('site_name', 'like', "%$request->keyword%");
            });
        }

        $q->orderBy('id', 'desc');

        $websites = $q->paginate($request->page_size ?? 50);
        // $websites = $q->get();

        return view('wncms::backend.websites.index' , [
            'websites' => $websites,
            'page_title' => __('word.website_management')
        ]);
    }

    public function create()
    {
        $themes = wncms()->theme()->getActivatedTheme();
        $first_website = Website::count() ? false : true;

        return view('wncms::backend.websites.create',[
            'themes' => $themes,
            'page_title' => __('word.website_management'),
            'first_website' => $first_website,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'site_name' => 'required',
                'domain' => 'required',
            ],
            [
                'site_name.required' => __('word.site_name_is_required'),
                'domain.required' => __('word.domain_is_required'),
            ]
        );

        // $response = Http::post(base64_decode('aHR0cHM6Ly9saWNlbnNlLjNkYXlzZW8uY29tL2FwaS92MS93ZWJzaXRlL2F1dGg='), $request->all());
        // extract(json_decode($response->body(),true));
        // if(empty($status) || $status != 200) return back()->withErrors(['message'=>base64_decode('5o6I5qyK56K85LiN5q2j56K677yM6KuL6YeN6Kmm77yM6Iul6YeN6Kmm5b6M5LuN54S25pyq6IO95L2/55So77yM6KuLPGEgaHJlZj0iaHR0cHM6Ly9saWNlbnNlLjNkYXlzZW8uY29tIiB0YXJnZXQ9Il9ibGFuayI+55m75YWl5a6i5oi25bmz5Y+wPC9hPumHjeaWsOeUn+aIkOaOiOasiueivO+8jOWmguWVj+mhjOacquiDveino+axuu+8jOiri+iBr+e5q+WuouacjeiZleeQhu+8jDxhIGhyZWY9Imh0dHBzOi8vdC5tZS9zZWNyZXR3ZWJtYXN0ZXIiIHRhcmdldD0iX2JsYW5rIj5odHRwczovL3QubWUvc2VjcmV0d2VibWFzdGVyPC9hPg==')]);

        $existing_website = Website::where('domain', $request->domain)->first();
        if($existing_website){
            return back()->withInput()->withErrors(['message' => __('word.website_is_already_exist')]);
        }

        $website = Website::create([
            'site_name' => $request->site_name,
            'domain' => $request->domain,
            'theme' => $request->theme,
        ]);

        $website->users()->sync(auth()->id());

        if(!$website) return redirect()->route('websites.index');
        
        //handle images
        foreach(['site_logo' , 'site_favicon'] as $key){
            if($request->hasFile($key)){
                $website->clearMediaCollection($key);
                $image = $website->addMediaFromRequest($key)->toMediaCollection($key);
                $value = str_replace(env('APP_URL') , '' ,$image->getUrl());
                // $site_favicon AND $site_logo
                $$key = $value;
            }
        }
 
        //add theme options
        $default_theme_options = config('theme.'. $request->theme .'.default');

        foreach($default_theme_options ?? [] as $key => $value){
            $website->theme_options()->firstOrCreate(
                [
                    'theme' => $request->theme,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }
        
        // wncms()->cache()->flush(['websites']);
        cache()->flush();

        return redirect()->route('websites.index')->with([
            'status' => 'success',
            'message' => __('word.successfully_created')
        ]);
    }

    public function edit(Website $website)
    {
        // TODO: authorization
        // $website = wncms()->checkLicense($website);

        $themes = wncms()->theme()->getActivatedTheme();

        return view('wncms::backend.websites.edit' , [
            'page_title' => __('word.website_management'),
            'website' => $website,
            'themes' => $themes,
        ]);
    }

    public function update(Request $request, Website $website)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string|max:255',
            'body_code' => 'nullable|string|max:255',
            'analytics' => 'nullable|string|max:255',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ], [
            'required' => __('word.field_required', ['field' => ':attribute']),
            'max' => __('word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ], [
            'site_name' => __('word.site_name'),
            'theme' => __('word.theme'),
            'meta_verification' => __('word.meta_verification'),
            'site_slogan' => __('word.site_slogan'),
            'site_seo_description' => __('word.site_seo_description'),
            'site_seo_keywords' => __('word.site_seo_keywords'),
            'head_code' => __('word.head_code'),
            'body_code' => __('word.body_code'),
            'analytics' => __('word.analytics'),
            'remark' => __('word.remark'),
        ]);

        //debug to list all posssible combinations of validation rules error message
        $rules = [
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string|max:255',
            'body_code' => 'nullable|string|max:255',
            'analytics' => 'nullable|string|max:255',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ];
        
        $messages = [
            'required' => __('word.field_required', ['field' => ':attribute']),
            'max' => __('word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ];
        
        $customMessages = [
            'site_name' => __('word.site_name'),
            'theme' => __('word.theme'),
            'meta_verification' => __('word.meta_verification'),
            'site_slogan' => __('word.site_slogan'),
            'site_seo_description' => __('word.site_seo_description'),
            'site_seo_keywords' => __('word.site_seo_keywords'),
            'head_code' => __('word.head_code'),
            'body_code' => __('word.body_code'),
            'analytics' => __('word.analytics'),
            'remark' => __('word.remark'),
        ];
        
        $allMessages = [];
        
        foreach ($rules as $field => $rule) {
            // Add required messages
            if (strpos($rule, 'required') !== false) {
                $allMessages["{$field}.required"] = str_replace(':attribute', __('word.' . $field), $messages['required']);
            }
        
            // Add max messages
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = $matches[1];
                $allMessages["{$field}.max"] = str_replace([':attribute', ':max'], [__('word.' . $field), $max], $messages['max']);
            }
        }
        
        // dd($request->all());
        foreach(['site_logo', 'site_logo_white', 'site_favicon'] as $key){
            if($request->{$key . '_remove'}  == 1){
                $website->clearMediaCollection($key);
            }

            if($request->hasFile($key)){
                $website->clearMediaCollection($key);
                $website->addMediaFromRequest($key)->toMediaCollection($key);
            }
        }

        //首次安裝主題
        if($request->theme != $website->theme){
            //check if first time
            if($website->theme_options()->where('theme', $request->theme)->count() === 0){
                //add theme options
                $default_theme_options = config('theme.'. $request->theme .'.default');
                foreach($default_theme_options ?? [] as $key => $value){
                    $website->theme_options()->firstOrCreate(
                        [
                            'theme' => $request->theme,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
            };
        }

        $website->update([
            'site_name' => $request->site_name,
            'theme' => $request->theme,
            'meta_verification' => $request->meta_verification,
            'site_slogan' => $request->site_slogan,
            'site_seo_description' => $request->site_seo_description,
            'site_seo_keywords' => $request->site_seo_keywords,
            'head_code' => $request->head_code,
            'body_code' => $request->body_code,
            'analytics' => $request->analytics,
            'enabled_page_cache' => $request->enabled_page_cache ? true : false,
            'enabled_data_cache' => $request->enabled_data_cache ? true : false,
            'remark' => $request->remark,
        ]);

        $domainAliasIds = [];
        $domainAliases = explode("\r\n", $request->domain_aliases ?? '');

        foreach($domainAliases as $domain){
            $existingDomainAlias = DomainAlias::where('domain', $domain)->first();
            if($existingDomainAlias){
                $existingDomainAlias->update([
                    'website_id' => $website->id
                ]);
                $domainAliasIds[] = $existingDomainAlias->id;
            }else{
                $domainAlias = DomainAlias::create([
                    'website_id' => $website->id,
                    'domain' => $domain
                ]);
                $domainAliasIds[] = $domainAlias->id;
            }
        }

        $website->domain_aliases()->whereNotIn('domain', $domainAliases)->delete();

        //清理緩存
        wncms()->cache()->flush(['websites']); 
        
        return redirect()->route('websites.edit' , $website)->with([
            'status' => 'success',
            'message' => __('word.successfully_updated')
        ]);
    }

    public function destroy(Website $website)
    {
        return redirect()->back()->withErrors(['message'=>__('word.function_not_release_yet')]);
    }

    public function editThemeOptions(Website $website)
    {
        //get theme license
        $license = '';

        //check theme license

        //return to activate page if not activated

        
        //load theme 檔案
        $option_tabs = config('theme.'. ($website->theme ?? 'default') .'.option_tabs');

        // $current_options = $website->theme_options()->where('theme',$website->theme)->pluck('value','key')->toArray();
        
        $current_options = $website->theme_options()
            ->where('theme', $website->theme)
            ->get()
            ->mapWithKeys(function ($option) {
                // Get the translation for the current locale
                $translatedValue = $option->getTranslation('value', app()->getLocale());
                return [$option->key => $translatedValue];
            })
            ->toArray();

        $websites = wncms()->website()->getList(wheres:[
            ['theme', $website->theme],
            ['id', "<>", $website->id],
        ]);

        return view('wncms::backend.websites.theme_options' , [
            'page_title' => __('word.website_management'),
            '_website' => $website,
            'websites' => $websites,
            'option_tabs' => $option_tabs,
            'current_options' => $current_options,
        ]);
    }

    public function updateThemeOptions(Request $request, Website $website)
    {
        $shouldClearTagCache = false;
        $configs = config('theme.' . $website->theme . ".option_tabs");
        $keyToSearch = 'name';
        $options = collect($this->findParentArrays($configs, $keyToSearch));

        foreach($request->inputs as $key => $value){

            if(strpos($key, 'categories') !== false){
                $shouldClearTagCache = true;
            }

            if($request->hasFile('inputs.' . $key)){
                $website->clearMediaCollection($key);
                $image = $website->addMediaFromRequest('inputs.' . $key)->toMediaCollection($key);
                $value = str_replace(env('APP_URL') , '' ,$image->getUrl());
            }

            if(Str::endswith($key, '_remove') && $value == 1){

                $file_key = str_replace("_remove" , '', $key );
                $website->clearMediaCollection($file_key);

                //clear value
                $website->theme_options()->updateOrCreate(
                    [
                        'theme' => $website->theme,
                        'key' => $file_key,
                    ],
                    [
                        'value' => null
                    ]
                );

            }else{

                if(($options->where('name',$key)->first()['type'] ?? null) == 'tagify'){
                    $tagIds = collect(json_decode($value, true))->pluck('value')->toArray();
                    $value = implode(",", $tagIds);
                }

                //update new value
                $website->theme_options()->updateOrCreate(
                    [
                        'theme' => $website->theme,
                        'key' => $key,
                    ],
                    [
                        'value' => $value
                    ]
                );
            }

        }

        if($shouldClearTagCache){
            wncms()->cache()->flush(['tags']);
        }
            
        wncms()->cache()->flush(['websites', 'pages']);

        return redirect()->route('websites.theme.options', $website);
    }

    public function cloneThemeOptions(Request $request, Website $website)
    {
        $fromWebsite = wncms()->website()->get($request->from_website_id);
        $fromSettings = $fromWebsite->get_options();
        $shouldClearTagCache = false;

        foreach($fromSettings as $key => $value){

            if(strpos($key, 'categories') !== false){
                $shouldClearTagCache = true;
            }

            //clear value
            $website->theme_options()->updateOrCreate(
                [
                    'theme' => $website->theme,
                    'key' => $key,
                ],
                [
                    'value' => $value
                ]
            );

        }

        if($shouldClearTagCache){
            wncms()->cache()->tags(['tags'])->flush();
        }

        wncms()->cache()->flush(['page', 'websites']);

        return redirect()->route('websites.theme.options', $website);
    }

    function findParentArrays($array, $keyToSearch)
    {
        $results = [];
    
        foreach ($array as $key => $value) {
            if ($key === $keyToSearch) {
                // If the key matches, add the parent array to the results
                $results[] = $array;
            } elseif (is_array($value)) {
                // If the value is an array, recursively search within it
                $results = array_merge($results, $this->findParentArrays($value, $keyToSearch));
            }
        }
    
        return $results;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 匯入主題預設參數
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.1.7
     * @version 3.1.7
     * 
     * ----------------------------------------------------------------------------------------------------
     */
    public function importDefaultOption(Request $request)
    {
        // dd($request->all());
        if($request->confirmation != 'default'){
            return back()->withErrors(['message' => __('word.enter_default_to_confirm')]);
        }

        //get website
        $website = wncms()->website()->get($request->website_id, false);
        $count = 0;
        if($website){

            //get all default option
            $defaultThemeOptions = config("theme.{$website->theme}.default");

            
            //foreach 
            foreach($defaultThemeOptions ?? [] as $key => $value){
                //first or create
                $website->theme_options()->updateOrCreate(
                    [
                        'key' => $key,
                        'theme' => $website->theme,
                        'website_id' => $website->id,
                    ],
                    [
                        'value' => $value,
                    ],
                );

                //count
                $count++;
            }

            // dd($count, $website, $website->theme, $defaultThemeOptions);

            wncms()->cache()->flush(['websites', 'pages']);
            
            //return message
            return back()->withMessage(__('word.successfully_updated_count', ['count' => $count]));
        }else{
            return back()->withErrors(['message' => __('word.websites_is_not_found')]);
        }

    }

}
