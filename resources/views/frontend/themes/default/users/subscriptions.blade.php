@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div>
    <h1>@lang('wncms::word.subscriptions')</h1>

    <table>
        <thead>
            <tr>
                <th>@lang('wncms::word.plan_name')</th>
                <th>@lang('wncms::word.price')</th>
                <th>@lang('wncms::word.status')</th>
                <th>@lang('wncms::word.subscribed_at')</th>
                <th>@lang('wncms::word.expired_at')</th>
                <th>@lang('wncms::word.action')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $subscription)
                <tr>
                    <td>{{ $subscription->plan?->name }}</td>
                    <td>{{ number_format($subscription->price->amount, 2) }}</td>
                    <td>@lang('wncms::word.' . $subscription->status)</td>
                    <td>{{ $subscription->subscribed_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $subscription->expired_at ? $subscription->expired_at->format('Y-m-d H:i:s') : __('wncms::word.lifetime') }}</td>
                    <td>
                        @if($subscription->status != 'cancelled')
                        <form action="{{ route('frontend.plans.unsubscribe') }}" method="POST">
                            @csrf
                            <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                            <button type="submit">@lang('wncms::word.unsubscribe')</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">@lang('wncms::word.no_subscriptions')</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
