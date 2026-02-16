{{-- Package Section --}}

@can('package_index')

<div class="menu-item">
    <div class="menu-content pt-5 pb-2">
        <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('wncms::word.package')</span>
    </div>
</div>

{{-- Core package manager link --}}
<div class="menu-item">
    <a class="menu-link py-2 @if(request()->routeIs('packages.index*')) active @endif" href="{{ route('packages.index') }}">
        <span class="menu-icon">
            <i class="fa-lg @if(request()->routeIs('packages.index*')) fa-beat @endif fa-solid fa-gear"></i>
        </span>
        <span class="menu-title fw-bold">@lang('wncms::word.packages')</span>
    </a>
</div>

{{-- Dynamic package menus --}}
@foreach(wncms()->getPackageMenus() as $menu)
    <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if($menu['is_active']) show @endif">
        {{-- Parent menu --}}
        <span class="menu-link py-2">
            <span class="menu-icon">
                <i class="fa-lg {{ $menu['icon'] }} @if($menu['is_active']) fa-beat @endif"></i>
            </span>
            <span class="menu-title fw-bold">{{ $menu['title'] }}</span>
            @if(!empty($menu['items']))
                <span class="menu-arrow"></span>
            @endif
        </span>

        {{-- One-level submenu --}}
        @if(!empty($menu['items']))
            <div class="menu-sub menu-sub-accordion">
                @foreach($menu['items'] as $item)
                    @if(!empty($item['route']) && wncms()->hasRoute($item['route']))
                        <div class="menu-item">
                            <a class="menu-link @if(request()->routeIs(($item['route'] ?? '') . '*')) active @endif" href="{{ route($item['route']) }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title fw-bold">{{ $item['name'] }}</span>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endforeach

@endcan
