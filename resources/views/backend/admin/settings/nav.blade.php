<div class="col-12 col-sm-4 col-lg-2">
    <div class="card bg-dark text-gray-100">
        <div class="card-body px-3 py-3 py-md-8">
            <ul class="nav nav-tabs nav-pills border-0 flex-row flex-md-column me-0 me-md-5 mb-3 mb-md-0 fs-6 w-100">
                @foreach($availableSettings as $nav_tab_index => $nav_tab)
                @php $isActive = $nav_tab['tab_name'] === $activeTab; @endphp
                @if(!empty($nav_tab['tab_name']) && !empty($nav_tab['tab_content']))
                <li class="nav-item col-4 col-md-12 fw-bold me-0">
                    <a class="nav-link {{ $isActive ? 'active' : '' }}"
                        data-bs-toggle="tab"
                        data-bs-target="#tab_{{ $nav_tab['tab_name'] }}"
                        href="javascript:void(0);">
                        @lang("wncms::word." . $nav_tab['tab_name'] . "_setting")
                    </a>
                </li>
                @endif
                @endforeach

                {{-- Dynamic API Access Tab --}}
                <li class="nav-item col-4 col-md-12 fw-bold me-0">
                    <a class="nav-link {{ $activeTab === 'api_access' ? 'active' : '' }}"
                        data-bs-toggle="tab"
                        data-bs-target="#tab_api_access"
                        href="javascript:void(0);">
                        @lang('wncms::word.api_setting')
                    </a>
                </li>

                {{-- developer_mode tab --}}
                @if(gss('developer_mode'))
                    <li class="nav-item col-4 col-md-12 fw-bold me-0">
                        <a class="nav-link {{ $activeTab === 'developer' ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#tab_{{ 'developer' }}"
                            href="javascript:void(0);">
                            @lang("wncms::word." . 'developer' . "_setting")
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        @if(!gss('disable_core_update'))
        <div class="card-footer p-3">
            <a href="{{ route('updates') }}" class="btn btn-sm btn-secondary w-100">@lang('wncms::word.check_updates')</a>
        </div>
        @endif
    </div>
</div>