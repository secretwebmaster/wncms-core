@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold text-slate-900">{{ __('wncms::word.reset_password') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('wncms::word.enter_new_password') }}</p>

        @if($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('frontend.users.password.reset.submit') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">{{ __('wncms::word.new_password') }}</label>
                <input type="password" name="password" id="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" required>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">{{ __('wncms::word.confirm_new_password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" required>
            </div>

            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">{{ __('wncms::word.reset_password_button') }}</button>
        </form>
    </section>
</main>
@endsection
