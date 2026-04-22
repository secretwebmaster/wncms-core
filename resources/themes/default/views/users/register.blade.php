@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.register')</h1>
        <p class="mt-2 text-sm text-slate-600">@lang('wncms::word.no_account_yet')</p>

        <form method="POST" action="{{ route('frontend.users.register.submit') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.username')</label>
                <input type="text" id="username" name="username" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_username') }}" required>
            </div>

            <div>
                <label for="nickname" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.nickname') (@lang('wncms::word.optional'))</label>
                <input type="text" id="nickname" name="nickname" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_nickname') }}">
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.email')</label>
                <input type="email" id="email" name="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_email') }}" required>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.password')</label>
                <input type="password" id="password" name="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_password') }}" required>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">@lang('wncms::word.confirm_password')</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" placeholder="{{ __('wncms::word.enter_confirm_password') }}" required>
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">@lang('wncms::word.register')</button>
            </div>
        </form>
    </section>
</main>
@endsection
