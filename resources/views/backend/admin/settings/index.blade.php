@extends('wncms::layouts.backend')

@push('head_css')
    <style>
        .wncms-tooltip-wide .tooltip-inner {
            max-width: 420px;
            text-align: left;
            white-space: normal;
            font-size: 0.875rem;
            line-height: 1.4;
        }
    </style>
@endpush

@section('content')

    @include('wncms::backend.parts.message')

    <form class="form" method="POST" action="{{ route('settings.update', $website) }}" enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <div class="row g-2">

            {{-- nav tab --}}
            @include('wncms::backend.admin.settings.nav')

            {{-- tab content --}}
            <div class="col-12 col-sm-8 col-lg-10 tab-content" id="myTabContent">
                @foreach ($availableSettings as $nav_tab_index => $nav_tab)
                    @php $isActive = $nav_tab['tab_name'] === $activeTab; @endphp

                    @if (!empty($nav_tab['tab_name']) && !empty($nav_tab['tab_content']))
                        <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}" id="tab_{{ $nav_tab['tab_name'] }}" role="tabpanel">
                            <div class="card">
                                <div class="collapse show">
                                    <div class="card-body border border-dark border-5 rounded p-6">

                                        @foreach ($nav_tab['tab_content'] as $tab_content_index => $tab_content)
                                            @if ($tab_content['type'] == 'heading')
                                                <h2>{{ $tab_content['name'] }}</h2>
                                            @elseif(in_array($tab_content['type'], ['text', 'number']))
                                                <div class="row mb-1">

                                                    @include('wncms::backend.admin.settings.label', [
                                                        'tab_content' => $tab_content,
                                                        'settings' => $settings,
                                                    ])

                                                    <div class="col-lg-8 fv-row">
                                                        <input type="{{ $tab_content['type'] }}" class="form-control form-control-sm" name="settings[{{ $tab_content['name'] }}]" value="{{ $settings[$tab_content['name']] ?? '' }}" @if (!empty($tab_content['disabled'])) disabled @endif />
                                                    </div>
                                                </div>
                                            @elseif($tab_content['type'] == 'switch')
                                                <div class="row mb-2">

                                                    @include('wncms::backend.admin.settings.label', [
                                                        'tab_content' => $tab_content,
                                                        'settings' => $settings,
                                                        'class' => 'col-12 col-lg-4',
                                                    ])

                                                    <div class="col-12 col-lg-8 d-flex justify-content-lg-end mt-2 mt-lg-0">
                                                        <div class="form-check form-check-solid form-check-custom form-switch">
                                                            <input type="hidden" name="settings[{{ $tab_content['name'] }}]" value="0">
                                                            <input class="form-check-input w-35px h-20px border border-1 border-secondary"
                                                                type="checkbox"
                                                                name="settings[{{ $tab_content['name'] }}]"
                                                                value="1"
                                                                {{ $settings[$tab_content['name']] ?? false ? 'checked' : '' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($tab_content['type'] == 'select')
                                                <div class="row mb-1">

                                                    @include('wncms::backend.admin.settings.label', [
                                                        'tab_content' => $tab_content,
                                                        'settings' => $settings,
                                                    ])

                                                    <div class="col-lg-8 fv-row">
                                                        <select class="form-select form-select-sm" name="settings[{{ $tab_content['name'] }}]">
                                                            <option value="">@lang('wncms::word.please_select')</option>

                                                            @if ($tab_content['options'] == 'supported_locales')
                                                                @foreach (collect(config('laravellocalization.supportedLocales', []))->mapWithKeys(fn($val, $key) => [$key => $val['native'] ?? $key])->toArray() as $optionKey => $optionValue)
                                                                    @php
                                                                        $value = $optionKey;
                                                                        $label = $optionValue;
                                                                        $isSelected = ($settings[$tab_content['name']] ?? '') == $value;
                                                                    @endphp
                                                                    <option value="{{ $value }}" @if ($isSelected) selected @endif>{{ $label }}</option>
                                                                @endforeach
                                                            @elseif(!empty($tab_content['options']) && is_array($tab_content['options']))
                                                                @foreach ($tab_content['options'] as $optionKey => $optionValue)
                                                                    @php
                                                                        $value = is_int($optionKey) ? $optionValue : $optionKey;
                                                                        $label = is_int($optionKey) ? $optionValue : $optionValue;
                                                                        $isSelected = ($settings[$tab_content['name']] ?? '') == $value;
                                                                    @endphp
                                                                    <option value="{{ $value }}" @if ($isSelected) selected @endif>
                                                                        @if (isset($tab_content['translate_option']) && $tab_content['translate_option'] === false)
                                                                            {{ $label }}
                                                                        @else
                                                                            @lang('wncms::word.' . $label)
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>
                                            @elseif($tab_content['type'] == 'textarea')
                                                <div class="row mb-1">

                                                    @include('wncms::backend.admin.settings.label', [
                                                        'tab_content' => $tab_content,
                                                        'settings' => $settings,
                                                    ])

                                                    <div class="col-lg-8 fv-row">
                                                        <textarea name="settings[{{ $tab_content['name'] }}]" class="form-control form-control-sm" rows="4">{{ $settings[$tab_content['name']] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            @elseif($tab_content['type'] == 'custom')
                                                @if ($tab_content['name'] == 'display_model')
                                                    <div class="row mb-1">
                                                        {{-- <label class="col-lg-4 col-form-label fw-bold fs-6">
                                                            @lang('wncms::word.display_model')
                                                            <br>
                                                            @if (!empty($settings['show_developer_hints']))
                                                                <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                                            @endif
                                                        </label> --}}

                                                        @include('wncms::backend.admin.settings.label', [
                                                            'tab_content' => $tab_content,
                                                            'settings' => $settings
                                                        ])

                                                        <div class="col-lg-8 fv-row">
                                                            <div class="row align-items-center mt-3">

                                                                {{-- CHECK ALL BUTTON --}}
                                                                <div class="col-123 mb-3">
                                                                    <button type="button"
                                                                        class="btn_check_all btn btn-sm btn-dark px-2 py-1 fw-bold"
                                                                        data-target-class="model_checkbox">
                                                                        @lang('wncms::word.check_all')
                                                                    </button>
                                                                </div>

                                                                {{-- MODELS (controller-prepared: $modelDisplayList) --}}
                                                                @foreach ($modelDisplayList as $modelData)
                                                                    @if (!empty($modelData['routes']))
                                                                        <div class="col-6 col-md-4 mb-1 model_checkbox">

                                                                            <label class="form-check form-check-inline form-check-solid me-5">

                                                                                {{-- Checkbox --}}
                                                                                <input class="form-check-input"
                                                                                    name="active_models[]"
                                                                                    type="checkbox"
                                                                                    value="{{ $modelData['model_name'] }}"
                                                                                    @if (in_array($modelData['model_name'], json_decode($settings['active_models'] ?? '[]', true))) checked @endif />

                                                                                {{-- Label --}}
                                                                                <span class="fw-bold ps-2 fs-6">
                                                                                    @if (!empty($modelData['model_key']))
                                                                                        @lang('wncms::word.' . $modelData['model_key'])
                                                                                    @else
                                                                                        @lang('wncms::word.' . str()->snake($modelData['model_name'], '_'))
                                                                                    @endif
                                                                                </span>

                                                                            </label>

                                                                        </div>
                                                                    @endif
                                                                @endforeach

                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif(false)
                                                @endif
                                            @endif
                                        @endforeach

                                        @if ($nav_tab['tab_name'] == 'smtp')
                                            @include('wncms::backend.admin.settings.tabs.smtp')
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Dynamic API Access Tab --}}
                @include('wncms::backend.admin.settings.tabs.api')

                {{-- developer_mode tab --}}
                @includeWhen(gss('developer_mode'), 'wncms::backend.admin.settings.tabs.developer')

                {{-- submit --}}
                <div class="card-footer d-flex justify-content-end mt-5">
                    <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit w-100">
                        @include('wncms::backend.parts.submit', ['label' => __('wncms::word.save_all')])
                    </button>
                </div>
            </div>

        </div>

        <input type="hidden" name="active_tab" id="active_tab" value="">
    </form>

@endsection

@push('foot_js')
    <script>
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            var tabName = $(e.target).data('bsTarget').replace('#tab_', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        });

        $('form').on('submit', function() {
            const activeTab = $('.nav-link.active').attr('data-bs-target').replace('#tab_', '');
            $('#active_tab').val(activeTab);
            WNCMS.Cookie.Set('activeSettingTab', activeTab, 7);
        });

        activateTabFromCookie();

        function activateTabFromCookie() {
            const tabFromUrl = new URL(window.location).searchParams.get('tab');
            const cookieTab = WNCMS.Cookie.Get('activeSettingTab');

            const finalTab = tabFromUrl || cookieTab;

            if (finalTab) {
                const triggerEl = document.querySelector('[data-bs-target="#tab_' + finalTab + '"]');
                if (triggerEl) {
                    const tabTrigger = new bootstrap.Tab(triggerEl);
                    tabTrigger.show();
                }
            }
        }
    </script>

    <script>
        $('.btn_check_all').click(function() {
            var targetClass = $(this).data('target-class');
            var checkboxes = $('.' + targetClass + ' input[type="checkbox"]');
            var allChecked = checkboxes.filter(':checked').length === checkboxes.length;
            checkboxes.prop('checked', !allChecked);
        });
    </script>

    {{-- tooltips --}}
    <script>
        function wncmsInitTooltips(scope) {
            if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;

            (scope || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                if (bootstrap.Tooltip.getInstance(el)) return;
                new bootstrap.Tooltip(el, {
                    container: 'body',
                    trigger: 'hover focus',
                    customClass: 'wncms-tooltip-wide'
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            wncmsInitTooltips(document);
        });

        document.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            if (target) {
                const pane = document.querySelector(target);
                if (pane) wncmsInitTooltips(pane);
            }
        });
    </script>
@endpush
