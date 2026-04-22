@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.forgot_password')</h1>
        <p class="mt-2 text-sm text-slate-600">@lang('wncms::word.forgot_password_description')</p>

        <form method="POST" action="{{ route('frontend.users.password.forgot.submit') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.email')</label>
                <input type="email" name="email" id="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_email') }}" required>
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">@lang('wncms::word.send_reset_password_link')</button>
        </form>
    </section>
</main>
@endsection
