@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
    @if($status === Password::PASSWORD_RESET)
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold text-emerald-900">{{ __('wncms::word.reset_completed_title') }}</h1>
            <p class="mt-2 text-sm text-emerald-800">{{ __('wncms::word.reset_completed_message') }}</p>
            <a href="{{ route('frontend.users.login') }}" class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">{{ __('wncms::word.login_button') }}</a>
        </section>
    @else
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold text-rose-900">{{ __('wncms::word.reset_failed_title') }}</h1>
            @if($status === Password::INVALID_TOKEN)
                <p class="mt-2 text-sm text-rose-800">{{ __('wncms::word.invalid_token') }}</p>
            @elseif($status === Password::INVALID_USER)
                <p class="mt-2 text-sm text-rose-800">{{ __('wncms::word.invalid_user') }}</p>
            @elseif($status === Password::INVALID_PASSWORD)
                <p class="mt-2 text-sm text-rose-800">{{ __('wncms::word.invalid_password') }}</p>
            @else
                <p class="mt-2 text-sm text-rose-800">{{ __('wncms::word.reset_failed_message') }}</p>
            @endif
            <a href="{{ route('frontend.users.login') }}" class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">{{ __('wncms::word.login_button') }}</a>
        </section>
    @endif
</main>
@endsection
