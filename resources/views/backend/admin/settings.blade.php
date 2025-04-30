@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

@php
$firstTabName = collect($availableSettings)->firstWhere(fn($t) => !empty($t['tab_name']) && !empty($t['tab_content']))['tab_name'] ?? null;
$activeTab = request('tab') ?? old('active_tab') ?? $firstTabName;
@endphp

<form class="form" method="POST" action="{{ route('settings.update', $website) }}" enctype="multipart/form-data">

    @csrf
    @method('PUT')

    <div class="row g-2">

        {{-- nav tab --}}
        <div class="col-12 col-sm-4 col-lg-2">
            <div class="card">
                <div class="card-body px-3 py-8">
                    <ul class="nav nav-tabs nav-pills border-0 flex-row flex-md-column me-0 me-md-5 mb-3 mb-md-0 fs-6 w-100">
                        @foreach($availableSettings as $nav_tab_index => $nav_tab)
                        @php $isActive = $nav_tab['tab_name'] === $activeTab; @endphp
                        @if(!empty($nav_tab['tab_name']) && !empty($nav_tab['tab_content']))
                        <li class="nav-item col-4 col-md-12 fw-bold me-0">
                            <a class="nav-link {{ $isActive ? 'active' : '' }}"
                                data-bs-toggle="tab"
                                data-bs-target="#tab_{{ $nav_tab['tab_name'] }}"
                                href="javascript:void(0);">
                                @lang("wncms::word." . $nav_tab['tab_name'] . "_setting")
                            </a>
                        </li>
                        @endif
                        @endforeach
                    </ul>
                </div>

                @if(!gss('disable_core_update'))
                <div class="card-footer p-3">
                    <a href="{{ route('updates') }}" class="btn btn-sm btn-secondary w-100">@lang('wncms::word.check_updates')</a>
                </div>
                @endif
            </div>
        </div>

        {{-- tab content --}}
        <div class="col-12 col-sm-8 col-lg-10 tab-content" id="myTabContent">
            @foreach($availableSettings as $nav_tab_index => $nav_tab)
            @php $isActive = $nav_tab['tab_name'] === $activeTab; @endphp
            @if(!empty($nav_tab['tab_name']) && !empty($nav_tab['tab_content']))
            <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}" id="tab_{{ $nav_tab['tab_name'] }}" role="tabpanel">
                <div class="card">
                    <div class="collapse show">
                        <div class="card-body border-top p-6">

                            @foreach($nav_tab['tab_content'] as $tab_content_index => $tab_content)

                            @if($tab_content['type'] == 'heading')

                            <h2>{{ $tab_content['name'] }}</h2>

                            @elseif(in_array($tab_content['type'], ['text', 'number']))

                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
                                    @if(!empty($tab_content['badge']))
                                    <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">{{ $tab_content['badge'] }}</span>
                                    @endif
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 fv-row">
                                    <input type="{{ $tab_content['type'] }}" class="form-control form-control-sm" name="settings[{{ $tab_content['name'] }}]" value="{{ $settings[($tab_content['name'])] ?? '' }}" @if(!empty($tab_content['disabled'])) disabled @endif />
                                    @if(trans()->has('word.'. $tab_content['name'] .'_description'))<div class="text-muted p-1">@lang('wncms::word.'. $tab_content['name'] .'_description')</div>@endif
                                </div>
                            </div>

                            @elseif($tab_content['type'] == 'switch')

                            {{-- {{ dd( $settings[($tab_content['name']))] ?? '' }} --}}
                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
                                    @if(!empty($tab_content['badge']))
                                    <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">{{ $tab_content['badge'] }}</span>
                                    @endif
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 d-flex align-items-center">
                                    <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                                        <input type="hidden" name="settings[{{ $tab_content['name'] }}]" value="0">
                                        <input class="form-check-input w-35px h-20px border border-1 border-secondary" type="checkbox" name="settings[{{ $tab_content['name'] }}]" value="1" {{ $settings[($tab_content['name'])] ?? '' ? 'checked' : '' }} />
                                        <label class="form-check-label" for="check_beta_functions"></label>
                                    </div>
                                    @if(trans()->has('word.'. $tab_content['name'] .'_description'))<div class="text-muted p-1">@lang('wncms::word.'. $tab_content['name'] .'_description')</div>@endif
                                </div>
                            </div>

                            @elseif($tab_content['type'] == 'select')

                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
                                    @if(!empty($tab_content['badge']))
                                    <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">{{ $tab_content['badge'] }}</span>
                                    @endif
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 fv-row">
                                    <select class="form-select form-select-sm" name="settings[{{ $tab_content['name'] }}]">
                                        <option value="">@lang('wncms::word.please_select')</option>
                                        @foreach($tab_content['options'] ?? [] as $optionKey => $optionValue)
                                        @php
                                        $value = is_int($optionKey) ? $optionValue : $optionKey;
                                        $label = is_int($optionKey) ? $optionValue : $optionValue;
                                        $isSelected = ($settings[$tab_content['name']] ?? '') == $value;
                                        @endphp
                                        <option value="{{ $value }}" @if($isSelected) selected @endif>
                                            @if(isset($tab_content['translate_option']) && $tab_content['translate_option'] === false)
                                            {{ $label }}
                                            @else
                                            @lang('wncms::word.' . $label)
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>

                                    @if(trans()->has('word.'. $tab_content['name'] .'_description'))<div class="text-muted p-1">@lang('wncms::word.'. $tab_content['name'] .'_description')</div>@endif
                                </div>
                            </div>

                            @elseif($tab_content['type'] == 'textarea')

                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
                                    @if(!empty($tab_content['badge']))
                                    <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">{{ $tab_content['badge'] }}</span>
                                    @endif
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 fv-row">
                                    <textarea name="settings[{{ $tab_content['name'] }}]" class="form-control form-control-sm" rows="4">{{ $settings[($tab_content['name'])] ?? '' }}</textarea>
                                    @if(trans()->has('word.'. $tab_content['name'] .'_description'))<div class="text-muted p-1">@lang('wncms::word.'. $tab_content['name'] .'_description')</div>@endif
                                </div>
                            </div>

                            {{-- @elseif($tab_content['type'] == 'checkbox')

                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.' . ($tab_content['text'] ?? $tab_content['name']))
                                    @if(!empty($tab_content['badge']))
                                    <span class="badge badge-sm badge-exclusive badge-danger fw-bold fs-8 px-2 py-1 ms-2">{{ $tab_content['badge'] }}</span>
                                    @endif
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 fv-row">
                                    options
                                </div>
                            </div> --}}

                            @elseif($tab_content['type'] == 'custom')

                            @if($tab_content['name'] == 'display_model')
                            <div class="row mb-1">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">
                                    @lang('wncms::word.display_model')
                                    <br>
                                    @if(!empty($settings['show_developer_hints']))
                                    <span class="fs-xs text-gray-300">{{ $tab_content['name'] }}</span>
                                    @endif
                                </label>
                                <div class="col-lg-8 fv-row">
                                    <div class="row align-items-center mt-3">
                                        <div class="col-123 mb-3">
                                            <button type="button" class="btn_check_all btn btn-sm btn-dark px-2 py-1 fw-bold" data-target-class="model_checkbox">@lang('wncms::word.check_all')</button>
                                        </div>
                                        @foreach(wncms_get_model_names() as $modelData)
                                        @if(!empty($modelData['routes']))
                                        <div class="col-6 col-md-4 mb-1 model_checkbox">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="active_models[]" type="checkbox" value="{{ $modelData['model_name'] }}" @if(in_array($modelData['model_name'], json_decode($settings[('active_models')], true) ?? '' ?? [])) checked @endif />
                                                <span class="fw-bold ps-2 fs-6">{{ $modelData['model_name'] }}</span>
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

                            @if($nav_tab['tab_name'] == 'smtp')
                            {{-- test_smtp --}}
                            <button type="button" class="btn btn-sm btn-info fw-bold" data-bs-toggle="modal" data-bs-target="#modal_test_smtp">@lang('wncms::word.test_smtp')</button>
                            <div class="modal fade" tabindex="-1" id="modal_test_smtp">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3 class="modal-title">@lang('wncms::word.test_smtp')</h3>
                                        </div>

                                        <div class="modal-body">
                                            <div class="alert alert-info">@lang('wncms::word.please_save_settings_before_your_smtp_test')</div>
                                            <div class="form-item mb-3">
                                                <label for="recipient" class="form-label">@lang('wncms::word.recipient')</label>
                                                <input type="text" id="recipient" class="form-control">
                                            </div>
                                            <div class="smtp-test-result">
                                                <label for="recipient" class="form-label">@lang('wncms::word.result')</label>
                                                <textarea rows="4" class="form-control" disabled></textarea>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                                            <button type="button" class="btn btn-info fw-bold btn-test">@lang('wncms::word.test')</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @push('foot_js')
                            <script>
                                $('#modal_test_smtp .btn-test').on('click', function(){
                                                        console.log('smtp test');
                                                        var button = $(this);
                                                        button.prop('disabled', true)
                                                        var recipient = $('#recipient').val();
                                                        $.ajax({
                                                            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                                            url:"{{ route('settings.smtp_test') }}",
                                                            data:{
                                                                recipient:recipient,
                                                            },
                                                            type:"POST",
                                                            success:function(response){
                                                                console.log(response)
                                                                $('.smtp-test-result textarea').val(response.message);
                                                                button.prop('disabled', false)
                                                            }
                                                        });
                                                    })
                            </script>
                            @endpush
                            @endif

                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endforeach

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
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tabName = $(e.target).data('bsTarget').replace('#tab_', '');
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    });

    $('form').on('submit', function () {
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
@endpush