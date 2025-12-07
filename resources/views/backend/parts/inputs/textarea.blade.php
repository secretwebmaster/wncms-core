@php
    // normalize textarea display
    $decoded = json_decode($currentValue, true);

    // treat only JSON empty array/object as empty
    if ($decoded === [] || $currentValue === '[]' || $currentValue === '{}') {
        $displayValue = '';
    } else {
        $displayValue = $currentValue;
    }
@endphp

<textarea name="{{ $inputName }}" class="form-control" rows="6" @disabled(!empty($option['disabled']) || !empty($disabled))>{{ $displayValue }}</textarea>
