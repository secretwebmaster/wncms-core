<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
    <div class="mx-auto w-full max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-start justify-between gap-4">
            <a href="{{ route('frontend.pages.home') }}" class="flex items-center gap-3">
                <img src="{{ $website->site_logo ?: asset('wncms/images/logos/logo_black.png') }}" alt="LOGO" class="h-10 w-auto rounded-lg sm:h-11">
                <div class="hidden sm:block">
                    <p class="text-sm font-semibold text-slate-900">{{ $website->site_name ?: __("$themeId::word.home_site_name_fallback") }}</p>
                    <p class="text-xs text-slate-500">{{ $website->site_slogan ?: __("$themeId::word.header_slogan_fallback") }}</p>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <div class="relative" id="language-dropdown">
                    @php
                        $localeCode = app()->getLocale();
                        $localeList = $wncms->getLocaleList();
                        $currentLocaleName = $localeList[$localeCode]['native'] ?? strtoupper($localeCode);
                    @endphp
                    <button type="button" id="language-dropdown-trigger" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700" aria-haspopup="true" aria-expanded="false">
                        <span>{{ $currentLocaleName }}</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.118l3.71-3.889a.75.75 0 111.08 1.04l-4.25 4.455a.75.75 0 01-1.08 0l-4.25-4.455a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </button>
                    <ul id="language-dropdown-menu" class="absolute right-0 mt-2 hidden min-w-36 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                        @foreach($wncms->getLocaleList() as $key => $locale)
                            <li><a href="{{ wncms()->locale()->getLocalizedURL($key, null, [], true) }}" class="block px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-sky-700">{{ $locale['native'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <button type="button" id="mobile-menu-trigger" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 md:hidden" aria-expanded="false" aria-controls="header-navigation">
                    <span class="sr-only">{{ __("$themeId::word.open_menu") }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>
            </div>
        </div>

        <div id="header-navigation" class="mt-3 hidden rounded-2xl border border-slate-200/80 bg-white/95 p-2 md:mt-4 md:block">
            <div class="flex flex-col gap-1 md:flex-row md:flex-wrap md:items-center md:gap-2">
                <a href="{{ route('frontend.pages.home') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.homepage')</a>
                <a href="{{ route('frontend.pages.blog') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.blog')</a>
                @auth
                    <a href="{{ route('frontend.users.dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.dashboard')</a>
                    <a href="{{ route('frontend.users.profile') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.my_account')</a>
                    @if(wncms()->isPackageActive('wncms-ecommerce'))
                        <a href="{{ route('frontend.plans.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms-ecommerce::word.plans')</a>
                        <a href="{{ route('frontend.products.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms-ecommerce::word.products')</a>
                        <a href="{{ route('frontend.users.card') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms-ecommerce::word.card_recharge')</a>
                        <a href="{{ route('frontend.users.subscriptions.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms-ecommerce::word.my_subscriptions')</a>
                        <a href="{{ route('frontend.orders.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms-ecommerce::word.my_orders')</a>
                    @endif
                    <a href="{{ route('frontend.users.logout') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-700">@lang('wncms::word.logout')</a>
                @else
                    <a href="{{ route('frontend.users.login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.login')</a>
                    <a href="{{ route('frontend.users.password.forgot') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.forgot_password')</a>
                    <a href="{{ route('frontend.users.register') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-700">@lang('wncms::word.register')</a>
                @endauth
            </div>
        </div>
    </div>
</header>

@push('foot_js')
    <script>
        (function () {
            const mobileTrigger = document.getElementById('mobile-menu-trigger');
            const mobileMenu = document.getElementById('header-navigation');
            const languageTrigger = document.getElementById('language-dropdown-trigger');
            const languageMenu = document.getElementById('language-dropdown-menu');
            const languageWrapper = document.getElementById('language-dropdown');

            if (mobileTrigger && mobileMenu) {
                mobileTrigger.addEventListener('click', function () {
                    const isExpanded = mobileTrigger.getAttribute('aria-expanded') === 'true';
                    mobileTrigger.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                    mobileMenu.classList.toggle('hidden');
                });
            }

            if (languageTrigger && languageMenu && languageWrapper) {
                languageTrigger.addEventListener('click', function () {
                    const isExpanded = languageTrigger.getAttribute('aria-expanded') === 'true';
                    languageTrigger.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                    languageMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', function (event) {
                    if (!languageWrapper.contains(event.target)) {
                        languageTrigger.setAttribute('aria-expanded', 'false');
                        languageMenu.classList.add('hidden');
                    }
                });
            }
        })();
    </script>
@endpush
