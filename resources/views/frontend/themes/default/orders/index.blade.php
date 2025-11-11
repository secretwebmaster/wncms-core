@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div>
    <h1>@lang('wncms::word.orders')</h1>

    <table>
        <thead>
            <tr>
                <th>@lang('wncms::word.order_id')</th>
                <th>@lang('wncms::word.total_amount')</th>
                <th>@lang('wncms::word.status')</th>
                <th>@lang('wncms::word.payment_method')</th>
                <th>@lang('wncms::word.created_at')</th>
                <th>@lang('wncms::word.action')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ number_format($order->total_amount, 2) }}</td>
                    <td>@lang('wncms::word.' . $order->status)</td>
                    <td>{{ $order->payment_gateway?->name }}</td>
                    <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        <a href="{{ route('frontend.orders.show', ['slug' => $order->slug]) }}">
                            @lang('wncms::word.view_details')
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">@lang('wncms-ecommerce::word.no_orders')</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
