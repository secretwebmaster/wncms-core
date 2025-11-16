<div class="tab-pane fade {{ $activeTab === 'api_access' ? 'show active' : '' }}" id="tab_api_access" role="tabpanel">
    <div class="card">
        <div class="collapse show">
            <div class="card-body border-top p-6">

                {{-- Global API Access Switch --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">
                        @lang('wncms::word.enable_api_access')
                        <br>
                        @if(!empty($settings['show_developer_hints']))
                        <span class="fs-xs text-gray-300">enable_api_access</span>
                        @endif
                    </label>
                    <div class="col-lg-8 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input type="hidden" name="settings[enable_api_access]" value="0">
                            <input class="form-check-input w-35px h-20px border border-1 border-secondary" type="checkbox" name="settings[enable_api_access]" value="1" {{ ($settings['enable_api_access'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle rounded border border-1 border-dark text-nowrap" style="--bs-border-color:black">
                        <thead class="table-dark">
                            <tr class="fw-bold text-center">

                                {{-- Model --}}
                                <th class="text-start api-col-model">
                                    @lang('wncms::word.model')
                                </th>

                                {{-- Enable model --}}
                                <th class="api-col-fixed">
                                    @lang('wncms::word.enable')
                                </th>

                                {{-- Common actions --}}
                                @foreach($commonActions as $action)
                                <th class="api-col-fixed">
                                    {{ __('wncms::word.' . $action) }}
                                </th>
                                @endforeach

                                {{-- Others --}}
                                <th class="api-col-grow w-100">
                                    @lang('wncms::word.others')
                                </th>

                                {{-- Row select-all --}}
                                <th class="api-col-fixed">
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input type="checkbox" class="form-check-input check_all_api_global">
                                        <label class="ms-2">@lang('wncms::word.select_all')</label>
                                    </div>
                                </th>

                            </tr>
                        </thead>

                        <tbody>

                            @foreach($apiModels as $modelKey => $model)
                            @php
                            $modelClass = $model['class'];
                            $routes = $model['routes'];
                            $routeByAction = collect($routes)->keyBy('action');
                            @endphp

                            <tr class="api-row-{{ $modelKey }}">

                                {{-- Model name --}}
                                <td class="fw-bold">@lang('wncms::word.' . $modelKey)</td>

                                {{-- Enable model API --}}
                                @php
                                $enableKey = 'enable_api_' . $modelKey;
                                @endphp
                                <td class="text-center api-checkbox-cell">
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input type="hidden" name="settings[{{ $enableKey }}]" value="0">
                                        <input class="form-check-input api-checkbox api-row-{{ $modelKey }}-checkbox"
                                            type="checkbox"
                                            name="settings[{{ $enableKey }}]"
                                            value="1"
                                            {{ ($settings[$enableKey] ?? false) ? 'checked' : '' }}>
                                    </div>
                                </td>

                                {{-- COMMON ACTIONS --}}
                                @foreach($commonActions as $action)
                                @php
                                $route = $routeByAction->get($action);
                                @endphp

                                <td class="text-center api-checkbox-cell">
                                    @if($route)
                                    @php
                                    $key = $route['key'];
                                    $label = $modelClass::getApiLabel($route);
                                    @endphp

                                    <div class="form-check form-check-sm form-check-custom">
                                        <input type="hidden" name="settings[{{ $key }}]" value="0">
                                        <input class="form-check-input api-checkbox api-row-{{ $modelKey }}-checkbox" type="checkbox" name="settings[{{ $key }}]" value="1" title="{{ $label }}" {{ ($settings[$key] ?? false) ? 'checked' : '' }}>
                                        @if(!empty($settings['show_developer_hints']))
                                        <label class="ms-2 text-secondary">{{ $key }}</label>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-muted"></span>
                                    @endif
                                </td>
                                @endforeach

                                {{-- OTHER ACTIONS --}}
                                <td class="text-start api-checkbox-cell">
                                    @php
                                    $otherRouteList = array_filter($routes, fn($r) => !in_array($r['action'], $commonActions));
                                    @endphp

                                    @if(empty($otherRouteList))
                                    <span class="text-muted">-</span>
                                    @else
                                    @foreach($otherRouteList as $route)
                                    @php
                                    $key = $route['key'];
                                    $label = $modelClass::getApiLabel($route);
                                    @endphp

                                    <div class="form-check form-check-sm form-check-custom mb-1">
                                        <input type="hidden" name="settings[{{ $key }}]" value="0">
                                        <input class="form-check-input api-checkbox api-row-{{ $modelKey }}-checkbox" type="checkbox" value="1" {{ ($settings[$key] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label ms-1">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                    @endif
                                </td>

                                {{-- ROW SELECT-ALL --}}
                                <td class="text-center">
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input type="checkbox" class="form-check-input check_all_api_row" data-target-row="{{ $modelKey }}">
                                    </div>
                                </td>

                            </tr>

                            @endforeach

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

@push('foot_js')
<script>
    // Global select/unselect ALL checkboxes in the table
    $('.check_all_api_global').on('change', function() {
        var checked = $(this).is(':checked');
        $('.api-checkbox').prop('checked', checked);
        $('.check_all_api_row').prop('checked', checked);
    });

    // Per-row select/unselect ALL checkboxes for that model
    $('.check_all_api_row').on('change', function() {
        var row = $(this).data('target-row');
        var checked = $(this).is(':checked');

        $('.api-row-' + row + '-checkbox').prop('checked', checked);
    });
</script>
@endpush