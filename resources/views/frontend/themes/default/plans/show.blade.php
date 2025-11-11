@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div>
    <h2>@lang('wncms::word.plan_detail')</h2>
    <table>
        <tr>
            <th>@lang('wncms::word.name')</th>
            <td>{{ $plan->name }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.description')</th>
            <td>{{ $plan->description ?? __('wncms::word.not_provided') }}</td>
        </tr>
        <tr>
            <th>@lang('wncms::word.free_trial_duration')</th>
            <td>
                @if($plan->free_trial_duration)
                    {{ $plan->free_trial_duration }} @lang('wncms::word.days')
                @else
                    @lang('wncms::word.not_available')
                @endif
            </td>
        </tr>
        <tr>
            <th>@lang('wncms::word.status')</th>
            <td>@lang('wncms::word.' . $plan->status)</td>
        </tr>
    </table>

    <h2>@lang('wncms::word.pricing_details')</h2>

    <table>
        <thead>
            <tr>
                <th>@lang('wncms::word.duration')</th>
                <th>@lang('wncms::word.price')</th>
                <th>@lang('wncms::word.action')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plan->prices as $price)
                <tr>
                    <td>
                        @if($price->is_lifetime)
                            @lang('wncms::word.lifetime')
                        @else
                            {{ $price->duration }} @lang('wncms::word.' . $price->duration_unit)
                        @endif
                    </td>
                    <td>{{ config('wncms.ecommerce.default_currency_unit') }} {{ config('wncms.ecommerce.default_currency_symbol') }}{{ number_format($price->amount, 2) }}</td>
                    <td>
                        @if(!auth()->check())
                            <button disabled>@lang('wncms::word.login_first')</button>
                        @elseif(!$user->subscriptions()->where('plan_id', $plan->id)->where('price_id', $price->id)->where('status', 'active')->first())
                            <form action="{{ route('frontend.plans.subscribe', ['plan_id' => $plan->id, 'price_id' => $price->id ]) }}" method="POST">
                                @csrf
                                <button type="submit">@lang('wncms::word.subscribe')</button>
                            </form>
                        @else
                            <button disabled>@lang('wncms::word.subscribed')</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        <a href="{{ route('frontend.plans.index') }}">@lang('wncms::word.back_to_plans')</a>
    </p>
</div>
@endsection
