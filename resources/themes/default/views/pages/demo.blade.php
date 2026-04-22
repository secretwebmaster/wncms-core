@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __("$themeId::word.demo_title") }}</h1>
            <a class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" href="{{ route('frontend.pages.home') }}">@lang('wncms::word.homepage')</a>
        </div>

        <p class="mb-8 text-sm text-slate-600">{{ __("$themeId::word.demo_description") }}</p>

        <section class="space-y-8" id="demo-content">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.page-list")
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.post-list")
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.tag-list")
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.website-options")
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.theme-options")
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include("$themeId::parts.link-list")
            </div>
        </section>
    </main>
@endsection
