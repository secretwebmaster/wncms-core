<?php

namespace Wncms\Services\Wncms\Helpers;

use Wncms\Models\ContactForm;

class ContactFormHelper
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_contact_form";

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single contact form by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param integer $contactFormId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    function get(?int $contactFormId = null)
    {
        if(empty($contactFormId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['contact_forms'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($contactFormId) {
            $q = ContactForm::query();
            $q->where('id', $contactFormId);
            $q->with('options');
            $contactForm = $q->first();
            return $contactForm;
        });
    }

    public function getList(int $count = 0)
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['contact_forms'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function() use($count) {
            $q = ContactForm::query();

            if($count){
                $q->limit($count);
            }

            $contactForms = $q->get();
            return $contactForms;
        });

    }
    

    function render(?int $contactFormId = null, $hideContactFormTitle = false)
    {
        if(empty($contactFormId)) return null;
        $method = "render";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['contact_forms'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($contactFormId, $hideContactFormTitle) {
            $contactForm = $this->get($contactFormId);
            // dd($contactForm);
            if(!$contactForm) return;

            return view('backend.contact_forms.render', [
                'contactForm' => $contactForm,
                'hideContactFormTitle' => $hideContactFormTitle,
            ])->render();
        });

    }
}
