@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold text-emerald-900">{{ __('wncms::word.reset_email_sent_title') }}</h1>
        <p class="mt-2 text-sm text-emerald-800">{{ __('wncms::word.reset_email_sent_message', ['email' => $email]) }}</p>

        <div class="mt-6">
            <a href="{{ route('frontend.users.login') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">{{ __('wncms::word.back_to_login') }}</a>
        </div>
    </section>
</main>
@endsection
