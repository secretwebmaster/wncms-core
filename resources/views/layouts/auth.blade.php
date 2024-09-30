<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

    <head>
        <meta charset="utf-8">
        <title>{{ $website?->site_name ?? gss('system_name') ?? __('word.wncms') }}</title>
        <meta name="description" content="{{ $website?->site_seo_description ?? gss('system_description') }}">
        <meta name="keywords" content="{{ $website?->site_keyword  ?? gss('system_keyword') }}">
        <link rel="canonical" href="{{ wncms_add_https($website?->domain) }}">
        <link rel="shortcut icon" href="{{ $website?->site_favicon ?: asset('wncms/images/logos/favicon.png') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- CSS --}}
        <link rel="stylesheet" href="{{ asset('wncms/plugins/global/plugins.bundle.css') }}" type="text/css">
        <link rel="stylesheet" href="{{ asset('wncms/plugins/global/plugins-wncms.bundle.css') }}" type="text/css">
        <link rel="stylesheet" href="{{ asset('wncms/css/style.bundle.css') }}" type="text/css">
        <style>
            body{
                background-image:url('{{ asset("wncms/images/backgrounds/notebook-background.webp") }}');
                background-size: cover;
                background-repeat: no-repeat; 
            }
            .wncms-auth-form-wrapper{
                background: #25252521;
                border-radius: 5px;
                backdrop-filter: blur(24px);
                box-shadow: 0px 3px 10px 2px #0000003b;
            }
        </style>
        @stack('head_css')
        <link rel="stylesheet" href="{{ asset('wncms/css/core.css?v=' . $wncms->getVersion('css')) }}" type="text/css">

        {{-- JS --}}
        @stack('head_js')
    </head>

    <body id="wncms_body" class="bg-body" data-kt-name="wncms">

        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <div class="d-flex flex-column flex-lg-row flex-column-fluid  flex-column-reverse">
                <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1 vh-100 justify-content-center">

                    {{-- Login Form --}}
                    <div class="d-flex flex-center flex-column flex-lg-row-fluid mb-10">
                        <div class="wncms-auth-form-wrapper w-100 w-lg-500px mw-100 px-5 px-md-10 py-5">
                            @yield('auth_content')
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="d-flex flex-center flex-wrap px-5" id="auth-footer">
                        <div class="d-flex flex-wrap justify-content-center fw-bold fs-base">
                            <a href="https://3dayseo.com" target="_blank" class="link-light px-3 mb-3">@lang('word.wn_official_website')</a>
                            <a href="https://wncms.cc" target="_blank" class="link-light px-3 mb-3">@lang('word.wncms_official_website')</a>
                            <a href="https://wntheme.com" target="_blank" class="link-light px-3 mb-3">@lang('word.wntheme_official_website')</a>
                            <a href="https://t.me/secretwebmaster" target="_blank" class="link-light px-3 mb-3">@lang('word.live_support')</a>
                        </div>
                    </div>

                </div>

                {{-- Side Image --}}
                @if(gss('show_auth_page_side_image'))
                    <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" style="background-image: url({{ asset('wncms/images/backgrounds/search_bg.jpg') }})">
                        <div class="d-flex flex-column flex-center py-15 px-5 px-md-15 w-100 vh-100">
                            <a href="{{ route('frontend.pages.home') }}" class="mw-75 mb-12 text-center">
                                <img alt="Logo" src="{{ $website?->site_logo ?: asset('wncms/images/logos/logo_white.png') }}" class="w-75">
                            </a>
                            <img class="mx-auto w-275px w-md-50 mb-10 mb-lg-20" src="{{ asset('wncms/images/logos/qr-code.png') }}" alt="">
                            <h1 class="text-white fs-2qx fw-bolder text-center mb-7">{{ $website?->site_keyword  ?? gss('system_keyword') }}</h1>
                            <div class="text-white fs-base text-center">{{ $website?->site_seo_description  ?? gss('system_description') }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- JS --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('wncms/plugins/global/plugins.bundle.js?v='). $wncms->getVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/scripts.bundle.js?v=') . $wncms->getVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/widgets.js?v=') . $wncms->getVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/wncms.js?v=') . $wncms->getVersion('js') }}"></script>
        {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/js/formValidation.min.js"></script> --}}
        {{-- <script src="{{ asset('wncms/js/custom/authentication/sign-in/general.js?v=') . $wncms->getVersion('js') }}"></script> --}}

        @stack('foot_js')

    </body>

</html>