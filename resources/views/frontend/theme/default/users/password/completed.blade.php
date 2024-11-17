@extends('frontend.theme.default.layouts.app')

@section('content')
    <div class="password-reset-completed">
        @if ($status === Password::PASSWORD_RESET)
            <div class="alert alert-success">
                <h1>{{ __('wncms::word.reset_completed_title') }}</h1>
                <p>{{ __('wncms::word.reset_completed_message') }}</p>
            </div>
        @else
            <div class="alert alert-danger">
                <h1>{{ __('wncms::word.reset_failed_title') }}</h1>
                @if ($status === Password::INVALID_TOKEN)
                    <p>{{ __('wncms::word.invalid_token') }}</p>
                @elseif ($status === Password::INVALID_USER)
                    <p>{{ __('wncms::word.invalid_user') }}</p>
                @elseif ($status === Password::INVALID_PASSWORD)
                    <p>{{ __('wncms::word.invalid_password') }}</p>
                @else
                    <p>{{ __('wncms::word.reset_failed_message') }}</p>
                @endif
            </div>
        @endif

        <div class="login-link">
            <a href="{{ route('frontend.users.login') }}" class="btn btn-primary">
                {{ __('wncms::word.login_button') }}
            </a>
        </div>
    </div>
@endsection
