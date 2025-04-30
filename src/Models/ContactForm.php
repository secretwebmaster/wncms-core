<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class ContactForm extends Model
{
    use HasFactory;
    use HasTranslations;
    use WnModelTrait;

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
        return $this->belongsToMany(ContactFormOption::class, 'contact_form_option_relationship', 'form_id', 'option_id')->withPivot('order', 'is_required');
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
