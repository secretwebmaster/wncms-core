@extends('frontend.theme.default.layouts.app')

@section('content')
<div class="register-container">
    <h2>@lang('wncms::word.register')</h2>
    <form method="POST" action="{{ route('frontend.users.register.submit') }}">
        @csrf
        <div class="form-group">
            <label for="username">@lang('wncms::word.username')</label>
            <input type="text" id="username" name="username" placeholder="@lang('wncms::word.enter_username')" required>
        </div>
        <div class="form-group">
            <label for="nickname">@lang('wncms::word.nickname') (@lang('wncms::word.optional'))</label>
            <input type="text" id="nickname" name="nickname" placeholder="@lang('wncms::word.enter_nickname')">
        </div>
        <div class="form-group">
            <label for="email">@lang('wncms::word.email')</label>
            <input type="email" id="email" name="email" placeholder="@lang('wncms::word.enter_email')" required>
        </div>
        <div class="form-group">
            <label for="password">@lang('wncms::word.password')</label>
            <input type="password" id="password" name="password" placeholder="@lang('wncms::word.enter_password')" required>
        </div>
        <div class="form-group">
            <label for="password_confirmation">@lang('wncms::word.confirm_password')</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="@lang('wncms::word.confirm_password')" required>
        </div>
        <button type="submit" class="btn-submit">@lang('wncms::word.register')</button>
    </form>
</div>

<style>
    .register-container {
        max-width: 400px;
        margin: 20px auto;
        font-family: Arial, sans-serif;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.5em;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input {
        width: 100%;
        padding: 8px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: none;
        background-color: #007bff;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }
</style>
@endsection
