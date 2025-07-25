@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

{{-- Toolbar Filters --}}
<div class="wncms-toolbar-filter mt-5">
    <form action="{{ route('order_items.index') }}">
        <div class="row gx-1 align-items-center position-relative my-1">

            @include('wncms::backend.common.default_toolbar_filters')

            {{-- Order Id --}}
            <div class="d-flex align-items-center col-12 col-md-auto mb-3 ms-0 me-1">
                <input type="text" name="order_id" value="{{ request()->order_id }}" class="form-control form-control-sm" placeholder="@lang('wncms::word.order_id')" />
            </div>

            {{-- Filter by Item Type --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <select name="order_itemable_type" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.select_item', ['item_name' => __('wncms::word.order_itemable_type')])</option>
                    @foreach($itemTypes as $type)
                    <option value="{{ $type }}" @if(request('order_itemable_type')==$type) selected @endif>
                        {{ class_basename($type) }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter by Item ID --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="text" name="order_itemable_type" class="form-control form-control-sm"
                    value="{{ request('order_itemable_type') }}" placeholder="@lang('wncms::word.order_itemable_type')">
            </div>

            {{-- Submit --}}
            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
            </div>
        </div>
    </form>
</div>

{{-- Toolbar Buttons --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        @include('wncms::backend.common.default_toolbar_buttons', ['model_prefix' => 'order_items'])
    </div>
</div>

{{-- Model Data --}}
<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle text-nowrap mb-0">
                {{-- thead --}}
                <thead class="table-dark">
                    <tr>
                        <th>@lang('wncms::word.action')</th>
                        <th>@lang('wncms::word.id')</th>
                        <th>@lang('wncms::word.user_order')</th>
                        <th>@lang('wncms::word.order_itemable_type')</th>
                        <th>@lang('wncms::word.quantity')</th>
                        <th>@lang('wncms::word.price')</th>
                        <th>@lang('wncms::word.created_at')</th>
                    </tr>
                </thead>
                {{-- tbody --}}
                <tbody>
                    @foreach($orderItems as $orderItem)
                    <tr>
                        <td>
                            <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('order_items.edit', $orderItem) }}">@lang('wncms::word.edit')</a>
                            @include('wncms::backend.parts.modal_delete', ['model' => $orderItem, 'route' => route('order_items.destroy', $orderItem)])
                        </td>
                        <td>{{ $orderItem->id }}</td>
                        <td>{{ $orderItem->order->slug ?? '-' }}</td>
                        <td>{{ $orderItem->order_itemable_type }}</td>
                        <td>{{ $orderItem->quantity }}</td>
                        <td>{{ number_format($orderItem->amount, 2) }}</td>
                        <td>{{ $orderItem->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-5">
    {{ $orderItems->withQueryString()->links() }}
</div>

@endsection