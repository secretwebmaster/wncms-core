@extends("$themeId::layouts.app")

@section('content')
<div class="login-container">
    <h2>@lang('wncms::word.login')</h2>
    <form method="POST" action="{{ route('frontend.users.login.submit') }}">
        @csrf
        <table class="login-table mb-3">
            <tr>
                <th>@lang('wncms::word.username_or_email')</th>
                <td>
                    <input type="text" name="username" class="form-control" id="username" placeholder="{{ __('wncms::word.enter_username') }}" autofocus required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.password')</th>
                <td>
                    <input type="password" name="password" class="form-control" id="password" placeholder="{{ __('wncms::word.enter_password') }}" required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.remember_me')</th>
                <td>
                    <div class="d-flex align-items-center">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">@lang('wncms::word.remember_me')</label>
                    </div>
                </td>
            </tr>
        </table>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">@lang('wncms::word.login')</button>
            <small class="d-block mt-3 text-center">@lang('wncms::word.no_account')? <a href="{{ route('register') }}">@lang('wncms::word.register_here')</a>.</small>
        </div>
    </form>
</div>
@endsection
