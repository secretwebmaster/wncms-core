@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")

        <section class="lg:col-span-9">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.subscriptions')</h1>

                <div class="table-container mt-4">
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
                                            <button type="submit" class="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">@lang('wncms::word.unsubscribe')</button>
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
            </div>
        </section>
    </div>
</main>
@endsection
