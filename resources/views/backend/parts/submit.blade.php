@php
    $label = $label ?? __('word.submit');
    $message = $message ?? __('word.please_wait');
@endphp

<span class="indicator-label fw-bold">{{ $label }}</span>
<span class="indicator-progress">
    <span>{{ $message }}</span>
    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
</span>
