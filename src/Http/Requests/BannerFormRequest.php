<?php

namespace Wncms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->status,
            'url' => $this->url,
            'sort' => $this->sort,
            'contact' => $this->contact,
            'remark' => $this->remark,
            'positions' => $this->positions,
            'expired_at' => !empty($this->expired_at) ? Carbon::parse($this->expired_at) : null,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => '',
            'url' => '',
            'sort' => '',
            'contact' => '',
            'remark' => '',
            'positions' => '',
            'expired_at' => '',
        ];
    }

    public function messages()
    {
        return [
            //
        ];
    }
}
