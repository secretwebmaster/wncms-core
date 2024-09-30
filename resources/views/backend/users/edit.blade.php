@extends('layouts.backend')
@section('content')

@include('backend.parts.message')


<form class="form" method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="card mb-3">
        <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">@lang('word.edit_user')</h3>
            </div>
        </div>

        <div class="card-body border-top p-3 p-md-9">

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label  fw-bold fs-6">@lang('word.role')</label>
                <div class="col-lg-8 fv-row">
                    <select name="role" class="form-select form-select-solid form-select-lg">
                        <option value="">@lang('word.please_select')</option>
                        @foreach($roles as $role)
                            <option  value="{{ $role->name }}" @if($user->hasRole($role)) selected @endif><b>{{ $role->name }}</b></option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.username')</label>
                <div class="col-lg-8 fv-row">
                    <input type="text" name="username" class="form-control" value="{{ $user->username ?? old('username') }}"/>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.email')</label>
                <div class="col-lg-8 fv-row">
                    <input type="text" name="email" class="form-control" value="{{ $user->email ?? old('email') }}"/>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.password')</label>
                <div class="col-lg-8 fv-row">
                    <input type="password" name="password" class="form-control" value="{{ old('password') }}"/>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.password_confirmation')</label>
                <div class="col-lg-8 fv-row">
                    <input type="password" name="password_confirmation" class="form-control" value="{{ old('password_confirmation') }}"/>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.api_token')</label>
                <div class="col-lg-8 fv-row">
                    <input type="text" name="api_token" class="form-control" value="{{ old('api_token', $user->api_token) }}" disabled/>
                </div>
            </div>

        </div>
    </div> 

    <div class="card mb-3">
        <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">@lang('word.general_info')</h3>
            </div>
        </div>

        <div class="card-body border-top p-3 p-md-9">

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label  fw-bold fs-6">@lang('word.default_language')</label>
                <div class="col-lg-8 fv-row">
                    <select name="default_language" class="form-select form-select-sm">
                        @foreach ( [
                            "zh_TW" =>'繁體中文',
                            "zh_CN" =>'简体中文',
                            "en" =>'English',
                            "ja" =>'日本語',
                            "ar" =>'العربية',
                            "az" =>'Azərbaycanca / آذربايجان',
                            "bn" =>'বাংলা',
                            "br" =>'Brezhoneg',
                            "cs" =>'Česky',
                            "da" =>'Dansk',
                            "de" =>'Deutsch',
                            "el" =>'Ελληνικά',
                            "es" =>'Español',
                            "fa" =>'فارسی',
                            "fi" =>'Suomi',
                            "fr" =>'Français',
                            "hi" =>'हिन्दी',
                            "hr" =>'Hrvatski',
                            "hu" =>'Magyar',
                            "id" =>'Bahasa Indonesia',
                            "it" =>'Italiano',
                            "km" =>'ភាសាខ្មែរ',
                            "ko" =>'한국어',
                            "nl" =>'Nederlands',
                            "no" =>'Norsk',
                            "pl" =>'Polski',
                            "pt" =>'Português',
                            "ro" =>'Română',
                            "ru" =>'Русский',
                            "si" =>'සිංහල',
                            "sk" =>'Slovenčina',
                            "sl" =>'Slovenščina',
                            "sv" =>'Svenska',
                            "th" =>'ไทย / Phasa Thai',
                            "tr" =>'Türkçe',
                            "vi" =>'Tiếng Việt',
                        ] as $key => $option)
                              <option value="{{ $key }}" @if(($user->default_language == $key) || old('default_language') == $option) selected @endif>{{ $option }}</option>
                        @endforeach

                    </select>
                </div>
            </div>

        

        </div>

    </div>

    <div>
        <button type="submit" class="btn btn-primary w-100 fw-bold" id="kt_account_profile_details_submit">
            @include('backend.parts.submit', ['label' => __('word.edit')])
        </button>
    </div>
</form>

@endsection
