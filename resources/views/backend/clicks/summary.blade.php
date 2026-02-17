@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

<div class="wncms-toolbar-filter mt-5">
    <form action="{{ route('clicks.summary') }}">
        <div class="row gx-1 align-items-center position-relative my-1">

            @include('wncms::backend.common.default_toolbar_filters')

            @if(!empty($clickableTypes))
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="clickable_type" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select')@lang('wncms::word.clickable_type')</option>
                    @foreach($clickableTypes as $className => $displayName)
                    <option value="{{ $className }}" @selected(request('clickable_type')==$className)>
                        {{ $displayName }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="text" class="form-control form-control-sm w-75px" name="clickable_id" value="{{ request()->clickable_id }}" placeholder="id">
            </div>

            @if(!empty($channels))
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="channel" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select')@lang('wncms::word.channel')</option>
                    @foreach($channels as $channel)
                    <option value="{{ $channel->slug }}" @selected($channel->slug == request()->channel)>{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-6 col-md-auto mb-3">
                <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control form-control-sm" value="{{ request()->start_datetime }}">
            </div>

            <div class="col-6 col-md-auto mb-3">
                <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control form-control-sm" value="{{ request()->end_datetime }}">
            </div>

            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
            </div>
        </div>

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

<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="" data-end="">@lang('wncms::word.all_time')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfDay()->format('Y-m-d\TH:i') }}">@lang('wncms::word.today')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subDay()->startOfDay()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subDay()->endOfDay()->format('Y-m-d\TH:i') }}">@lang('wncms::word.yesterday')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfMonth()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfMonth()->format('Y-m-d\TH:i') }}">@lang('wncms::word.this_month')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d\TH:i') }}">@lang('wncms::word.last_month')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->startOfYear()->format('Y-m-d\TH:i') }}" data-end="{{ now()->endOfYear()->format('Y-m-d\TH:i') }}">@lang('wncms::word.this_year')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold" data-start="{{ now()->subYear()->startOfYear()->format('Y-m-d\TH:i') }}" data-end="{{ now()->subYear()->endOfYear()->format('Y-m-d\TH:i') }}">@lang('wncms::word.last_year')</button>
    </div>
</div>

<div class="alert alert-info">
    <div>@lang('wncms::word.clicks'): {{ $totalClicks }}</div>
    <div>@lang('wncms::word.total'): {{ $summaryRows->total() }}</div>
</div>

@include('wncms::backend.common.showing_item_of_total', ['models' => $summaryRows])

<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-xs table-hover table-bordered align-middle text-nowrap mb-0">
                <thead class="table-dark">
                    <tr class="text-start fw-bold gs-0">
                        @if(request()->show_detail)
                        <th>@lang('wncms::word.id')</th>
                        @endif
                        <th>@lang('wncms::word.type')</th>
                        <th>@lang('wncms::word.item_id')</th>
                        <th>@lang('wncms::word.name')</th>
                        <th>@lang('wncms::word.total')</th>
                        @foreach($dateList as $day)
                        <th>{{ \Carbon\Carbon::parse($day)->format('m-d') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($summaryRows as $row)
                    <tr>
                        @if(request()->show_detail)
                        <td>{{ $row->record_id }}</td>
                        @endif
                        <td title="{{ $row->clickable_type }}">{{ $row->clickable_type_label }}</td>
                        <td>
                            <a href="{{ route('clicks.index', ['clickable_type' => $row->clickable_type, 'clickable_id' => $row->clickable_id]) }}" class="text-primary fw-bold">
                                {{ $row->clickable_id }}
                            </a>
                        </td>
                        <td>{{ $row->clickable_name }}</td>
                        <td>{{ $row->total }}</td>
                        @foreach($dateList as $day)
                        <td>{{ $row->daily_counts[$day] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ (request()->show_detail ? 5 : 4) + $dateList->count() }}" class="text-center text-muted py-8">@lang('wncms::word.no_data')</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-5">
    {{ $summaryRows->withQueryString()->links() }}
</div>

@endsection

@push('foot_js')
<script>
    $('.model_index_checkbox').on('change', function(){
        if($(this).is(':checked')){
            $(this).val('1');
        } else {
            $(this).val('0');
        }
        $(this).closest('form').submit();
    });

    document.querySelectorAll('.wncms-toolbar-buttons button').forEach(button => {
        button.addEventListener('click', function() {
            const startDate = this.getAttribute('data-start');
            const endDate = this.getAttribute('data-end');
            document.getElementById('start_datetime').value = startDate ? startDate : '';
            document.getElementById('end_datetime').value = endDate ? endDate : '';
        });
    });
</script>
@endpush
