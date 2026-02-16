<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', wncms()->locale()->getCurrentLocale()) }}">

    <head>
        <meta charset="UTF-8">
        <title>{{ $page_title ?? $website->site_name }}</title>

        {{-- Meta --}}
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="keywords" content="{{ $website->site_seo_keywords }}">
        <meta name="description" content="{{ $website->site_seo_description }}">
        {!! $website->meta_verification !!}

        {{-- Favicon --}}
        <link rel="shortcut icon" type="image/x-icon" href="{{ $website->site_favicon ?: asset('wncms/images/logos/favicon.png') }}">

        {{-- Demo Theme CSS --}}
        <link rel="stylesheet" href="{{ wncms()->theme()->asset($themeId, 'css/style.css') }}">

        {{-- Optional FontAwesome --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">

        @stack('head_css')
        <style>
            {!! gto('head_css') !!}
        </style>

        @stack('head_js')
        {!! $website->head_code !!}
    </head>

    <body class="@stack('body_class')">

        {{-- Header --}}
        @include(wncms()->theme()->view($themeId, 'parts.header'))

        {{-- Main Content --}}
        @yield('content')

        {{-- Footer --}}
        @include(wncms()->theme()->view($themeId, 'parts.footer'))

        {{-- JS --}}
        <script src="{{ asset('wncms/js/jquery.min.js') . wncms()->addVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/lazysizes.min.js') . wncms()->addVersion('js') }}"></script>
        <script src="{{ wncms()->theme()->asset($themeId, 'js/app.js') }}"></script>

        @stack('foot_js')

        {!! $website->body_code !!}
        {!! $website->analytics !!}

        {{-- CSS --}}
        @stack('foot_css')
        <style>
            {!! gto('custom_css') !!}
        </style>

    </body>

</html>
