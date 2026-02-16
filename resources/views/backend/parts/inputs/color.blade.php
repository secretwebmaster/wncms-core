<div class="input-group">
    <input type="text"
        name="{{ $inputName }}"
        class="form-control form-control-sm"
        value="{{ $currentValue ?: '#FFA218' }}"
        placeholder="{{ $option['placeholder'] ?? '#FFA218' }}"
        @required(!empty($option['required']))
        @disabled(!empty($option['disabled']) || !empty($disabled)) />
    <div class="colorpicker-input" data-input="{{ $inputName }}" data-current="{{ $currentOptions[$option['name']] ?? '#ccc' }}"></div>
</div>
