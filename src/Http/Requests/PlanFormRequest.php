<?php

namespace Wncms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
            'free_trial_duration' => 'nullable|integer|min:0',

            'prices' => 'required|array',
            'prices.*.duration' => 'nullable|required_if:prices.*.is_lifetime,false|integer|min:1',
            'prices.*.amount' => 'required|numeric|min:0',
            'prices.*.duration_unit' => 'nullable|required_if:prices.*.is_lifetime,false|in:day,week,month,year',
            'prices.*.is_lifetime' => 'required|boolean',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => __('wncms::word.name_required'),
            'name.string' => __('wncms::word.name_string'),
            'name.max' => __('wncms::word.name_max'),
        
            'slug.string' => __('wncms::word.slug_string'),
            'slug.max' => __('wncms::word.slug_max'),
        
            'status.required' => __('wncms::word.status_required'),
            'status.in' => __('wncms::word.status_in'),
        
            'description.string' => __('wncms::word.description_string'),
        
            'free_trial_duration.integer' => __('wncms::word.free_trial_duration_integer'),
            'free_trial_duration.min' => __('wncms::word.free_trial_duration_min'),
        
            'prices.required' => __('wncms::word.prices_required'),
            'prices.array' => __('wncms::word.prices_array'),
        
            'prices.*.duration.required_if' => __('wncms::word.prices_duration_required_if'),
            'prices.*.duration.integer' => __('wncms::word.prices_duration_integer'),
            'prices.*.duration.min' => __('wncms::word.prices_duration_min'),
        
            'prices.*.amount.required' => __('wncms::word.prices_amount_required'),
            'prices.*.amount.numeric' => __('wncms::word.prices_amount_numeric'),
            'prices.*.amount.min' => __('wncms::word.prices_amount_min'),
        
            'prices.*.duration_unit.required_if' => __('wncms::word.prices_duration_unit_required_if'),
            'prices.*.duration_unit.in' => __('wncms::word.prices_duration_unit_in'),
        
            'prices.*.is_lifetime.required' => __('wncms::word.prices_is_lifetime_required'),
            'prices.*.is_lifetime.boolean' => __('wncms::word.prices_is_lifetime_boolean'),
        ];
        
    }
}
