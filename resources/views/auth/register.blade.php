@extends('layouts.auth')
@section('auth_content')

    <form id="form_register" data-action="{{ route('register', ['is_ajax' => true]) }}">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-light fw-bolder mb-3">@lang('word.register')</h1>
            {{-- <div class="text-gray-500 fw-semibold fs-6">@lang('word.start_rewriting_post')</div> --}}
        </div>

        {{-- Email --}}
        <div class="fv-row mb-8 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control" value="{{ old('Email') }}" required autofocus>
        </div>

        {{-- Password --}}
        <div class="fv-row mb-3 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="password" placeholder="@lang('word.password')" name="password" autocomplete="off" class="form-control">
        </div>

        {{-- Password --}}
        <div class="fv-row mb-3 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="password" placeholder="@lang('word.password_confirmation')" name="password_confirmation" autocomplete="off"  class="form-control">
        </div>

        {{-- 忘記密碼 --}}
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <div></div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-light fw-bold">@lang('word.forgot_password') ?</a>
            @endif
        </div>

        {{-- 提交 --}}
        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-dark w-100 mt-5" wncms-btn-loading data-form="form_register" data-success-text="@lang('word.registerd')">
                <span class="indicator-label fw-bold">@lang('word.register')</span>
                <span class="indicator-progress" style="display:none;">@lang('word.please_wait')<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>

        {{-- Login --}}
        <div class="text-gray-500 text-center fw-semibold fs-6">
            @lang('word.already_have_account') ? <a href="{{ route('login') }}" class="link-light fw-bold">@lang('word.go_to_login')</a>
        </div>

        @if(gss('allow_google_login'))
            {{-- Separator --}}
            <div class="separator separator-content my-14">
                <span class="w-125px text-gray-500 fw-semibold fs-7">@lang('word.or')</span>
            </div>
            

            {{-- Social Login --}}
            
            <div class="row g-3 mb-9">
                <div class="col-12">
                    <a href="{{ route('login.google') }}" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/google-icon.svg') }}" class="h-15px me-3">@lang('word.register_with_google')
                    </a>
                </div>

                {{-- <div class="col-md-6">
                    <a href="#" class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/apple-black.svg') }}" class="theme-light-show h-15px me-3">
                        <img alt="Logo" src="{{ asset('wncms/media/svg/brand-logos/apple-black-dark.svg') }}" class="theme-dark-show h-15px me-3">@lang('word.register_with_apple')
                    </a>
                </div> --}}
            </div>
        @endif
        
    </form>


@endsection
