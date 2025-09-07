<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;
use Wncms\Translatable\Traits\HasTranslations;

class ContactForm extends WncmsModel
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-envelope'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const SUB_ROUTES = [
        'contact_form_options.index',
        'contact_form_options.create',
        'contact_form_submissions.index',
    ];

    protected $translatable = ['title','description'];

    public function options()
    {
        return $this->belongsToMany(wncms()->getModelClass('contact_form_option'), 'contact_form_option_relationship', 'form_id', 'option_id')->withPivot('order', 'is_required');
    }

    public function getOptionDisplayName($fieldName)
    {
        $systemKeys = [
            'current_url',
        ];
        
        if(in_array($fieldName, $systemKeys)){
            return __('wncms::word.' . $fieldName);
        }

        return $this->options->where('name', $fieldName)->first()?->display_name ?? $fieldName;
    }
}
