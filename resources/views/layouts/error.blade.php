<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ asset('wncms/css/style.bundle.css') }}" type="text/css">
        <style>
            body {
                background: linear-gradient(135deg, #f8d7da, #ffffff);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: Arial, sans-serif;
                color: #333;
            }

            .error-container {
                text-align: center;
                padding: 20px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            h1 {
                font-size: 48px;
                color: #721c24;
            }

            p {
                font-size: 18px;
                color: #333;
            }

            .language-switcher-wrapper {
                position: absolute;
                top: 5px;
                right: 5px;
            }
        
            .language-switcher-item-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
            }
        
            .language-switcher-item-list li {
                display: inline-block;
                margin-right: 3px;
            }
        
            .language-switcher-item {
                text-decoration: none;
                padding: 3px 5px;
                border: 1px solid black;
                border-radius: 3px;
                color: black;
                font-size: 12px;
                transition: background-color 0.3s, color 0.3s;
            }
        
            .language-switcher-item:hover {
                background-color: black;
                color: #fff;
            }
        </style>
        @yield('styles')
    </head>


    <body id="wncms_body" class="bg-body" data-kt-name="wncms">

        <div class="language-switcher-wrapper">
            <ul class="language-switcher-item-list">
                @foreach($wncms->getLocaleList() as $key => $locale)
                <li>
                    <a class="language-switcher-item" href="{{\LaravelLocalization::getLocalizedURL($key, null, [], true) }}">{{ $locale['native'] }}</a>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <div class="d-flex flex-column flex-lg-row flex-column-fluid">
                <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">

                    {{-- Content --}}
                    <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                        <div class="w-lg-500px p-10">
                            @yield('content')
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="d-flex flex-center flex-wrap px-5">
                        <div class="d-flex fw-semibold text-primary fs-base">
                            <a href="https://3dayseo.com" target="_blank" class="px-5">@lang('word.wn_official_website')</a>
                            <a href="https://wncms.cc" target="_blank" class="px-5">@lang('word.wn_official_website')</a>
                            <a href="https://wntheme.com" target="_blank" class="px-5">@lang('word.wntheme_official_website')</a>
                            <a href="https://t.me/secretwebmaster" target="_blank" class="px-5">@lang('word.live_support')</a>
                            <a href="https://t.me/secretwebmaster" target="_blank" class="px-5">@lang('word.contact_to_purchase')</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- JS --}}
        {{-- <script src="{{ asset('wncms/js/custom.js') }}"></script> --}}

        @yield('scripts')

    </body>

</html>