@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.login')</h1>
        <p class="mt-2 text-sm text-slate-600">@lang('wncms::word.welcome_back')</p>

        <form method="POST" action="{{ route('frontend.users.login.submit') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.username_or_email')</label>
                <input type="text" name="username" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" id="username" placeholder="{{ __('wncms::word.enter_username') }}" autofocus required>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.password')</label>
                <input type="password" name="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" id="password" placeholder="{{ __('wncms::word.enter_password') }}" required>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span>@lang('wncms::word.remember_me')</span>
            </label>

            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">@lang('wncms::word.login')</button>

            <p class="text-center text-sm text-slate-600">
                @lang('wncms::word.no_account')?
                <a href="{{ route('frontend.users.register') }}" class="font-medium text-sky-700 hover:text-sky-800">@lang('wncms::word.register_here')</a>
            </p>
        </form>
    </section>
</main>
@endsection
