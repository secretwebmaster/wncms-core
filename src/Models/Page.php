<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Secretwebmaster\LaravelOptionable\Traits\HasOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Services\Models\WncmsModel;
use Wncms\Translatable\Traits\HasTranslations;

class Page extends WncmsModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;
    use HasOptions;

    protected $guarded = [];

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

    public const ORDERS = [
        'order',
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

    public const ROUTES = [
        'index',
        'create',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('page_thumbnail')->singleFile();
        $this->addMediaCollection('page_content');
    }

    //! Relationship
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

    //! Attribute
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

    //! Data handling
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
}
