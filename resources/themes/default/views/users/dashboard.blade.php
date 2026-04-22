@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")

        <section class="lg:col-span-9 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.dashboard')</h1>
                <p class="mt-2 text-sm text-slate-600">@lang('wncms::word.welcome_back'), {{ auth()->user()->nickname ?: auth()->user()->username }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">@lang('wncms::word.username')</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ auth()->user()->username }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">@lang('wncms::word.email')</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ auth()->user()->email ?: __('wncms::word.n_a') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">@lang('wncms::word.last_login_at')</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ auth()->user()->last_login_at ?: __('wncms::word.n_a') }}</p>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
