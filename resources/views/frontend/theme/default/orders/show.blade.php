@extends('frontend.theme.default.layouts.app')

@section('content')
<h2>
    {{ $order->slug }}
</h2>

<div>
    @if($order->status == "pending_payment")

    <form action="{{ route('frontend.orders.pay', ['order' => $order]) }}" method="POST">
        @csrf
        <input type="hidden" name="order_id" value="{{ $order->id }}">
        <select name="payment_gateway" id="">
            <option value="">@lang('wncms::word.please_select')</option>
            @foreach($paymentGateways as $paymentGateway)
                <option value="{{ $paymentGateway['slug'] }}">{{ $paymentGateway['name'] }}</option>
            @endforeach
        </select>
        <button type="submit">Pay</button>
    </form>

    @else
    <p>
        {{ $order->status }}
    </p>
    @endif

</div>
@endsection
