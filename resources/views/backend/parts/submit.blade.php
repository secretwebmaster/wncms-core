@php
    $label = $label ?? __('wncms::word.submit');
    $message = $message ?? __('wncms::word.please_wait');
@endphp

<span class="indicator-label fw-bold">{{ $label }}</span>
<span class="indicator-progress">
    <span>{{ $message }}</span>
    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
</span>
