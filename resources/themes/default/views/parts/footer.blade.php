<footer class="mt-12 border-t border-slate-200 bg-white">
    <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ $website->site_name ?: 'WNCMS' }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ $website->site_slogan ?: __("$themeId::word.footer_slogan_fallback") }}</p>
        </div>

        <div>
            <p class="text-sm font-semibold text-slate-900">{{ __("$themeId::word.navigation") }}</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                <li><a href="{{ route('frontend.pages.home') }}" class="hover:text-sky-700">@lang('wncms::word.homepage')</a></li>
                <li><a href="{{ route('frontend.pages.blog') }}" class="hover:text-sky-700">@lang('wncms::word.blog')</a></li>
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold text-slate-900">{{ __("$themeId::word.account") }}</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                @auth
                    <li><a href="{{ route('frontend.users.dashboard') }}" class="hover:text-sky-700">@lang('wncms::word.dashboard')</a></li>
                    <li><a href="{{ route('frontend.users.profile') }}" class="hover:text-sky-700">@lang('wncms::word.my_account')</a></li>
                @else
                    <li><a href="{{ route('frontend.users.login') }}" class="hover:text-sky-700">@lang('wncms::word.login')</a></li>
                    <li><a href="{{ route('frontend.users.register') }}" class="hover:text-sky-700">@lang('wncms::word.register')</a></li>
                @endauth
            </ul>
        </div>
    </div>

    <section class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h2 class="text-base font-semibold text-slate-900">{{ __("$themeId::word.friend_links") }}</h2>
            <ul class="mt-4 grid grid-cols-4 gap-2 md:grid-cols-10">
                @foreach(wncms()->link()->getList(['count' => 40, 'multi_website' => false]) as $link)
                    <li class="w-full" wncms-click-record data-clickable-id="{{ $link->id }}" data-clickable-type="Wncms\Models\Link" data-name="{{ $link->name }}" data-value="{{ $link->url }}">
                        <a href="{{ $link->url }}" target="_blank" class="block w-full truncate rounded-md border border-slate-200 bg-white px-2 py-2 text-center text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700" title="{{ $link->name }}">{{ $link->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-4 text-xs text-slate-500 sm:px-6 lg:px-8">
            <p>© {{ date('Y') }} {{ $website->site_name ?: 'WNCMS' }}. {{ __("$themeId::word.all_rights_reserved") }}</p>
            <p>{{ __("$themeId::word.powered_by_wncms") }}</p>
        </div>
    </div>
</footer>

@push('foot_js')
    @include('wncms::frontend.common.clicks.record')
@endpush
