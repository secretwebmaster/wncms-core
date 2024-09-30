<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    {{-- Head --}}
    <head>
        {{-- Meta --}}
        <title>{{ !empty($page_title) ? "{$page_title} | " : '' }}{{ gss('system_name') }} @if( gss('system_description')) | {{ gss('system_description') }} @endif</title>
        <meta name="description" content="{{ gss('system_description') }}">
        <meta name="keywords" content="{{ gss('system_keyword') }}">
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ asset('wncms/images/logos/favicon.png') }}">

        {{-- CSS --}}
        {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/> --}}
        <link rel="stylesheet" href="{{ asset('wncms/plugins/global/plugins.bundle.css?v=' . $wncms->getVersion('css')) }}" type="text/css">
        <link rel="stylesheet" href="{{ asset('wncms/css/style.bundle.css?v=' . $wncms->getVersion('css')) }}" type="text/css">
        @stack('head_css')
        <link rel="stylesheet" href="{{ asset('wncms/css/core.css?v=' . $wncms->getVersion('css')) }}" type="text/css">

        {{-- JS --}}
        @stack('head_js')

    </head>


    {{-- Body --}}
    <body id="wncms_body"
        data-kt-app-layout="dark-sidebar"
        data-kt-app-header-fixed="true"
        data-kt-app-sidebar-fixed="true"
        data-kt-app-sidebar-hoverable="true"
        data-kt-app-sidebar-push-header="true"
        data-kt-app-sidebar-push-toolbar="true"
        data-kt-app-sidebar-push-footer="true"
        data-kt-app-toolbar-enabled="true" 
        class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed app-default" 
        style="--wncms-toolbar-height:55px;--wncms-toolbar-height-tablet-and-mobile:55px" 
        data-kt-name="wncms">

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            
                {{-- Header --}}
                @include('backend.parts.header')

                <div class="app-wrapper flex-column flex-row-fluid p-1 p-md-0" id="kt_app_wrapper">

                    {{-- Sidebar --}}
                    @include('backend.parts.sidebar')

                    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">

                        <style>
                            .global-notification-message {
                                background-color: var(--wncms-success-light);
                                color: var(--wncms-success);
                                padding: 5px 20px;
                                display: none
                            }

                            .global-notification-message a {
                                display:none;
                                color: var(--wncms-success);
                                font-weight: bold;
                            }

                            .global-notification-message a:hover {
                                color: var(--wncms-success);
                                text-decoration: underline;
                            }

                            .global-notification-message-title {

                            }
                        </style>
                        
                        <div class="global-notification-message">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="global-notification-message-title"></div>
                                <div class="global-notification-message-url"><a href="javascript:;"></a></div>
                            </div>
                        </div>
                    

                        <div class="d-flex flex-column flex-column-fluid">

                            {{-- Breadcrum  需要傳參數 --}}
                            @include('backend.parts.toolbar')

                            
                            
                            {{-- Content --}}
                            <div id="kt_app_content" class="app-content flex-column-fluid">
                                <div id="kt_app_content_container" class="app-container container-fluid h-100">
                                    @yield('content')
                                </div>
                            </div>

                        </div>

                        {{-- Footer --}}
                        @role('admin')@include('backend.parts.footer')@endrole
                    </div>
                    
                </div>
                
            </div>
        </div>

        {{-- CSS --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>

        {{-- JS --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="{{ asset('wncms/plugins/global/plugins.bundle.js?v=' . $wncms->getVersion('js')) }}"></script>
        <script src="{{ asset('wncms/js/scripts.bundle.js?v=' . $wncms->getVersion('js')) }}"></script>
        <script src="{{ asset('wncms/js/widgets.js?v=' . $wncms->getVersion('js')) }}"></script>
        <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
        <script src="{{ asset('wncms/js/main.js?v=' . $wncms->getVersion('js')) }}"></script>
        <script src="{{ asset('wncms/js/lazysizes.min.js') }}"></script>
        <script src="{{ asset('wncms/js/init.js?v='). $wncms->getVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/wncms.js?v='). $wncms->getVersion('js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>

        @include('backend.common.check_for_update')
        @stack('foot_js')
        @stack('foot_css')

    </body>

</html>
