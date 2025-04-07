@extends('wncms::layouts.backend')

@push('head_css')
{{-- <style>
    td,
    th {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis
    }
</style> --}}
<style>
    table {
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }

    th,
    td {
        border: 1px solid black;
        padding: 10px;
        position: relative;
        overflow: hidden;
    }

    th {
        background: #f4f4f4;
    }

    .resizer {
        position: absolute;
        right: 0;
        top: 0;
        width: 5px;
        height: 100%;
        cursor: col-resize;
        /* background: #ccc; */
        z-index: 1;
    }

    .resizing {
        user-select: none;
    }
</style>
@endpush


@section('content')

@include('wncms::backend.parts.message')

{{-- WNCMS toolbar filters --}}
<div class="wncms-toolbar-filter mt-5">
    <form action="{{ route('clicks.index') }}">
        <div class="row gx-1 align-items-center position-relative my-1">

            @include('wncms::backend.common.default_toolbar_filters')

            {{-- clickable_types --}}
            @if(!empty($clickableTypes))
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="clickable_type" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select')@lang('wncms::word.clickable_type')</option>
                    @foreach($clickableTypes as $clickableType)
                    <option value="{{ $clickableType }}" @if($clickableType==request()->clickable_type) selected @endif>{{ $clickableType }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Add custom toolbar item here --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="text" class="form-control form-control-sm w-50px" name="clickable_id" value="{{ request()->clickable_id }}" placeholder="id">
            </div>

            {{-- channel --}}
            @if(!empty($channels))
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="channel" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select')@lang('wncms::word.channel')</option>
                    @foreach($channels as $channel)
                    <option value="{{ $channel->slug }}" @if($channel->slug == request()->channel) selected @endif>{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Start DateTime -->
            <div class="col-6 col-md-auto  mb-3">
                <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control form-control-sm" value="{{ request()->start_datetime }}">
            </div>

            <!-- End DateTime -->
            <div class="col-6 col-md-auto  mb-3">
                <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control form-control-sm" value="{{ request()->end_datetime }}">
            </div>

            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
            </div>
        </div>

        {{-- Checkboxes --}}
        <div class="d-flex flex-wrap">
            @foreach(['show_detail'] as $show)
            <div class="mb-3 ms-0">
                <div class="form-check form-check-sm form-check-custom me-2">
                    <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                    <label class="form-check-label fw-bold ms-1">@lang('wncms::word.' . $show)</label>
                </div>
            </div>
            @endforeach
        </div>
    </form>
</div>

{{-- Date --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        <button class="btn btn-sm btn-dark fw-bold" data-start="" data-end="">@lang('wncms::word.all_time')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfDay()->format('Y-m-d\TH:i') }}">@lang('wncms::word.today')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subDay()->startOfDay()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subDay()->endOfDay()->format('Y-m-d\TH:i') }}">@lang('wncms::word.yesterday')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfMonth()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfMonth()->format('Y-m-d\TH:i') }}">@lang('wncms::word.this_month')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d\TH:i') }}">@lang('wncms::word.last_month')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfYear()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfYear()->format('Y-m-d\TH:i') }}">@lang('wncms::word.this_year')</button>
        <button class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subYear()->startOfYear()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subYear()->endOfYear()->format('Y-m-d\TH:i') }}">@lang('wncms::word.last_year')</button>
    </div>
</div>

{{-- WNCMS toolbar buttons --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        {{-- Create + Bilk Create + Clone + Bulk Delete --}}
        {{-- @include('wncms::backend.common.default_toolbar_buttons', [
        'model_prefix' => 'clicks',
        ]) --}}
    </div>
</div>

{{-- Message box --}}
<div class="alert alert-info">
    <div>Clicks: {{ $clicks->total() }}</div>
</div>

{{-- Chart.js - Clicks over last 30 days --}}
<div class="my-5">
    <canvas id="clicksChart" height="300"></canvas>
</div>


{{-- Index --}}
@include('wncms::backend.common.showing_item_of_total', ['models' => $clicks])

{{-- Model Data --}}
<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-xs table-hover table-bordered align-middle text-nowrap mb-0">

                {{-- thead --}}
                <thead class="table-dark">
                    <tr class="text-start fw-bold gs-0">
                        {{-- Checkbox --}}
                        {{-- <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom me-3">
                                <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                            </div>
                        </th> --}}
                        {{-- <th>@lang('wncms::word.action')</th> --}}
                        <th>@lang('wncms::word.id') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.clickable_type') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.clickable_id') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.clickable_name') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.name') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.value') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.channel') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.ip') <div class="resizer"></div></th>
                        <th>@lang('wncms::word.referer') <div class="resizer"></div></th>
                        @foreach($parameters as $parameter)
                        <th title="{{ $parameter->key }}" class="text-gray-500">{{ $parameter->name }} <div class="resizer"></div></th>
                        @endforeach
                        <th>@lang('wncms::word.created_at')</th>

                        @if(request()->show_detail)
                        <th>@lang('wncms::word.updated_at')</th>
                        @endif
                    </tr>
                </thead>

                {{-- tbody --}}
                <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                    @foreach($clicks as $click)
                    <tr>
                        {{-- Checkboxes --}}
                        {{-- <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $click->id }}" />
                            </div>
                        </td> --}}
                        {{-- Actions --}}
                        {{-- <td>
                            <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('clicks.edit' , $click) }}">@lang('wncms::word.edit')</a>
                            @include('wncms::backend.parts.modal_delete' , ['model'=>$click , 'route' => route('clicks.destroy' , $click), 'btn_class' => 'px-2 py-1'])
                        </td> --}}

                        {{-- Data --}}
                        <td>{{ $click->id }}</td>
                        <td>{{ $click->clickable_type }}</td>
                        <td class="clickable-id-cell text-primary cursor-pointer"
                            data-clickable-id="{{ $click->clickable_id }}"
                            data-clickable-type="{{ $click->clickable_type }}">
                            {{ $click->clickable_id }}
                        </td>
                        <td>{{ $click->clickable?->name ?? $click->clickable?->title ?? '' }}</td>
                        <td>{{ $click->name }}</td>
                        <td>{{ $click->value }}</td>
                        <td title="{{ $click->channel?->slug }}">{{ $click->channel?->name }}</td>
                        <td>{{ $click->ip }}</td>
                        <td>{{ $click->referer }}</td>
                        @foreach($parameters as $parameter)
                        <td>
                            @if(!empty($click->parameters[$parameter->key]))
                            <span title="{{ $click->parameters[$parameter->key] }}">{{ $click->parameters[$parameter->key] }}</span>
                            @endif
                        </td>
                        @endforeach
                        <td>{{ $click->created_at }}</td>

                        @if(request()->show_detail)
                        <td>{{ $click->updated_at }}</td>
                        @endif

                    <tr>
                        @endforeach
                </tbody>

            </table>
        </div>
    </div>
</div>

{{-- Index --}}
@include('wncms::backend.common.showing_item_of_total', ['models' => $clicks])

{{-- Pagination --}}
<div class="mt-5">
    {{ $clicks->withQueryString()->links() }}
</div>

@endsection

@push('foot_js')
<script>
    //修改checkbox時直接提交
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
</script>
@endpush

@push('foot_js')

{{-- seting datetime --}}
<script>
    // Attach event listener to all buttons inside the toolbar
    document.querySelectorAll('.wncms-toolbar-buttons button').forEach(button => {
        button.addEventListener('click', function () {
            // Get attributes from the button
            const startDate = this.getAttribute('data-start');
            const endDate = this.getAttribute('data-end');

            // Update start_datetime field
            document.getElementById('start_datetime').value = startDate ? startDate : ''; // Set to empty if not exist

            // Update end_datetime field
            document.getElementById('end_datetime').value = endDate ? endDate : ''; // Set to empty if not exist
        });
    });
</script>
@endpush


@push('foot_js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const resizers = document.querySelectorAll('.resizer');
    
        resizers.forEach(resizer => {
            let startX, startWidth, column;
    
            resizer.addEventListener('mousedown', function (event) {
                startX = event.clientX;
                column = resizer.parentElement;
                startWidth = column.offsetWidth;
                column.classList.add('resizing');
    
                console.log(`Start resizing column: ${column.innerText.trim()}`);
                console.log(`Start X: ${startX}px, Start Width: ${startWidth}px`);
    
                document.addEventListener('mousemove', resizeColumn);
                document.addEventListener('mouseup', stopResizing);
            });
    
            function resizeColumn(event) {
                const newWidth = startWidth + (event.clientX - startX);
                column.style.width = newWidth + 'px';
    
                console.log(`Moving - New Width: ${newWidth}px (Delta: ${event.clientX - startX}px)`);
            }
    
            function stopResizing() {
                console.log(`Stop resizing: ${column.innerText.trim()}, Final Width: ${column.offsetWidth}px`);
                column.classList.remove('resizing');
                document.removeEventListener('mousemove', resizeColumn);
                document.removeEventListener('mouseup', stopResizing);
            }
        });
    });
</script>
@endpush

@push('foot_js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const clicksChartCtx = document.getElementById('clicksChart').getContext('2d');

    const clicksChart = new Chart(clicksChartCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: '@lang('wncms::word.clicks')',
                data: {!! json_encode($chartCounts ?? []) !!},
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision:0
                    }
                }
            }
        }
    });
</script>
@endpush

@push('foot_js')
<script>
    $(document).ready(function () {
    $('.clickable-id-cell').on('click', function () {
        const id = $(this).data('clickable-id');
        const type = $(this).data('clickable-type');

        const url = new URL(window.location.href);
        const params = url.searchParams;

        // Check if clickable_id or clickable_type are missing or empty
        if (!params.has('clickable_id') || !params.get('clickable_id')) {
            params.set('clickable_id', id);
        }

        if (!params.has('clickable_type') || !params.get('clickable_type')) {
            params.set('clickable_type', type);
        }

        // Redirect with updated query string
        window.location.href = url.toString();
    });
});
</script>
@endpush