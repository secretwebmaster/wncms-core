@extends('layouts.auth')
@section('auth_content')

    <form class="form w-100 " novalidate="novalidate" id="kt_sign_in_form" action="{{ route('login') }}">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-dark fw-bolder mb-3">@lang('word.login')</h1>
            {{-- <div class="text-gray-500 fw-semibold fs-6">@lang('word.start_rewriting_post')</div> --}}
        </div>

        {{-- Email --}}
        <div class="fv-row mb-8 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" value="{{ old('Email') }}" required autofocus>
        </div>

        {{-- 提交 --}}
        <div class="d-grid mb-10">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-dark mt-5">
                @include('partials.general._button-indicator', ['label' => __('word.send_reset_password_request')])
            </button>
        </div>
        
        {{-- 返回 --}}
        <div class="text-gray-500 text-center fw-semibold fs-6">
           <a href="{{ route('login') }}" class="link-primary">@lang('word.go_to_login')</a>
        </div>

    </form>


@endsection
