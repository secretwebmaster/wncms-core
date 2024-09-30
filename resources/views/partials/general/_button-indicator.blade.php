@php
    $label = $label ?? __('Submit');
    $message = $message ?? __('請稍等...');
@endphp

<!--begin::Indicator-->
<span class="indicator-label fw-bold">
    {{ $label }}
</span>
<span class="indicator-progress">
    {{ $message }}
    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
</span>
<!--end::Indicator-->
