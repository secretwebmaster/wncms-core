@extends('frontend.theme.default.layouts.app')

@section('content')
    <div class="password-reset">
        <h1>{{ __('wncms::word.reset_password') }}</h1>
        <p>{{ __('wncms::word.enter_new_password') }}</p>

        {{-- Display Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('frontend.users.password.reset.submit') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">{{ __('wncms::word.new_password') }}</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">{{ __('wncms::word.confirm_new_password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ __('wncms::word.reset_password_button') }}</button>
            </div>
        </form>
    </div>
@endsection
