
{{-- core_functions --}}
@role('superadmin|admin|manager|member')

    {{-- heading --}}
    <div class="menu-item">
        <div class="menu-content pt-5 pb-2">
            <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('wncms::word.user_function')</span>
        </div>
    </div>

    {{-- Dashboard --}}
    <div class="menu-item">
        <a class="menu-link py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <span class="menu-icon">
                <i class="fa-lg fa-solid fa-computer {{ request()->routeIs('dashboard') ? 'fa-beat' : '' }}"></i>
            </span>
            <span class="menu-title fw-bold">@lang('wncms::word.dashboard')</span>
        </a>
    </div>

    {{-- user info --}}
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('users.account.*') ? 'show' : '' }}">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg fa-solid fa-user {{ request()->routeIs('users.account.*') ? 'fa-beat' : '' }}"></i>
            </span>
            <span class="menu-title fw-bold">@lang('wncms::word.my_account')</span>
            <span class="menu-arrow"></span>
        </span>

        <div class="menu-sub menu-sub-accordion">
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('users.account.profile.show') ? 'active' : '' }}" href="{{ route('users.account.profile.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('wncms::word.user_profile')</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('users.account.security.show') ? 'active' : '' }}" href="{{ route('users.account.security.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('wncms::word.user_security')</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('users.account.api.show') ? 'active' : '' }}" href="{{ route('users.account.api.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('wncms::word.user_api')</span>
                </a>
            </div>
        </div>
    </div>

@endrole
