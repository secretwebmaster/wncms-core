@extends('frontend.theme.default.layouts.app')

@section('content')

<h2>@lang('wncms-ecommerce::word.order_detail')</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>@lang('wncms-ecommerce::word.order_id')</th>
        <td>{{ $order->slug }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.status')</th>
        <td data-order-status>{{ __('wncms-ecommerce::word.' . $order->status) }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.total_amount')</th>
        <td>{{ number_format($order->total_amount, 2) }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.payment_gateway')</th>
        <td>{{ $order->payment_gateway->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.tracking_code')</th>
        <td>{{ $order->tracking_code ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.remark')</th>
        <td>{{ $order->remark ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms-ecommerce::word.created_at')</th>
        <td>{{ $order->created_at }}</td>
    </tr>
</table>

<br>

<h3>@lang('wncms-ecommerce::word.order_items_list')</h3>

@if($order->order_items->count())
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>@lang('wncms-ecommerce::word.item_id')</th>
        <th>@lang('wncms-ecommerce::word.type')</th>
        <th>@lang('wncms-ecommerce::word.name')</th>
        <th>@lang('wncms-ecommerce::word.quantity')</th>
        <th>@lang('wncms-ecommerce::word.amount')</th>
    </tr>
    @foreach($order->order_items as $item)
    {{-- @dd($item) --}}
        <tr>
            <td>{{ $item->id }}</td>
            {{-- <td>{{ $item->order_itemable?->priceable?->type ?? '' }}</td> --}}
            <td>{{ $item->type }}</td>
            {{-- <td>{{ $item->order_itemable?->priceable?->name ?? '' }}</td> --}}
            <td>{{ $item->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->amount, 2) }}</td>
        </tr>
    @endforeach
</table>
@else
<p>@lang('wncms-ecommerce::word.no_items_found')</p>
@endif

<br>

@if($order->status == 'pending_payment')
    <h3>@lang('wncms-ecommerce::word.payment')</h3>
    <form action="{{ route('frontend.orders.pay', ['slug' => $order->slug]) }}" method="POST">
        @csrf
        <select name="payment_gateway">
            <option value="">@lang('wncms-ecommerce::word.please_select')</option>
            @foreach($paymentGateways as $paymentGateway)
                <option value="{{ $paymentGateway['slug'] }}">{{ $paymentGateway['name'] }}</option>
            @endforeach
        </select>
        <button type="submit">@lang('wncms-ecommerce::word.pay_now')</button>
    </form>
@endif

@endsection

@push('foot_js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderSlug = "{{ $order->slug }}";
    const statusCell = document.querySelector('[data-order-status]');
    const statusUrl = "{{ route('frontend.orders.status') }}";
    const initialStatus = "{{ $order->status }}";

    async function fetchOrderStatus() {
        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ slug: orderSlug }),
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.status === 'completed') {
                clearInterval(timer);
                location.reload();
            }

        } catch (err) {
            console.error('Error fetching order status:', err);
        }
    }

    let timer = null;
    if (initialStatus === 'pending_payment') {
        timer = setInterval(fetchOrderStatus, 3000);
    }
});
</script>
@endpush
