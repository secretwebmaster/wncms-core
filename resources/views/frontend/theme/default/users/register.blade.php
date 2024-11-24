@extends('frontend.theme.default.layouts.app')

@section('content')
<div class="register-container">
    <h2>@lang('wncms::word.register')</h2>
    <form method="POST" action="{{ route('frontend.users.register.submit') }}">
        @csrf
        <table class="register-table mb-3">
            <tr>
                <th>@lang('wncms::word.username')</th>
                <td>
                    <input type="text" id="username" name="username" class="form-control" placeholder="{{ __('wncms::word.enter_username') }}" required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.nickname') (@lang('wncms::word.optional'))</th>
                <td>
                    <input type="text" id="nickname" name="nickname" class="form-control" placeholder="{{ __('wncms::word.enter_nickname') }}">
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.email')</th>
                <td>
                    <input type="email" id="email" name="email" class="form-control" placeholder="{{ __('wncms::word.enter_email') }}" required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.password')</th>
                <td>
                    <input type="password" id="password" name="password" class="form-control" placeholder="{{ __('wncms::word.enter_password') }}" required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.confirm_password')</th>
                <td>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="{{ __('wncms::word.enter_confirm_password') }}" required>
                </td>
            </tr>
        </table>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">@lang('wncms::word.register')</button>
        </div>
    </form>
</div>
@endsection
