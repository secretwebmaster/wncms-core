<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", \LaravelLocalization::getCurrentLocale()) }}" dir="ltr">

    <head>
        {{-- Meta --}}
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0,minimum-scale=1,maximum-scale=5">
        {!! $website->meta_verification !!}
        @if(!gss('enable_cache') && !$website->enabled_page_cache)
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @endif
        
        <link rel="shortcut icon" type="images/x-icon" href="{{ $website->site_favicon ?: asset('wncms/images/logos/favicon.png') }}" />
        
        {{-- SEO --}}
        <title>{{ $page_title ?? $website->site_name }}</title>
        <meta name="description" content="{{ $website->site_seo_description }}">
        <meta name="keywords" content="{{ $website->site_seo_keywords }}">
        @stack('head_seo')
        
        {{-- JS --}}
        <script src="{{ asset('wncms/js/cookie.js?v=') . $wncms->getVersion('js') }}"></script>
        @stack('head_js')
        {!! $website->head_code !!}

        {{-- CSS --}}
        {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" referrerpolicy="no-referrer" /> --}}
        <link rel="stylesheet" type="text/css" href="{{ asset("theme/starter/css/style.css?v=") . $wncms->getVersion('css') }}" />
        @stack('head_css')
        <style>{!! gto('head_css') !!}</style>
    </head>

    <body itemtype="https://schema.org/WebPage" itemscope="itemscope" class="@stack('body_class')">

        {!! $website->body_code !!}

        @include('frontend.theme.starter.parts.header')

        @yield('content')

        @include('frontend.theme.starter.parts.footer')

        <script src="{{ asset('wncms/js/jquery.min.js?v=' . $wncms->getVersion('js')) }}"></script>
        <script src="{{ asset('wncms/js/lazysizes.min.js?v=' . $wncms->getVersion('js')) }}"></script>
        
        @stack('foot_js')
        @stack('foot_css')

        {!! $website->analytics !!}
        <style>{!! gto('custom_css') !!}</style>
        
    </body>

</html>