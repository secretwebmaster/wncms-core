@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div class="password-forgot">
    <h2>@lang('wncms::word.forgot_password')</h2>
    <p>@lang('wncms::word.forgot_password_description')</p>
    <form method="POST" action="{{ route('frontend.users.password.forgot.submit') }}">
        @csrf
        <table class="forgot-password-table mb-3">
            <tr>
                <th>@lang('wncms::word.email')</th>
                <td>
                    <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('wncms::word.enter_email') }}" required>
                </td>
            </tr>
        </table>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">@lang('wncms::word.send_reset_password_link')</button>
        </div>
    </form>
</div>
@endsection
