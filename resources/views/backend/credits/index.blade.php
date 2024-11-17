@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('credits.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Filter by Credit Type --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="credit_type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.credit_type')</option>
                        @foreach(\Wncms\Enums\CreditType::values() as $type)
                            <option value="{{ $type }}" @if($type == request()->credit_type) selected @endif>@lang('wncms::word.' . $type)</option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                </div>
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            @include('wncms::backend.common.default_toolbar_buttons', ['model_prefix' => 'credits'])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $credits])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">

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
                            <th>@lang('wncms::word.user')</th>
                            <th>@lang('wncms::word.credit_type')</th>
                            <th>@lang('wncms::word.amount')</th>
                            <th>@lang('wncms::word.created_at')</th>
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($credits as $credit)
                            <tr>
                                {{-- Checkbox --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="{{ $credit->id }}" data-model-id="{{ $credit->id }}"/>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('credits.edit', $credit) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $credit, 'route' => route('credits.destroy', $credit), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $credit->id }}</td>
                                <td>{{ $credit->user?->username ?? '-' }}</td>
                                <td>@lang('wncms::word.' . $credit->credit_type)</td>
                                <td>{{ $credit->amount }}</td>
                                <td>{{ $credit->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    @include('wncms::backend.common.showing_item_of_total', ['models' => $credits])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $credits->withQueryString()->links() }}
    </div>

@endsection
