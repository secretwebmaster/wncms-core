@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
    <h2>@lang('wncms::word.order_details')</h2>
    <table border="1">
        <tr>
            <th>@lang('wncms::word.order_id')</th>
            <td>{{ $order->id }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.slug')</th>
            <td>{{ $order->slug }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.status')</th>
            <td>@lang('wncms::word.' . $order->status)</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.total_amount')</th>
            <td>{{ number_format($order->total_amount, 2) }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.payment_method')</th>
            <td>{{ $order->payment_gateway?->slug ?? 'unknow' }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.coupon')</th>
            <td>{{ $order->coupon->code ?? __('wncms::word.no_coupon_applied') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.original_amount')</th>
            <td>{{ $order->original_amount ? number_format($order->original_amount, 2) : __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.email')</th>
            <td>{{ $order->email ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.nickname')</th>
            <td>{{ $order->nickname ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.telephone')</th>
            <td>{{ $order->tel ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.address')</th>
            <td>{{ $order->address ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.payment_gateway')</th>
            <td>{{ $order->paymentGateway->name ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.tracking_code')</th>
            <td>{{ $order->tracking_code ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.remark')</th>
            <td>{{ $order->remark ?? __('wncms::word.n_a') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.created_at')</th>
            <td>{{ $order->created_at }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.updated_at')</th>
            <td>{{ $order->updated_at }}</td>
        </tr>
    </table>
    @if ($order->status == 'pending_payment')
        <p><strong>@lang('wncms::word.status'):</strong> @lang('wncms::word.status.pending_payment_message')</p>
    @endif
@endsection
