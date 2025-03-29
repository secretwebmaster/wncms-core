<button class="btn btn-sm btn-{{ $btnColor ?? 'dark' }} fw-bold mb-1 {{ $btnClass ?? '' }}"
    wncms-btn-ajax
    wncms-get-model-ids
    data-route="{{ route('models.update') }}"
    data-method="POST"

    @if(!empty($swal))
    wncms-btn-swal
    data-swal="true"
    @endif

    data-param-model="{{ $model }}"
    data-param-column="{{ $fieldColumn ?? 'status' }}"
    data-param-value="{{ $fieldValue ?? 'active' }}"
>{{ $btnText ?? __('wncms::word.bulk_update') }}</button>