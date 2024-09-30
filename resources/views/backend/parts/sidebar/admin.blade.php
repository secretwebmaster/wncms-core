@role('superadmin|admin|manager')

    {{-- Header --}}
    <div class="menu-item">
        <div class="menu-content pt-5 pb-2">
            <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('word.admin_functions')</span>
        </div>
    </div>

    {{-- 系統設定 --}}
    @can('setting_edit')
        <div class="menu-item">
            <a class="menu-link py-2 @if(request()->routeIs('settings.index')) active @endif" href="{{ route('settings.index') }}">
                <span class="menu-icon">
                    <i class="fa-lg @if(request()->routeIs('settings.index')) fa-beat @endif fa-solid fa-gear"></i>
                </span>
                <span class="menu-title fw-bold">@lang('word.setting')</span>
            </a>
        </div>
    @endcan

    {{-- 系統設定 --}}
    @can('theme_index')
        <div class="menu-item">
            <a class="menu-link py-2 @if(request()->routeIs('themes.index')) active @endif" href="{{ route('themes.index') }}">
                <span class="menu-icon">
                    <i class="fa-lg @if(request()->routeIs('themes.index')) fa-beat @endif fa-solid fa-gear"></i>
                </span>
                <span class="menu-title fw-bold">@lang('word.theme_list')</span>
            </a>
        </div>
    @endcan

    {{-- 權限列表 --}}
    @can('role_edit')
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if(request()->routeIs('roles.*') || request()->routeIs('permissions.*')) show @endif">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg @if(request()->routeIs('roles.*') || request()->routeIs('permissions.*')) fa-beat @endif fa-solid fa-lock"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.model_management', ['model_name' => __('word.role')])</span>
            <span class="menu-arrow"></span>
        </span>

        <div class="menu-sub menu-sub-accordion">
            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('roles.index')) active @endif" href="{{ route('roles.index') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.model_list', ['model_name' => __('word.role')])</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('roles.create')) active @endif" href="{{ route('roles.create') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.model_create', ['model_name' => __('word.role')])</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('permissions.index')) active @endif" href="{{ route('permissions.index') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.model_list', ['model_name' => __('word.permission')])</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('permissions.create')) active @endif" href="{{ route('permissions.create') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.model_create', ['model_name' => __('word.permission')])</span>
                </a>
            </div>
        </div>
    </div>
    @endcan

    {{-- Tag --}}
    @can('tag_edit')
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if(request()->routeIs('tags.*')) show @endif">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg @if(request()->routeIs('tags.*')) fa-beat @endif fa-solid fa-tags"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.category_management')</span>
            <span class="menu-arrow"></span>
        </span>

        <div class="menu-sub menu-sub-accordion">
            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('tags.index')) active @endif" href="{{ route('tags.index') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.all_tag_types')</span>
                </a>
            </div>

            @foreach(wncms_get_all_tag_types() as $tag_type)
            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('tags.index.type') && in_array($tag_type, request()->route()->parameters)) active @endif" href="{{ route('tags.index.type' , ['type'=>$tag_type]) }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">{{ wncms_tag_word($tag_type) }}</span>
                </a>
            </div>
            @endforeach

            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('tags.create')) active @endif" href="{{ route('tags.create') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.create_tag')</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link @if(request()->routeIs('tags.keywords.index')) active @endif" href="{{ route('tags.keywords.index') }}">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">@lang('word.bind_keyword')</span>
                </a>
            </div>
        </div>
    </div>
    @endcan

    {{-- Analytics --}}
    {{-- @can('analytics_index')
    <div class="menu-item">
        <a class="menu-link @if(request()->routeIs('analytics.index')) active @endif py-2" href="{{ route('analytics.index') }}">
            <span class="menu-icon">
                <i class="fa-lg @if(request()->routeIs('analytics.index')) fa-beat @endif fa-solid fa-gear"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.analytics')</span>
        </a>
    </div>
    @endcan --}}

    @includeif('backend.parts.sidebar.custom_admin_sidebar')

    {{-- Settings --}}

    {{-- Separator --}}
    {{-- <div class="menu-item">
        <div class="menu-content pt-5 pb-2">
            <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">Modules</span>
        </div>
    </div> --}}

    {{-- Dropdown --}}
    {{-- <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <span class="svg-icon svg-icon-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path opacity="0.3" d="M16.5 9C16.5 13.125 13.125 16.5 9 16.5C4.875 16.5 1.5 13.125 1.5 9C1.5 4.875 4.875 1.5 9 1.5C13.125 1.5 16.5 4.875 16.5 9Z" fill="currentColor"></path>
                        <path d="M9 16.5C10.95 16.5 12.75 15.75 14.025 14.55C13.425 12.675 11.4 11.25 9 11.25C6.6 11.25 4.57499 12.675 3.97499 14.55C5.24999 15.75 7.05 16.5 9 16.5Z" fill="currentColor"></path>
                        <rect x="7" y="6" width="4" height="4" rx="2" fill="currentColor">
                            
                        </rect>
                    </svg>
                </span>
            </span>
            <span class="menu-title fw-bold">@lang('word.links')</span>
            <span class="menu-arrow">
                </span>
        </span>

        <div class="menu-sub menu-sub-accordion">
            <div class="menu-item">
                <a class="menu-link py-2" href="https://adsgroup.top/account/overview">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">word.Overview</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link py-2" href="https://adsgroup.top/account/settings">
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                    <span class="menu-title fw-bold">word.Settings</span>
                </a>
            </div>

            <div class="menu-item">
                <a class="menu-link py-2" href="#" title="Coming soon" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
                    <span class="menu-bullet">
                    <span class="bullet bullet-dot">
                    </span>
                </span>
                    <span class="menu-title fw-bold">word.Security</span>
                </a>
            </div>
        </div>
    </div> --}}

@endrole




