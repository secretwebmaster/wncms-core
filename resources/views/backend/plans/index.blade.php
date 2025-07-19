@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

{{-- WNCMS toolbar filters --}}
<div class="wncms-toolbar-filter mt-5">
    <form action="{{ route('plans.index') }}">
        <div class="row gx-1 align-items-center position-relative my-1">
            @include('wncms::backend.common.default_toolbar_filters')

            {{-- Status Filter --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="status" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select_status')</option>
                    @foreach(['active', 'inactive'] as $status)
                    <option value="{{ $status }}" @if(request('status')==$status) selected @endif>
                        @lang('wncms::word.' . $status)
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Billing Cycle Filter --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="billing_cycle" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select') @lang('wncms::word.billing_cycle')</option>
                    @foreach(['daily', 'weekly', 'monthly', 'yearly', 'one-time'] as $cycle)
                    <option value="{{ $cycle }}" @if(request('billing_cycle')==$cycle) selected @endif>
                        @lang('wncms::word.' . $cycle)
                    </option>
                    @endforeach
                </select>
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

{{-- WNCMS toolbar buttons --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        {{-- Create + Bulk Create + Clone + Bulk Delete --}}
        @include('wncms::backend.common.default_toolbar_buttons', [
        'model_prefix' => 'plans',
        ])
    </div>
</div>

{{-- Index --}}
@include('wncms::backend.common.showing_item_of_total', ['models' => $plans])

{{-- Model Data --}}
<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle text-nowrap mb-0">
                {{-- thead --}}
                <thead class="table-dark">
                    <tr class="text-start fw-bold gs-0">
                        {{-- Checkbox --}}
                        <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom me-3">
                                <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                            </div>
                        </th>
                        <th>@lang('wncms::word.action')</th>
                        <th>@lang('wncms::word.id')</th>
                        <th>@lang('wncms::word.status')</th>
                        <th>@lang('wncms::word.name')</th>
                        <th>@lang('wncms::word.slug')</th>
                        <th>@lang('wncms::word.description')</th>
                        <th>@lang('wncms::word.free_trial_duration')</th>
                        <th>@lang('wncms::word.price')</th>

                        @if(request()->show_detail)
                        <th>@lang('wncms::word.created_at')</th>
                        <th>@lang('wncms::word.updated_at')</th>
                        @endif
                    </tr>
                </thead>

                {{-- tbody --}}
                <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                    @foreach($plans as $plan)
                    <tr>
                        {{-- Checkboxes --}}
                        <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="{{ $plan->id }}" data-model-id="{{ $plan->id }}" />
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td>
                            <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('plans.edit', $plan) }}">
                                @lang('wncms::word.edit')
                            </a>
                            @include('wncms::backend.parts.modal_delete', [
                            'model' => $plan,
                            'route' => route('plans.destroy', $plan),
                            'btn_class' => 'px-2 py-1',
                            ])
                        </td>

                        {{-- Data --}}
                        <td>{{ $plan->id }}</td>
                        <td>@include('wncms::common.table_status', ['model' => $plan])</td>
                        <td>{{ $plan->name }}</td>
                        <td>{{ $plan->slug }}</td>
                        <td>{{ $plan->description }}</td>
                        <td>{{ $plan->free_trial_duration }}</td>
                        {{-- Plan Prices --}}
                        <td>
                            @if($plan->prices->isNotEmpty())
                            <table class="table table-xs table-bordered mb-0 text-center align-middle w-auto d-inline-block">
                                <thead>
                                    <tr class="table-dark text-gray-100">
                                        @foreach($plan->prices as $price)
                                        <th title="#{{ $price->id }}">
                                            @if($price->is_lifetime)
                                            @lang('wncms::word.lifetime')
                                            @else
                                            {{ $price->duration }} @lang('wncms::word.' . $price->duration_unit)
                                            @endif
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($plan->prices as $price)
                                        <td>{{ rtrim(rtrim(number_format($price->amount, 6, '.', ''), '0'), '.') }}</td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                            @else
                            <span class="text-muted">@lang('wncms::word.no_prices')</span>
                            @endif
                        </td>


                        @if(request()->show_detail)
                        <td>{{ $plan->created_at }}</td>
                        <td>{{ $plan->updated_at }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Index --}}
@include('wncms::backend.common.showing_item_of_total', ['models' => $plans])

{{-- Pagination --}}
<div class="mt-5">
    {{ $plans->withQueryString()->links() }}
</div>

@endsection

@push('foot_js')
<script>
    // Automatically submit form when checkbox changes
        $('.model_index_checkbox').on('change', function(){
            if ($(this).is(':checked')) {
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        });
</script>
@endpush