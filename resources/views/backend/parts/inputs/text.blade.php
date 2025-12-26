@php
    if(is_array($currentValue)){
        $currentValue = json_encode($currentValue, JSON_UNESCAPED_UNICODE);
    }
@endphp
<input type="{{ $option['type'] }}"
    name="{{ $inputName }}"
    class="form-control form-control-sm"
    value="{{ $currentValue }}"
    @disabled(!empty($option['disabled']) || !empty($disabled))
    @required(!empty($option['required']))
    @if (!empty($option['placeholder']) || !empty($disabled)) placeholder="{{ $option['placeholder'] }}" @endif />