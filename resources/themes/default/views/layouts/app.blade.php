<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

    <head>
        <title>{{ $page_title ?? $website->site_name }}</title>

        {{-- Meta --}}
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="keywords" content="{{ $website->site_seo_keywords }}">
        <meta name="description" content="{{ $website->site_seo_description }}">
        {!! $website->meta_verification !!}

        {{-- CSS --}}
        <link rel="shortcut icon" type="images/x-icon" href="{{ $website->site_favicon ?: asset('wncms/images/logos/favicon.png') }}" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" referrerpolicy="no-referrer" />
        <link rel="stylesheet" type="text/css" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}" />

        <script>
            window.tailwind = window.tailwind || {};
            window.tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['ui-sans-serif', 'system-ui', 'Segoe UI', 'Roboto', 'Noto Sans', 'Helvetica Neue', 'Arial', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
                        },
                        colors: {
                            brand: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                500: '#0ea5e9',
                                600: '#0284c7',
                                700: '#0369a1'
                            }
                        },
                        boxShadow: {
                            soft: '0 10px 40px -24px rgba(15, 23, 42, 0.55)'
                        }
                    }
                }
            }
        </script>
        <script src="https://cdn.tailwindcss.com"></script>

        @stack('head_css')
        <style>{!! gto('head_css') !!}</style>

        {{-- JS --}}
        @stack('head_js')
        <script src="{{ asset('wncms/js/cookie.js') . wncms()->addVersion('js') }}"></script>
        {!! $website->head_code !!}
    </head>

    <body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
        <div class="flex min-h-screen flex-col">
            {{-- Header --}}
            @include("$themeId::parts.header")

            {{-- Message --}}
            <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                @include('wncms::common.message')
            </div>

            {{-- Page content --}}
            <div class="flex-1">
                @yield('content')
            </div>

            {{-- Footer --}}
            @include("$themeId::parts.footer")
        </div>

        {{-- JS --}}
        @stack('foot_js')
        <script src="{{ asset('wncms/js/jquery.min.js' . wncms()->addVersion('js')) }}"></script>
        <script src="{{ asset('wncms/js/lazysizes.min.js' . wncms()->addVersion('js')) }}"></script>
        {!! $website->body_code !!}
        {!! $website->analytics !!}

        {{-- CSS --}}
        @stack('foot_css')
        <style>{!! gto('custom_css') !!}</style>
    </body>

</html>
