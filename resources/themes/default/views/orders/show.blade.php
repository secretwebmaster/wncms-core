@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>@lang('wncms-ecommerce::word.order_detail')</h2>
            <div class="table-container">
                <table class="kv-table">
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
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="mb-3 text-base font-semibold text-slate-900">@lang('wncms-ecommerce::word.order_items_list')</h3>

            @if($order->order_items->count())
            <div class="table-container">
                <table>
                    <tr>
                        <th>@lang('wncms-ecommerce::word.item_id')</th>
                        <th>@lang('wncms-ecommerce::word.type')</th>
                        <th>@lang('wncms-ecommerce::word.name')</th>
                        <th>@lang('wncms-ecommerce::word.quantity')</th>
                        <th>@lang('wncms-ecommerce::word.amount')</th>
                    </tr>
                    @foreach($order->order_items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->type }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @else
            <p>@lang('wncms-ecommerce::word.no_items_found')</p>
            @endif
        </section>

        @if($order->status == 'pending_payment')
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-slate-900">@lang('wncms-ecommerce::word.payment')</h3>
                <form action="{{ route('frontend.orders.pay', ['slug' => $order->slug]) }}" method="POST" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <select name="payment_gateway" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <option value="">@lang('wncms-ecommerce::word.please_select')</option>
                        @foreach($paymentGateways as $paymentGateway)
                            <option value="{{ $paymentGateway['slug'] }}">{{ $paymentGateway['name'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">@lang('wncms-ecommerce::word.pay_now')</button>
                </form>
            </section>
        @endif
    </div>
</main>
@endsection

@push('foot_js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderSlug = "{{ $order->slug }}";
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

            if (!response.ok) {
                return;
            }

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
