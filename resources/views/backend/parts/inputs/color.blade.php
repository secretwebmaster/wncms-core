<div class="input-group mb-5">
    <input type="text"
        name="{{ $inputName }}"
        class="form-control form-control-sm"
        value="{{ $currentValue }}"
        placeholder="{{ $option['placeholder'] ?? '' }}"
        @disabled(!empty($option['disabled']) || !empty($disabled)) />
    <div class="colorpicker-input" data-input="{{ $inputName }}" data-current="{{ $currentOptions[$option['name']] ?? '#ccc' }}"></div>
</div>