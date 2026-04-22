@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")
        <section class="lg:col-span-9 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __("$themeId::word.user_downloads") }}</h1>
            <p class="mt-3 text-sm text-slate-600">{{ __("$themeId::word.no_data_yet") }}</p>
        </section>
    </div>
</main>
@endsection
