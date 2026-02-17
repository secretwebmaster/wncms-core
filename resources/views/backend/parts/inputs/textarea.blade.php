@php
    // normalize textarea display
    if (is_array($currentValue)) {
        $displayValue = empty($currentValue) ? '' : json_encode($currentValue, JSON_UNESCAPED_UNICODE);
    } else {
        $decoded = is_string($currentValue) ? json_decode($currentValue, true) : null;
    }

    // treat only JSON empty array/object as empty when current value is string
    if (!isset($displayValue) && ($decoded === [] || $currentValue === '[]' || $currentValue === '{}')) {
        $displayValue = '';
    } elseif (!isset($displayValue)) {
        $displayValue = $currentValue;
    }
@endphp

<textarea name="{{ $inputName }}" class="form-control" rows="6" placeholder="{{ $option['placeholder'] ?? '' }}" @disabled(!empty($option['disabled']) || !empty($disabled))>{{ $displayValue }}</textarea>
