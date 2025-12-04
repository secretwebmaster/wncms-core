<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class WebsiteController extends BackendController
{
    public function index(Request $request)
    {
        if (isAdmin()) {
            $q = $this->modelClass::query();
        } else {
            $q = auth()->user()->websites();
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
                $subq->where('domain', 'like', "%$request->keyword%")
                    ->orWhere('site_name', 'like', "%$request->keyword%");
            });
        }

        $q->with(['domain_aliases', 'translations', 'media']);

        $q->orderBy('id', 'desc');

        $websites = $q->paginate($request->page_size ?? 50);

        return $this->view('backend.websites.index', [
            'websites' => $websites,
            'page_title' => __('wncms::word.website_management'),
            'hideToolbarWebsiteFiller' => true,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $website = $this->modelClass::find($id);
            if (!$website) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $website = new $this->modelClass;
        }

        $themes = wncms()->theme()->getThemes();
        $websiteCount = $this->modelClass::count();

        return $this->view('backend.websites.create', [
            'themes' => $themes,
            'page_title' => __('wncms::word.website_management'),
            'websiteCount' => $websiteCount,
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
                'site_name.required' => __('wncms::word.site_name_is_required'),
                'domain.required' => __('wncms::word.domain_is_required'),
            ]
        );

        $existing_website = $this->modelClass::where('domain', $request->domain)->first();
        if ($existing_website) {
            return back()->withInput()->withErrors(['message' => __('wncms::word.website_is_already_exist')]);
        }

        $website = $this->modelClass::create([
            'site_name' => $request->site_name,
            'domain' => $request->domain,
            'theme' => $request->theme,
        ]);

        $website->users()->sync(auth()->id());

        if (!$website) return redirect()->route('websites.index');

        //handle images
        foreach (['site_logo', 'site_favicon'] as $key) {
            if ($request->hasFile($key)) {
                $website->clearMediaCollection($key);
                $image = $website->addMediaFromRequest($key)->toMediaCollection($key);
                $value = str_replace(env('APP_URL'), '', $image->getUrl());
                // $site_favicon AND $site_logo
                $$key = $value;
            }
        }

        //add theme options
        $default_theme_options = config('theme.' . $request->theme . '.default');

        foreach ($default_theme_options ?? [] as $key => $value) {
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

        $this->flush();

        return redirect()->route('websites.index')->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created')
        ]);
    }

    public function edit($id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $themes = wncms()->theme()->getThemes();

        // dd($themes);

        return $this->view('backend.websites.edit', [
            'page_title' => __('wncms::word.website_management'),
            'website' => $website,
            'themes' => $themes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string',
            'body_code' => 'nullable|string',
            'analytics' => 'nullable|string',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ], [
            'required' => __('wncms::word.field_required', ['field' => ':attribute']),
            'max' => __('wncms::word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ], [
            'site_name' => __('wncms::word.site_name'),
            'theme' => __('wncms::word.theme'),
            'meta_verification' => __('wncms::word.meta_verification'),
            'site_slogan' => __('wncms::word.site_slogan'),
            'site_seo_description' => __('wncms::word.site_seo_description'),
            'site_seo_keywords' => __('wncms::word.site_seo_keywords'),
            'head_code' => __('wncms::word.head_code'),
            'body_code' => __('wncms::word.body_code'),
            'analytics' => __('wncms::word.analytics'),
            'remark' => __('wncms::word.remark'),
        ]);

        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        //debug to list all posssible combinations of validation rules error message
        $rules = [
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string',
            'body_code' => 'nullable|string',
            'analytics' => 'nullable|string',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ];

        $messages = [
            'required' => __('wncms::word.field_required', ['field' => ':attribute']),
            'max' => __('wncms::word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ];

        $customMessages = [
            'site_name' => __('wncms::word.site_name'),
            'theme' => __('wncms::word.theme'),
            'meta_verification' => __('wncms::word.meta_verification'),
            'site_slogan' => __('wncms::word.site_slogan'),
            'site_seo_description' => __('wncms::word.site_seo_description'),
            'site_seo_keywords' => __('wncms::word.site_seo_keywords'),
            'head_code' => __('wncms::word.head_code'),
            'body_code' => __('wncms::word.body_code'),
            'analytics' => __('wncms::word.analytics'),
            'remark' => __('wncms::word.remark'),
        ];

        $allMessages = [];

        foreach ($rules as $field => $rule) {
            // Add required messages
            if (strpos($rule, 'required') !== false) {
                $allMessages["{$field}.required"] = str_replace(':attribute', __('wncms::word.' . $field), $messages['required']);
            }

            // Add max messages
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = $matches[1];
                $allMessages["{$field}.max"] = str_replace([':attribute', ':max'], [__('wncms::word.' . $field), $max], $messages['max']);
            }
        }

        // dd($request->all());
        foreach (['site_logo', 'site_logo_white', 'site_favicon'] as $key) {
            if ($request->{$key . '_remove'}  == 1) {
                $website->clearMediaCollection($key);
            }

            if ($request->hasFile($key)) {
                $website->clearMediaCollection($key);
                $website->addMediaFromRequest($key)->toMediaCollection($key);
            }
        }

        //首次安裝主題
        if ($request->theme != $website->theme) {
            //check if first time
            if ($website->theme_options()->where('theme', $request->theme)->count() === 0) {
                //add theme options
                $default_theme_options = config('theme.' . $request->theme . '.default');
                foreach ($default_theme_options ?? [] as $key => $value) {
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
            'homepage' => $request->homepage,
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
            'domain' => $request->domain,
        ]);

        $domainAliasIds = [];
        $domainAliases = explode("\r\n", $request->domain_aliases ?? '');

        foreach ($domainAliases as $domain) {
            $existingDomainAlias = wncms()->getModelClass('domain_alias')::where('domain', $domain)->first();
            if ($existingDomainAlias) {
                $existingDomainAlias->update([
                    'website_id' => $website->id
                ]);
                $domainAliasIds[] = $existingDomainAlias->id;
            } else {
                $domainAlias = wncms()->getModelClass('domain_alias')::create([
                    'website_id' => $website->id,
                    'domain' => $domain
                ]);
                $domainAliasIds[] = $domainAlias->id;
            }
        }

        $website->domain_aliases()->whereNotIn('domain', $domainAliases)->delete();

        //清理緩存
        $this->flush();

        return redirect()->route('websites.edit', $website)->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated')
        ]);
    }

    public function editThemeOptions($id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        // load option config
        $option_tabs = config('theme.' . ($website->theme ?? 'default') . '.option_tabs');

        // flatten all option definitions
        $configs = $option_tabs;
        $keyToSearch = 'name';
        $allOptions = collect($this->findParentArrays($configs, $keyToSearch));

        // load stored values
        $current_options = $website->theme_options()
            ->where('theme', $website->theme)
            ->get()
            ->mapWithKeys(function ($option) use ($allOptions) {

                // get translated raw value
                $value = $option->getTranslation('value', app()->getLocale());

                // find field config
                $config = $allOptions->firstWhere('name', $option->key);
                $type = $config['type'] ?? null;

                // decode gallery json
                if ($type === 'gallery' && is_string($value)) {

                    // decode json to array
                    $decoded = json_decode($value, true);

                    // ensure proper structure for inputs.blade.php
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

                        $clean = [];

                        foreach ($decoded as $item) {
                            if (!is_array($item)) continue;

                            $clean[] = [
                                'image' => $item['image'] ?? '',
                                'text'  => $item['text'] ?? '',
                                'url'   => $item['url'] ?? '',
                            ];
                        }

                        $value = $clean;
                    }
                }

                return [$option->key => $value];
            })
            ->toArray();

        // load other websites with same theme
        $websites = wncms()->website()->getList(wheres: [
            ['theme', $website->theme],
            ['id', "<>", $website->id],
        ]);

        // dd($current_options);

        return $this->view('backend.websites.theme_options', [
            'page_title' => __('wncms::word.theme_options') . " #" . $website->id,
            '_website' => $website,
            'websites' => $websites,
            'option_tabs' => $option_tabs,
            'current_options' => $current_options,
        ]);
    }

    public function updateThemeOptions(Request $request, $id)
    {
        // dd($request->all());
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $shouldClearTagCache = false;

        $configs = config('theme.' . $website->theme . ".option_tabs");
        $keyToSearch = 'name';
        $options = collect($this->findParentArrays($configs, $keyToSearch));

        $inputs = $request->input('inputs', []);

        foreach ($inputs as $key => $group) {

            // find field config
            $config = $options->firstWhere('name', $key);
            $type = $config['type'] ?? null;

            if (strpos($key, 'categories') !== false) {
                $shouldClearTagCache = true;
            }

            // gallery
            if ($type === 'gallery') {

                // load flags
                $hasText = !empty($config['has_text']);
                $hasUrl  = !empty($config['has_url']);

                // load existing non-file values
                $images = $group['image'] ?? [];
                $texts  = $group['text']  ?? [];
                $urls   = $group['url']   ?? [];
                $remove = $group['remove'] ?? [];

                // build existing items
                $existing = [];
                foreach ($images as $i => $img) {
                    if (!$img) continue;

                    $existing[] = [
                        'image' => $img,
                        'text'  => $hasText ? ($texts[$i] ?? '') : '',
                        'url'   => $hasUrl  ? ($urls[$i]  ?? '') : '',
                    ];
                }

                // apply remove flags
                $kept = [];
                foreach ($existing as $i => $item) {
                    if (!isset($remove[$i]) || (int)$remove[$i] === 0) {
                        $kept[] = $item;
                    }
                }

                // delete removed spatie media
                foreach ($website->getMedia($key) as $media) {
                    $url = parse_url($media->getUrl(), PHP_URL_PATH);

                    $exists = collect($kept)->contains(function ($x) use ($url) {
                        return $x['image'] === $url;
                    });

                    if (!$exists) {
                        $media->delete();
                    }
                }

                // load uploaded files from request
                $files = $request->file("inputs.{$key}.file") ?? [];
                if (!is_array($files)) {
                    $files = $files ? [$files] : [];
                }
                
                // append uploaded images
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $media = $website->addMedia($file)->toMediaCollection($key);
                        $url = parse_url($media->getUrl(), PHP_URL_PATH);

                        $kept[] = [
                            'image' => $url,
                            'text'  => $hasText ? '' : '',
                            'url'   => $hasUrl  ? '' : '',
                        ];
                    }
                }

                // normalize structure
                $normalized = collect($kept)
                    ->filter(fn($x) => !empty($x['image']))
                    ->values()
                    ->toArray();

                // save option
                $website->theme_options()->updateOrCreate(
                    [
                        'theme' => $website->theme,
                        'key'   => $key,
                    ],
                    [
                        'value' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                    ]
                );

                continue;
            }


            // tagify
            if ($type === 'tagify' && is_string($group) && wncms()->isValidTagifyJson($group)) {
                $ids = collect(json_decode($group, true))->pluck('value')->toArray();
                $group = implode(',', $ids);
            }

            // normal array field
            if (is_array($group)) {
                $group = json_encode($group, JSON_UNESCAPED_UNICODE);
            }

            // save option
            $website->theme_options()->updateOrCreate(
                [
                    'theme' => $website->theme,
                    'key' => $key,
                ],
                [
                    'value' => $group,
                ]
            );
        }

        if ($shouldClearTagCache) {
            wncms()->cache()->flush(['tags']);
        }

        wncms()->cache()->flush(['websites', 'pages']);

        return redirect()->route('websites.theme.options', $website);
    }

    public function cloneThemeOptions(Request $request, $id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $fromWebsite = wncms()->website()->get($request->from_website_id);
        $fromSettings = $fromWebsite->get_options();
        $shouldClearTagCache = false;

        foreach ($fromSettings as $key => $value) {

            if (strpos($key, 'categories') !== false) {
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

        if ($shouldClearTagCache) {
            wncms()->cache()->tags(['tags'])->flush();
        }

        wncms()->cache()->flush(['page', 'websites']);

        return redirect()->route('websites.theme.options', $website);
    }

    public function findParentArrays($array, $keyToSearch)
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
        if ($request->confirmation != 'default') {
            return back()->withErrors(['message' => __('wncms::word.enter_default_to_confirm')]);
        }

        //get website
        $website = wncms()->website()->get($request->website_id, false);
        $count = 0;
        if ($website) {

            //get all default option
            $defaultThemeOptions = config("theme.{$website->theme}.default");


            //foreach 
            foreach ($defaultThemeOptions ?? [] as $key => $value) {
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
            return back()->withMessage(__('wncms::word.successfully_updated_count', ['count' => $count]));
        } else {
            return back()->withErrors(['message' => __('wncms::word.websites_is_not_found')]);
        }
    }
}
