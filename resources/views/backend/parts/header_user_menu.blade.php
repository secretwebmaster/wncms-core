<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
	<div class="menu-item px-3">
		<div class="menu-content d-flex align-items-center px-3">
            {{-- Logo --}}
			<div class="symbol symbol-50px me-5">
				<img alt="Logo" src="{{ auth()->user()->avatar }}" />
			</div>

            {{-- User Info --}}
			<div class="d-flex flex-column">
				<div class="fw-bold d-flex align-items-center fs-5">
                    {{-- <span>Max Smith</span>  --}}
                    <span class="badge bg-success">{{ auth()->user()->roles()->first()->name }}</span> 
                    {{-- <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ auth()->user()->roles->first()->name ?? __('word.user') }}</span> --}}
                </div>
				<a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
			</div>
		</div>
	</div>
    

    {{-- 用戶參數 --}}
	@if(!empty($custom_user_fields))
		<div class="separator my-2"></div>
		@foreach($custom_user_fields as $custom_user_field)
			<div class="menu-item px-5">
				<a href="javascript:;" class="menu-link px-5">
					<span class="menu-text">@lang('word.' . $custom_user_field)</span>
					<span class="menu-badge">
						<span class="badge badge-light-danger badge-circle fw-bold fs-7">{{ auth()->user()->{$custom_user_field} }}</span>
					</span>
				</a>
			</div>
		@endforeach
	@endif

    {{-- 下拉菜單 --}}
	{{-- <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start">
		<a href="#" class="menu-link px-5">
			<span class="menu-title">My Subscription</span>
			<span class="menu-arrow"></span>
		</a>
		<div class="menu-sub menu-sub-dropdown w-175px py-4">
			<div class="menu-item px-3">
				<a href="#" class="menu-link px-5">Referrals</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link px-5">Billing</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link px-5">Payments</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex flex-stack px-5">Statements
				<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="View your statements"></i></a>
			</div>
			<div class="separator my-2"></div>
			<div class="menu-item px-3">
				<div class="menu-content px-3">
					<label class="form-check form-switch form-check-custom form-check-solid">
						<input class="form-check-input w-30px h-20px" type="checkbox" value="1" checked="checked" name="notifications" />
						<span class="form-check-label text-muted fs-7">Notifications</span>
					</label>
				</div>
			</div>
		</div>
	</div> --}}


    {{-- 帳號設定 --}}
	<div class="menu-item px-5 my-1">
		<a href="{{ route('users.account.profile.show') }}" class="menu-link px-5">@lang('word.my_account')</a>
	</div>


    {{-- Language Switcher --}}
	{{-- <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start">
		<a href="#" class="menu-link px-5">
			<span class="menu-title position-relative">Language
			<span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">English
			<img class="w-15px h-15px rounded-1 ms-2" src="{{ asset(theme()->getMediaUrlPath() . 'flags/united-states.svg') }}" alt="" /></span></span>
		</a>
		<div class="menu-sub menu-sub-dropdown w-175px py-4">
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex px-5 active">
				<span class="symbol symbol-20px me-4">
					<img class="rounded-1" src="{{ asset(theme()->getMediaUrlPath() . 'flags/united-states.svg') }}" alt="" />
				</span>English</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex px-5">
				<span class="symbol symbol-20px me-4">
					<img class="rounded-1" src="{{ asset(theme()->getMediaUrlPath() . 'flags/spain.svg') }}" alt="" />
				</span>Spanish</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex px-5">
				<span class="symbol symbol-20px me-4">
					<img class="rounded-1" src="{{ asset(theme()->getMediaUrlPath() . 'flags/germany.svg') }}" alt="" />
				</span>German</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex px-5">
				<span class="symbol symbol-20px me-4">
					<img class="rounded-1" src="{{ asset(theme()->getMediaUrlPath() . 'flags/japan.svg') }}" alt="" />
				</span>Japanese</a>
			</div>
			<div class="menu-item px-3">
				<a href="#" class="menu-link d-flex px-5">
				<span class="symbol symbol-20px me-4">
					<img class="rounded-1" src="{{ asset(theme()->getMediaUrlPath() . 'flags/france.svg') }}" alt="" />
				</span>French</a>
			</div>
		</div>
	</div> --}}


    {{-- 登出 --}}
	<div class="menu-item px-5">
        <a href="javascript:;" data-action="{{ route('logout') }}" data-method="post" data-csrf="{{ csrf_token() }}" data-reload="true" class="button-ajax menu-link px-5">@lang('word.logout')</a>
	</div>
</div>
