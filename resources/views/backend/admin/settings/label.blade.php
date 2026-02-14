<label class="{{ $class ?? 'col-lg-4' }} col-form-label fw-bold fs-6">
    @if (!empty($tab_content['text_key']))
        @lang($tab_content['text_key'])
    @else
        @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
    @endif

    @if (!empty($tab_content['badge']))
        <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">
            {{ $tab_content['badge'] }}
        </span>
    @endif

    @php
        $descriptionKey = $tab_content['description_key'] ?? ('wncms::word.' . $tab_content['name'] . '_description');
    @endphp

    @if (trans()->has($descriptionKey))
    
        <i class="fa-solid fa-circle-info text-muted ms-2"
           role="button"
           tabindex="0"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           title="@lang($descriptionKey)"></i>
    @endif

    @if (!empty($settings['show_developer_hints']))
        <div class="fs-xs text-gray-300 mt-1">{{ $tab_content['name'] }}</div>
    @endif
</label>
