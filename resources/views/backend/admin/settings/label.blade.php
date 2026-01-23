<label class="{{ $class ?? 'col-lg-4' }} col-form-label fw-bold fs-6">
    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))

    @if (!empty($tab_content['badge']))
        <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">
            {{ $tab_content['badge'] }}
        </span>
    @endif

    @if (trans()->has('wncms::word.' . $tab_content['name'] . '_description'))
    
        <i class="fa-solid fa-circle-info text-muted ms-2"
           role="button"
           tabindex="0"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           title="@lang('wncms::word.' . $tab_content['name'] . '_description')"></i>
    @endif

    @if (!empty($settings['show_developer_hints']))
        <div class="fs-xs text-gray-300 mt-1">{{ $tab_content['name'] }}</div>
    @endif
</label>