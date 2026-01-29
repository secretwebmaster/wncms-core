@extends("$themeId::layouts.app")

@section('content')
    <div class="password-reset-email-sent">
        <div class="alert alert-success">
            <h1>{{ __('wncms::word.reset_email_sent_title') }}</h1>
            <p>{{ __('wncms::word.reset_email_sent_message', ['email' => $email]) }}</p>
        </div>

        <div class="back-link">
            <a href="{{ route('frontend.users.login') }}" class="btn btn-primary">
                {{ __('wncms::word.back_to_login') }}
            </a>
        </div>
    </div>
@endsection
