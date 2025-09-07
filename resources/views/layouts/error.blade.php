<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ asset('wncms/css/style.bundle.css') }}" type="text/css">
        <style>
            body {
                background: linear-gradient(165deg, #a6cbec, #373945);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: "Inter", "Segoe UI", Arial, sans-serif;
                color: #2d3748;
                margin: 0;
            }

            .error-container {
                text-align: center;
                padding: 40px 30px;
                background-color: #fff;
                border-radius: 16px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                width: 90%;
                animation: fadeIn 0.6s ease;
            }

            h1 {
                font-size: 72px;
                font-weight: 800;
                color: #1a202c;
                margin-bottom: 10px;
            }

            p {
                font-size: 18px;
                color: #4a5568;
                margin-bottom: 20px;
            }

            .btn-back {
                display: inline-block;
                padding: 12px 20px;
                font-size: 16px;
                font-weight: 600;
                color: #fff;
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 8px;
                text-decoration: none;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }

            .language-switcher-wrapper {
                position: absolute;
                top: 15px;
                right: 15px;
            }

            .language-switcher-item-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                gap: 6px;
            }

            .language-switcher-item {
                text-decoration: none;
                padding: 5px 10px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: bold;
                color: #2d3748;
                background: rgba(255, 255, 255, 0.8);
                transition: all 0.3s;
            }

            .language-switcher-item:hover {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #fff;
            }

            .language-switcher-item.active {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #fff;
                font-weight: 600;
                box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
            }

            .footer-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .footer-link {
                color: #ffffff;
                text-decoration: none;
                font-weight: 500;
                padding: 6px 10px;
                border-radius: 6px;
                transition: all 0.3s ease;
                position: relative;
            }

            .footer-link::after {
                content: "";
                position: absolute;
                left: 0;
                bottom: -3px;
                width: 0;
                height: 2px;
                background: #ffffff;
                transition: width 0.3s ease;
            }

            .footer-link:hover {
                color: #b9c5f9;
                background: rgba(255, 255, 255, 0.1);
                transform: translateY(-2px);
            }

            .footer-link:hover::after {
                width: 100%;
            }


            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
        @yield('styles')
    </head>


    <body id="wncms_body" class="bg-body" data-kt-name="wncms">

        <div class="language-switcher-wrapper">
            <ul class="language-switcher-item-list">
                @foreach($wncms->getLocaleList() as $key => $locale)
                <li>
                    <a class="language-switcher-item {{ app()->getLocale() === $key ? 'active' : '' }}"
                        href="{{ \wncms()->locale()->getLocalizedURL($key, null, [], true) }}">
                        {{ $locale['native'] }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="d-flex flex-column flex-root" id="wncms_app_root">
            <div class="d-flex flex-column flex-lg-row flex-column-fluid">
                <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1 justify-content-center h-100">

                    {{-- Content --}}
                    <div class="d-flex flex-column flex-center text-center py-5 mt-10">
                        @yield('content')
                    </div>
    
                    {{-- Footer --}}
                    <div class="d-flex flex-center flex-wrap px-5 mt-auto">
                        <div class="d-flex fw-semibold fs-base footer-links">
                            <a href="https://3dayseo.com" target="_blank" class="footer-link">@lang('wncms::word.wn_official_website')</a>
                            <a href="https://wncms.cc" target="_blank" class="footer-link">@lang('wncms::word.wn_official_website')</a>
                            <a href="https://wntheme.com" target="_blank" class="footer-link">@lang('wncms::word.wntheme_official_website')</a>
                            <a href="https://t.me/secretwebmaster" target="_blank" class="footer-link">@lang('wncms::word.live_support')</a>
                            <a href="https://t.me/secretwebmaster" target="_blank" class="footer-link">@lang('wncms::word.contact_to_purchase')</a>
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