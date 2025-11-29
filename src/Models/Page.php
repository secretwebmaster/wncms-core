<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Secretwebmaster\LaravelOptionable\Traits\HasOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;
use Illuminate\Http\Request;

class Page extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;
    use HasOptions;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'page';

    protected $guarded = [];

    protected static array $tagMetas = [
        [
            'key'   => 'page_category',
            'short' => 'category',
            'route' => '',
        ],
        [
            'key'   => 'page_tag',
            'short' => 'tag',
            'route' => '',
        ],
    ];

    protected $with = ['options'];

    protected $translatable = ['title', 'content'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-file-lines'
    ];

    public const OPTIONS = [
        'inherit_theme_layout',
        'include_header',
        'include_footer',
    ];

    public const SORTS = [
        'sort',
        'traffic_recently',
        'traffic_today',
        'traffic_yesterday',
        'traffic_week',
        'traffic_month',
        'traffic_total',
        'published_at',
        'expired_at',
        'created_at',
        'updated_at',
    ];

    public const STATUSES = [
        'published',
        'drafted',
        'trashed',
    ];

    public const TYPES = [
        'plain',
        'builder',
        'template',
    ];

    public const VISIBILITIES = [
        'public',
        'member',
        'admin',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('page_thumbnail')->singleFile();
        $this->addMediaCollection('page_content');
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function comments()
    {
        return $this->morphMany(wncms()->getModelClass('comment'), 'commentable');
    }

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function templates()
    {
        return $this->hasMany(wncms()->getModelClass('page_template'));
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        return $this->getFirstMediaUrl('page_thumbnail');
    }

    public function getTemplateInfoAttribute()
    {
        return collect(config('theme.' . $this->website->theme . '.templates'))->where('blade_name', $this->blade_name)->first();
    }

    public function getTemplatesPath()
    {
        return 'pages';
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Methods
     * ----------------------------------------------------------------------------------------------------
     */
    public function getWidgets()
    {
        return config('theme.' . $this->website?->theme . '.templates.' . $this->blade_name . '.widgets') ?? [];
    }

    public function getWidgetIndex($index, $widget)
    {
        return "widget_{$index}_{$widget}";
    }

    public function getPageTemplateOption($pageWidgetIndex, $key, $fallback = null, $valueType = null)
    {
        if (!empty($this->options[$pageWidgetIndex]['fields'][$key])) {

            $value = $this->options[$pageWidgetIndex]['fields'][$key];
        } elseif (!empty($this->getTranslation('options', app()->getFallbackLocale())[$pageWidgetIndex]['fields'][$key])) {

            $value = $this->getTranslation('options', app()->getFallbackLocale())[$pageWidgetIndex]['fields'][$key];
        }

        if (isset($value)) {

            if ($valueType == 'tagify' && wncms()->isValidTagifyJson($value)) {
                $value = collect(json_decode($value))->pluck('value')->implode(',');
            }

            return $value;
        }

        return $fallback;
    }

    public function gpto(...$arg)
    {
        return $this->getPageTemplateOption(...$arg);
    }

    public function savePageOption($request)
    {
        $newInputs = [];
        $pageWidgetOrder = 0;

        foreach ($request->inputs ?? [] as $pageWidgetId => $valueArr) {
            foreach ($valueArr as $key => $value) {
                // set id
                $newInputs[$pageWidgetId]['pageWidgetId'] = $pageWidgetId;

                // set order
                $newInputs[$pageWidgetId]['pageWidgetOrder'] = $pageWidgetOrder;

                // set field values
                $newInputs[$pageWidgetId]['fields'][$key] = $value;

                // handle remove image
                if (str()->endswith($key, '_remove')) {
                    $file_key = str_replace("_remove", '', $key);
                    if ($value == 1) {
                        $this->clearMediaCollection($file_key);
                        unset($newInputs[$pageWidgetId][$file_key]);
                    } else {
                        $newInputs[$pageWidgetId]['fields'][$file_key] = $this->getPageTemplateOption($pageWidgetId, $file_key);
                    }
                    unset($newInputs[$pageWidgetId]['fields'][$key]); // don’t keep *_remove
                }

                // handle upload image
                if ($request->hasFile("inputs.{$pageWidgetId}.{$key}")) {
                    $collection = "{$pageWidgetId}_{$key}";
                    $this->clearMediaCollection($collection);
                    $image = $this->addMediaFromRequest("inputs.{$pageWidgetId}.{$key}")->toMediaCollection($collection);
                    $value = str_replace(env('APP_URL'), '', $image->getUrl());
                    $newInputs[$pageWidgetId]['fields'][$key] = $value;
                }
            }

            $pageWidgetOrder++;
        }

        // 🔑 Use HasOptions trait to persist instead of updating a dropped column
        if (!empty($newInputs)) {
            $this->setOption('template_inputs_' . wncms()->getLocale(), $newInputs);
        } else {
            $this->deleteOption('template_inputs_' . wncms()->getLocale());
        }
    }

    public function getPageOption($key, $fallback = null)
    {
        if (!empty($this->options[$key])) {
            return $this->options[$key];
        }

        return $fallback;
    }

    /**
     * Page Builder
     */
    public function builderContents()
    {
        return $this->hasMany(PageBuilderContent::class, 'page_id')
            ->orderBy('version', 'asc');
    }

    public function latestBuilderContent(string $builderType = 'default')
    {
        return $this->builderContents()
            ->where('builder_type', $builderType)
            ->orderByDesc('version')
            ->first();
    }

    public function page_templates()
    {
        return $this->hasMany(wncms()->getModelClass('page_template'));
    }

    /**
     * Get the template row for the current theme + blade_name.
     */
    public function getCurrentTemplateRow()
    {
        if (!$this->blade_name || !$this->website) {
            return null;
        }

        return $this->page_templates()
            ->where('theme_id', $this->website->theme)
            ->where('template_id', $this->blade_name)
            ->first();
    }

    /**
     * Convenient accessor for the decoded template values.
     */
    public function getTemplateValues()
    {
        $row = $this->getCurrentTemplateRow();

        if (!$row || empty($row->value)) {
            return [];
        }

        return is_array($row->value) ? $row->value : json_decode($row->value, true);
    }
    protected function processFieldGroup(array $inputs, array $field, string $sectionName, int $accordionIndex = null, int $inlineIndex = null)
    {
        $results = [];

        // sortable
        if (!empty($field['sortable'])) {
            $sortKey = $field['name'] ?? null;
            if ($sortKey && !empty($inputs[$sectionName][$sortKey])) {
                $results[$sortKey] = $inputs[$sectionName][$sortKey];
            }
        }

        // inline
        if (($field['type'] ?? '') === 'inline') {
            $repeat = $field['repeat'] ?? 1;
            for ($i = 1; $i <= $repeat; $i++) {
                foreach ($field['sub_items'] as $sub) {
                    $key = $sub['name'];
                    if ($accordionIndex !== null && $repeat > 1) $finalKey = "{$key}_{$accordionIndex}_{$i}";
                    elseif ($accordionIndex !== null) $finalKey = "{$key}_{$accordionIndex}";
                    elseif ($repeat > 1) $finalKey = "{$key}_{$i}";
                    else $finalKey = $key;

                    $val = $inputs[$sectionName][$finalKey] ?? null;

                    // tagify
                    if (($sub['type'] ?? '') === 'tagify' && !empty($val) && wncms()->isValidTagifyJson($val)) {
                        $val = implode(',', collect(json_decode($val))->pluck('value')->toArray());
                    }

                    // image
                    if (($sub['type'] ?? '') === 'image' && request()->hasFile("template_inputs.{$sectionName}.{$finalKey}")) {
                        $this->clearMediaCollection("tpl_{$sectionName}_{$finalKey}");
                        $media = $this->addMediaFromRequest("template_inputs.{$sectionName}.{$finalKey}")
                            ->toMediaCollection("tpl_{$sectionName}_{$finalKey}");
                        $val = str_replace(env('APP_URL'), '', $media->getUrl());
                    }

                    $results[$finalKey] = $val;
                }
            }
            return $results;
        }

        // accordion
        if (($field['type'] ?? '') === 'accordion') {
            $repeat = $field['repeat'] ?? 1;
            for ($i = 1; $i <= $repeat; $i++) {
                foreach ($field['content'] as $child) {
                    $childResults = $this->processFieldGroup($inputs, $child, $sectionName, $i, null);
                    $results = array_merge($results, $childResults);
                }
            }
            return $results;
        }

        // normal
        $name = $field['name'] ?? null;
        if (!$name) return [];

        $finalKey = $accordionIndex !== null ? "{$name}_{$accordionIndex}" : $name;
        $val = $inputs[$sectionName][$finalKey] ?? null;

        // tagify
        if (($field['type'] ?? '') === 'tagify' && !empty($val) && wncms()->isValidTagifyJson($val)) {
            $val = implode(',', collect(json_decode($val))->pluck('value')->toArray());
        }

        // image
        if (($field['type'] ?? '') === 'image' && request()->hasFile("template_inputs.{$sectionName}.{$finalKey}")) {
            $this->clearMediaCollection("tpl_{$sectionName}_{$finalKey}");
            $media = $this->addMediaFromRequest("template_inputs.{$sectionName}.{$finalKey}")
                ->toMediaCollection("tpl_{$sectionName}_{$finalKey}");
            $val = str_replace(env('APP_URL'), '', $media->getUrl());
        }

        return [$finalKey => $val];
    }


    /**
     * MAIN handler
     */
    public function saveTemplateInputs(Request $request)
    {
        $theme      = wncms()->website()->get()?->theme;
        $templateId = $this->blade_name;

        $sections = config("theme.{$theme}.templates.{$templateId}.sections", []);
        $inputs   = $request->input('template_inputs', []);

        $processed = [];

        foreach ($sections as $sectionName => $section) {

            foreach ($section['options'] ?? [] as $option) {

                $chunk = $this->processFieldGroup($inputs, $option, $sectionName);

                foreach ($chunk as $k => $v) {
                    $processed[$sectionName][$k] = $v;
                }
            }
        }

        $this->savePageTemplateValues([
            'theme_id'    => $theme,
            'template_id' => $templateId,
            'value'       => $processed,
        ]);
    }

    /**
     * Save template values for the current theme + blade_name.
     */
    public function savePageTemplateValues(array $data)
    {
        return $this->page_templates()->updateOrCreate(
            [
                'theme_id'    => $data['theme_id'],
                'template_id' => $data['template_id'],
            ],
            [
                'value' => $data['value'],
            ]
        );
    }

    public function option(string $key, $default = null)
    {
        $theme = wncms()->website()->get()?->theme ?? 'default';
        $templateId = $this->blade_name;

        $row = $this->page_templates()
            ->where('theme_id', $theme)
            ->where('template_id', $templateId)
            ->first();

        $values = $row?->value ?? [];


        foreach (explode('.', $key) as $segment) {
            if (!is_array($values) || !array_key_exists($segment, $values)) {
                return $default;
            }
            $values = $values[$segment];
        }

        return $values;
    }

    
    public function allOptions()
    {
        $theme = wncms()->website()->get()?->theme ?? 'default';
        $templateId = $this->blade_name;

        $row = $this->page_templates()
            ->where('theme_id', $theme)
            ->where('template_id', $templateId)
            ->first();

        $values = $row?->value ?? [];

        return $values;
    }
}
