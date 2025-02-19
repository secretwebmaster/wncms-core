@extends('wncms::layouts.auth')
@section('auth_content')

    <form class="form w-100" id="form_login" data-action="{{ route('login.ajax') }}">
        @csrf

        {{-- Title --}}
        <div class="text-center mb-11">
            <h1 class="text-light fw-bolder mb-3">@lang('wncms::word.login')</h1>
        </div>

        {{-- Email --}}
        <div class="fv-row mb-8 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control" value="{{ old('Email') }}" required autofocus>
        </div>

        {{-- Password --}}
        <div class="fv-row mb-3 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="password" placeholder="@lang('wncms::word.password')" name="password" autocomplete="off" class="form-control">
        </div>

        {{-- 忘記密碼 --}}
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            @include('wncms::auth.btn-force-https')
            
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-light fw-bold">@lang('wncms::word.forgot_password') ?</a>
            @endif
        </div>

        {{-- 提交 --}}
        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-dark w-100 mt-5" wncms-btn-loading data-form="form_login" data-success-text="@lang('wncms::word.login_successed_redirecting')">
                <span class="indicator-label fw-bold">@lang('wncms::word.login')</span>
                <span class="indicator-progress" style="display:none;">@lang('wncms::word.please_wait')<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>
        
        {{-- 註冊 --}}
        @if(!gss('disable_registration'))
            <div class="text-gray-500 text-center fw-semibold fs-6">
                @lang('wncms::word.no_account_yet') ? <a href="{{ route('register') }}" class="link-light fw-bold">@lang('wncms::word.go_to_register')</a>
            </div>
        @endif

        @if(gss('allow_google_login'))
            {{-- Separator --}}
            <div class="separator separator-content my-14">
                <span class="w-125px text-gray-500 fw-semibold fs-7">@lang('wncms::word.or')</span>
            </div>
            
            {{-- Social Login --}}
            <div class="row g-3 mb-9">
                {{-- google --}}
                <div class="col-12">
                    <a href="{{ route('login.google') }}" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/google-icon.svg') }}" class="h-15px me-3">@lang('wncms::word.register_with_google')
                    </a>
                </div>

                {{-- apple --}}
                {{-- <div class="col-md-6">
                    <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/apple-black.svg') }}" class="theme-light-show h-15px me-3">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/apple-black-dark.svg') }}" class="theme-dark-show h-15px me-3">@lang('wncms::word.register_with_apple')
                    </a>
                </div> --}}
            </div>
        @endif

    </form>


@endsection