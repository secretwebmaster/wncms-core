@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- Toolbar Filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('credit_transactions.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Filter by User --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.user')</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @if(request('user_id') == $user->id) selected @endif>
                                {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter by Credit Type --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="credit_type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.credit_type')</option>
                        @foreach($creditTypes as $type)
                            <option value="{{ $type }}" @if(request('credit_type') == $type) selected @endif>
                                @lang('wncms::word.' . $type)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter by Transaction Type --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="transaction_type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.transaction_type')</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" @if(request('transaction_type') == $type) selected @endif>
                                @lang('wncms::word.' . $type)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
                </div>
            </div>
        </form>
    </div>

    {{-- Toolbar Buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            @include('wncms::backend.common.default_toolbar_buttons', ['model_prefix' => 'credit_transactions'])
        </div>
    </div>

    {{-- Showing Items --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $creditTransactions])

    {{-- Data Table --}}
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
                            <th>@lang('wncms::word.user')</th>
                            <th>@lang('wncms::word.credit_type')</th>
                            <th>@lang('wncms::word.transaction_type')</th>
                            <th>@lang('wncms::word.amount')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.created_at')</th>
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($creditTransactions as $creditTransaction)
                            <tr>
                                {{-- Checkbox --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="{{ $creditTransaction->id }}" data-model-id="{{ $creditTransaction->id }}"/>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    @include('wncms::backend.parts.modal_delete', [
                                        'model' => $creditTransaction,
                                        'route' => route('credit_transactions.destroy', $creditTransaction),
                                        'btn_class' => 'px-2 py-1'
                                    ])
                                </td>

                                {{-- Data --}}
                                <td>{{ $creditTransaction->id }}</td>
                                <td>{{ $creditTransaction->user->username ?? '-' }}</td>
                                <td>@lang('wncms::word.' . $creditTransaction->credit_type)</td>
                                <td>@lang('wncms::word.' . $creditTransaction->transaction_type)</td>
                                <td>{{ number_format($creditTransaction->amount, 2) }}</td>
                                <td>{{ $creditTransaction->remark }}</td>
                                <td>{{ $creditTransaction->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Showing Items --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $creditTransactions])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $creditTransactions->withQueryString()->links() }}
    </div>

@endsection
