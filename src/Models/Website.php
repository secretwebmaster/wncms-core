<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Translatable\Traits\HasTranslations;

class Website extends WncmsModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use WnModelTrait;
    use HasTranslations;

    protected $guarded = [];

    protected $translatable = [
        'site_name',
        'site_slogan',
        'site_seo_keywords',
        'site_seo_description',
    ];

    protected $withs = ['media'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-globe'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('site_logo')->singleFile();
        $this->addMediaCollection('site_logo_white')->singleFile();
        $this->addMediaCollection('site_favicon')->singleFile();
    }


    //! Relationships
    public function advertisements()
    {
        return $this->hasMany(wncms()->getModelClass('advertisement'));
    }

    public function domain_aliases()
    {
        return $this->hasMany(wncms()->getModelClass('domain_alias'));
    }
    
    public function faqs()
    {
        return $this->hasMany(wncms()->getModelClass('faq'));
    }

    public function menus()
    {
        return $this->hasMany(wncms()->getModelClass('menu'));
    }
    
    public function pages()
    {
        return $this->hasMany(wncms()->getModelClass('page'));
    }

    public function posts()
    {
        return $this->belongsToMany(wncms()->getModelClass('post'));
    }

    public function search_keywords()
    {
        return $this->hasMany(wncms()->getModelClass('search_keyword'));
    }

    public function theme_options()
    {
        return $this->hasMany(wncms()->getModelClass('theme_option'));
    }

    public function users()
    {
        return $this->belongsToMany(wncms()->getModelClass('user'));
    }

    public function contact_form_submissions()
    {
        return $this->hasMany(wncms()->getModelClass('contact_form_submission'));
    }


    //! Attribute
    public function getSiteFaviconAttribute()
    {
        return $this->getFirstMediaUrl('site_favicon');
    }

    public function getSiteLogoAttribute()
    {
        return $this->getFirstMediaUrl('site_logo');
    }

    public function getSiteLogoWhiteAttribute()
    {
        return $this->getFirstMediaUrl('site_logo_white');
    }

    public function getUrlAttribute()
    {
        return wncms_add_https($this->domain);
    }


    //! Functions
    public function get_options($locale = null)
    {
        if(!$this->theme){
            return [];
        }

        $locale ??= app()->getLocale();

        // Eager load translations with the theme options
        $options = $this->theme_options()
            ->where('theme', $this->theme ?? 'default')
            ->with(['translations' => function ($query) use ($locale) {
                $query->where('locale', $locale);
            }])
            ->get();

        //not mapped
        // return $options->pluck('value','key')->toArray();

        // Map the options with their translations
        return $options->mapWithKeys(function ($option) use ($locale) {
            // Get the translation for the current locale
            $translatedValue = $option->translations->firstWhere('locale', $locale)->value ?? $option->value;
            return [$option->key => $translatedValue];
        })->toArray();

        // return $this->theme_options()->where('theme',$this->theme ?? 'default')->pluck('value','key')->toArray();
    }

    public function get_option($key)
    {
        return data_get($this->get_options(), $key);
    }


}
