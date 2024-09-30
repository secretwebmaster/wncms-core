
@php
event('backend.menu.render', $PluginMenuItems = new \Illuminate\Support\Collection);
@endphp

{{-- Plugin --}}
<div class="menu-item">
    <div class="menu-content pt-5 pb-2">
        <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('word.plugin')</span>
    </div>
</div>

@foreach ($PluginMenuItems as $menuItem)
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if(request()->routeIs($menuItem['route_is'])) show @endif">
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg @if(request()->routeIs($menuItem['route_is'])) fa-beat @endif fa-solid fa-lock"></i>
            </span>
            <span class="menu-title fw-bold">@lang('word.model_management', ['model_name' => __('word.role')])</span>
            <span class="menu-arrow"></span>
        </span>

        {{-- Sub menuItem --}}
        <div class="menu-sub menu-sub-accordion">
            @foreach ($menuItem['submenu'] as $submenuItem)
                <div class="menu-item">
                    <a class="menu-link @if(request()->routeIs($submenuItem['route'])) active @endif" href="{{ route($submenuItem['route']) }}">
                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                        <span class="menu-title fw-bold">{{ $submenuItem['name'] }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endforeach