@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('orders.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Filter by User --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select_user')</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @if(request('user_id') == $user->id) selected @endif>
                                {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter by Status --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select_status')</option>
                        @foreach(['pending', 'paid', 'failed', 'canceled', 'completed'] as $status)
                            <option value="{{ $status }}" @if(request('status') == $status) selected @endif>
                                @lang('wncms::word.' . $status)
                            </option>
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
            {{-- Create + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', ['model_prefix' => 'orders'])
        </div>
    </div>

    {{-- Showing Items --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $orders])

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
                            <th>@lang('wncms::word.slug')</th>
                            <th>@lang('wncms::word.user')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.total_amount')</th>
                            <th>@lang('wncms::word.payment_method')</th>
                            <th>@lang('wncms::word.created_at')</th>
                            @if(request()->show_detail)
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($orders as $order)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="{{ $order->id }}" data-model-id="{{ $order->id }}"/>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('orders.edit', $order) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $order, 'route' => route('orders.destroy', $order), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->slug }}</td>
                                <td>{{ $order->user->username ?? '-' }}</td>
                                <td>@lang('wncms::word.' . $order->status)</td>
                                <td>{{ number_format($order->total_amount, 2) }}</td>
                                <td>{{ $order->payment_method ?? '-' }}</td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>

                                @if(request()->show_detail)
                                    <td>{{ $order->updated_at->format('Y-m-d H:i') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Showing Items --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $orders])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $orders->withQueryString()->links() }}
    </div>

@endsection

@push('foot_js')
    <script>
        // Submit form when checkbox is toggled
        $('.model_index_checkbox').on('change', function(){
            $(this).val($(this).is(':checked') ? '1' : '0');
            $(this).closest('form').submit();
        });
    </script>
@endpush
