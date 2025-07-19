@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('transactions.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- Filter by status --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select') @lang('wncms::word.status')</option>
                        @foreach(['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @if(request('status') === $status) selected @endif>
                                @lang('wncms::word.' . $status)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
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
                'model_prefix' => 'transactions',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $transactions])

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
                            <th>@lang('wncms::word.order_id')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.amount')</th>
                            <th>@lang('wncms::word.payment_method')</th>
                            <th>@lang('wncms::word.ref_id')</th>
                            <th>@lang('wncms::word.is_fraud')</th>
                            <th>@lang('wncms::word.created_at')</th>

                            @if(request()->show_detail)
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($transactions as $transaction)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $transaction->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('transactions.edit', $transaction) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $transaction, 'route' => route('transactions.destroy', $transaction), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $transaction->id }}</td>
                                <td>{{ $transaction->order_id }}</td>
                                <td>@lang('wncms::word.' . $transaction->status)</td>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                <td>{{ $transaction->payment_method ?? '-' }}</td>
                                <td>{{ $transaction->ref_id ?? '-' }}</td>
                                <td>@include('wncms::common.table_is_active', ['model' => $transaction, 'attribute' => 'is_fraud'])</td>
                                <td>{{ $transaction->created_at }}</td>

                                @if(request()->show_detail)
                                    <td>{{ $transaction->updated_at }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $transactions])

    {{-- Pagination --}}
    @if($transactions->hasPages())
        <div class="mt-5">
            {{ $transactions->withQueryString()->links() }}
        </div>
    @endif

@endsection

@push('foot_js')
    <script>
        // Automatically submit the form when checkboxes are toggled
        $('.model_index_checkbox').on('change', function() {
            $(this).val($(this).is(':checked') ? '1' : '0').closest('form').submit();
        });
    </script>
@endpush
