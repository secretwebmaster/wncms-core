<div class="input-group">
    <input type="text"
        name="{{ $inputName }}"
        class="form-control form-control-sm"
        value="{{ $currentValue }}"
        placeholder="{{ $option['placeholder'] ?? '' }}"
        @required(!empty($option['required']))
        @disabled(!empty($option['disabled']) || !empty($disabled)) />
    <div class="colorpicker-input" data-input="{{ $inputName }}" data-current="{{ $currentOptions[$option['name']] ?? '' }}"></div>
</div>
