@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900 px-6 py-12 text-white shadow-xl sm:px-10">
            <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-sky-400/20 blur-2xl"></div>
            <div class="absolute -bottom-16 -left-16 h-44 w-44 rounded-full bg-cyan-300/10 blur-2xl"></div>
            <div class="relative grid gap-8 lg:grid-cols-5 lg:items-center">
                <div class="lg:col-span-3">
                    <p class="mb-3 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-widest text-sky-100">{{ __("$themeId::word.home_badge") }}</p>
                    <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">{{ $website->site_name ?: __("$themeId::word.home_site_name_fallback") }}</h1>
                    <p class="mt-4 max-w-2xl text-sm text-slate-100 sm:text-base">{{ $website->site_slogan ?: __("$themeId::word.home_hero_description") }}</p>
                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        <a href="{{ route('frontend.pages.blog') }}" class="inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">@lang('wncms::word.blog')</a>
                        @guest
                            <a href="{{ route('frontend.users.register') }}" class="inline-flex items-center rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">@lang('wncms::word.register')</a>
                        @else
                            <a href="{{ route('frontend.users.dashboard') }}" class="inline-flex items-center rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">@lang('wncms::word.dashboard')</a>
                        @endguest
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs uppercase tracking-wide text-slate-200">{{ __("$themeId::word.home_card_content_title") }}</p>
                            <p class="mt-2 text-lg font-semibold">{{ __("$themeId::word.home_card_content_desc") }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs uppercase tracking-wide text-slate-200">{{ __("$themeId::word.home_card_user_system_title") }}</p>
                            <p class="mt-2 text-lg font-semibold">{{ __("$themeId::word.home_card_user_system_desc") }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur sm:col-span-2 lg:col-span-1">
                            <p class="text-xs uppercase tracking-wide text-slate-200">{{ __("$themeId::word.home_card_extensible_title") }}</p>
                            <p class="mt-2 text-lg font-semibold">{{ __("$themeId::word.home_card_extensible_desc") }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="mb-4 flex items-end justify-between gap-3">
                <h2 class="text-xl font-semibold text-slate-900">@lang('wncms::word.post_list')</h2>
                <a href="{{ route('frontend.pages.blog') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800">{{ __("$themeId::word.view_all") }}</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @php($thumbnailPlaceholder = asset('wncms/images/placeholders/loading_ghost.webp'))
                @foreach(wncms()->post()->getList(['page_size' => 6, 'count' => 6, 'cache' => true]) as $post)
                    @php($thumbnailSrc = $post->thumbnail ?: $thumbnailPlaceholder)
                    @php($postUrl = route('frontend.posts.show', ['slug' => $post->slug]))
                    <article class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ $postUrl }}" class="block overflow-hidden rounded-xl bg-slate-100">
                            <img src="{{ $thumbnailPlaceholder }}" data-src="{{ $thumbnailSrc }}" alt="{{ $post->title }}" class="lazyload h-40 w-full object-cover transition duration-300 group-hover:scale-105">
                        </a>
                        <h3 class="mt-4 text-base font-semibold text-slate-900"><a href="{{ $postUrl }}" class="hover:text-sky-700">{{ $post->title }}</a></h3>
                        <p class="mt-3 text-xs text-slate-500">{{ __("$themeId::word.post_id_label") }}: {{ $post->id }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-10" id="quick-links">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">{{ __("$themeId::word.quick_access") }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('frontend.pages.home') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.homepage')</a>
                    <a href="{{ route('frontend.pages.blog') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.blog')</a>
                    @auth
                        <a href="{{ route('frontend.users.dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.dashboard')</a>
                        <a href="{{ route('frontend.users.profile') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.my_account')</a>
                    @else
                        <a href="{{ route('frontend.users.login') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.login')</a>
                        <a href="{{ route('frontend.users.register') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50">@lang('wncms::word.register')</a>
                    @endauth
                </div>
            </div>
        </section>
    </main>
@endsection
