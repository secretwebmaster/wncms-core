
{{-- core_functions --}}
@role('superadmin|admin|manager|member')

    {{-- heading --}}
    <div class="menu-item">
        <div class="menu-content pt-5 pb-2">
            <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('word.user_function')</span>
        </div>
    </div>

    {{-- Dashboard --}}
    <div class="menu-item">
        <a class="menu-link py-2 {{ wncms_route_is('dashboard', 'active') }}" href="{{ route('dashboard') }}">
            <span class="menu-icon">
                <i class="fa-lg fa-solid fa-computer {{ wncms_route_is('dashboard', 'fa-beat') }}"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.dashboard')</span>
        </a>
    </div>

    {{-- user info --}}
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ wncms_route_is('users.account.*', 'show') }}">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg fa-solid fa-user {{ wncms_route_is('users.account.*', 'fa-beat') }}"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.my_account')</span>
            <span class="menu-arrow"></span>
        </span>

        <div class="menu-sub menu-sub-accordion">
            <div class="menu-item">
                <a class="menu-link {{ wncms_route_is('users.account.profile.show', 'active') }}" href="{{ route('users.account.profile.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.user_profile')</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ wncms_route_is('users.account.security.show', 'active') }}" href="{{ route('users.account.security.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.user_security')</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ wncms_route_is('users.account.api.show', 'active') }}" href="{{ route('users.account.api.show') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.user_api')</span>
                </a>
            </div>
        </div>
    </div>

@endrole