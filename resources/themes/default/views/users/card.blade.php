@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")

        <section class="lg:col-span-9 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.card_recharge')</h1>

                <form method="POST" action="{{ route('frontend.users.card.use') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="code" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.card_code')</label>
                        <input type="text" name="code" id="code" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_card_code') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.balance')</label>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $user->balance }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">@lang('wncms::word.recharge')</button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">{{ __("$themeId::word.card_list") }}</h2>
                <div class="table-container mt-4">
                    <table>
                        <tr>
                            <th>@lang('wncms::word.card_code')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.type')</th>
                            <th>@lang('wncms::word.value')</th>
                            <th>@lang('wncms::word.product')</th>
                            <th>@lang('wncms::word.plan')</th>
                            <th>@lang('wncms::word.user')</th>
                            <th>@lang('wncms::word.redeemed_at')</th>
                            <th>@lang('wncms::word.expired_at')</th>
                        </tr>
                        @foreach(\Secretwebmaster\WncmsEcommerce\Models\Card::limit(10)->get() as $card)
                        <tr>
                            <td>{{ $card->code }}</td>
                            <td>{{ $card->status }}</td>
                            <td>{{ $card->type }}</td>
                            <td>{{ $card->value }}</td>
                            <td>{{ $card->product?->name ?? '-' }}</td>
                            <td>{{ $card->plan?->name ?? '-' }}</td>
                            <td>{{ $card->user?->username }}</td>
                            <td>{{ $card->redeemed_at }}</td>
                            <td>{{ $card->expired_at ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
