<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif {{ trans('installer_messages.title') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('wncms/images/logos/favicon.png') }}" sizes="16x16"/>
        <link rel="icon" type="image/png" href="{{ asset('wncms/images/logos/favicon.png') }}" sizes="32x32"/>
        <link rel="icon" type="image/png" href="{{ asset('wncms/images/logos/favicon.png') }}" sizes="96x96"/>
        <link href="{{ asset('wncms/css/fontawesome.min.css?v=')  . config('installer.version') . '.' . wncms()->getVersion('css') }}" rel="stylesheet"/>
        <link href="{{ asset('installer/css/style.css?v=')  . config('installer.version') . '.' . wncms()->getVersion('css') }}" rel="stylesheet"/>
        
        @yield('style')
    </head>
    <body>

        {{-- Language switcher --}}
        <div class="installer-language-switcher">
            <style>
                .language-switcher-wrapper {
                    position: absolute;
                    top: 10px; /* Adjust this value as needed */
                    right: 10px; /* Adjust this value as needed */
                }
        
                .language-switcher-dropdown {
                    padding: 2px;
                    font-size: 12px;
                    background-color: white;
                    border: 1px solid transparent; /* Set to transparent initially */
                    border-radius: 3px;
                    background-image: linear-gradient(white, white), linear-gradient(to right, #590fb7 0%, #ff0076 100%);
                    background-origin: border-box;
                    background-clip: content-box, border-box;
                    margin:0;
                }
        
                .language-switcher-dropdown:hover {

                }
            </style>
        
            <div class="language-switcher-wrapper">
                <select class="language-switcher-dropdown" onchange="window.location.href=this.value;">
                    @foreach($wncms->getLocaleList() as $key => $locale)
                    
                        <option value="{{ \LaravelLocalization::getLocalizedURL($key, null, [], true) }}" @if(app()->getLocale() == $key) selected @endif>{{ $locale['native'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        {{-- Box --}}
        <div class="master">
            <div class="box">
                <div class="header">
                    <h1 class="header__title">@yield('title')</h1>
                </div>

                <ul class="step">

                    <li class="step__divider"></li>
                    <li><i class="step__icon fa-solid fa-lg fa-check" aria-hidden="true"></i></li>

                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('installer.final') }} {{ isActive('installer.environment')}} {{ isActive('installer.wizard')}} {{ isActive('installer.environmentClassic')}}">
                        <i class="step__icon fa-solid fa-server" aria-hidden="true"></i>
                    </li>

                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('installer.permissions') }}">
                        @if(Request::is('install/permissions') || Request::is('install/environment') || Request::is('install/environment/wizard') || Request::is('install/environment/classic') )
                            <a href="{{ route('installer.permissions') }}">
                                <i class="step__icon fa-solid fa-key" aria-hidden="true"></i>
                            </a>
                        @else
                            <i class="step__icon fa-solid fa-key" aria-hidden="true"></i>
                        @endif
                    </li>

                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('installer.requirements') }}">
                        @if(Request::is('install') || Request::is('install/requirements') || Request::is('install/permissions') || Request::is('install/environment') || Request::is('install/environment/wizard') || Request::is('install/environment/classic') )
                            <a href="{{ route('installer.requirements') }}">
                                <i class="step__icon fa-solid fa-list-check" aria-hidden="true"></i>
                            </a>
                        @else
                            <i class="step__icon fa-solid fa-list-check" aria-hidden="true"></i>
                        @endif
                    </li>

                    <li class="step__divider"></li>
                    <li class="step__item {{ isActive('installer.welcome') }}">
                        @if(Request::is('install') || Request::is('install/requirements') || Request::is('install/permissions') || Request::is('install/environment') || Request::is('install/environment/wizard') || Request::is('install/environment/classic') )
                            <a href="{{ route('installer.welcome') }}">
                                <i class="step__icon fa fa-heart" aria-hidden="true"></i>
                            </a>
                        @else
                            <i class="step__icon fa fa-heart" aria-hidden="true"></i>
                        @endif
                    </li>
                    <li class="step__divider"></li>
                </ul>

                <div class="main">

                    @if (session('message'))
                        <p class="alert text-center">
                            <strong>
                                @if(is_array(session('message')))
                                    {{ session('message')['message'] }}
                                @else
                                    {{ session('message') }}
                                @endif
                            </strong>
                        </p>
                    @endif

                    @if(session()->has('errors'))
                        <div class="alert alert-danger" id="error_alert">
                            <button type="button" class="close" id="close_alert" data-dismiss="alert" aria-hidden="true">
                                 <i class="fa fa-close" aria-hidden="true"></i>
                            </button>
                            <h4>
                                <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                                {{ trans('installer_messages.forms.errorTitle') }}
                            </h4>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('container')
                </div>
            </div>
        </div>

        <script src="{{ asset('wncms/js/jquery.min.js?v=') . config('installer.version') . '.' . wncms()->getVersion('js') }}"></script>
        <script src="{{ asset('wncms/js/wncms.js?v=') . config('installer.version') . "." . wncms()->getVersion('js') }}"></script>
        @yield('foot_js')
    </body>
</html>
