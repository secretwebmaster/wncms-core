{{-- Language swither --}}
<style>
    .language-switcher-wrapper {

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
        border: 1px solid #007bff;
        border-radius: 3px;
        color: #007bff;
        font-size: 12px;
        transition: background-color 0.3s, color 0.3s;
    }

    .language-switcher-item:hover {
        background-color: #007bff;
        color: #fff;
    }

    .user-menu ul{
        display: flex;
        padding: 0;
    }
    .user-menu ul li{
        list-style: none;
        margin-right: 5px;
    }
    .user-menu ul a{
        text-decoration: none;
        padding: 3px 5px;
        border: 1px solid #007bff;
        border-radius: 3px;
        color: #007bff;
        font-size: 12px;
        transition: background-color 0.3s, color 0.3s;
    }
</style>

<div class="language-switcher-wrapper">
    <ul class="language-switcher-item-list">
        @foreach($wncms->getLocaleList() as $key => $locale)
        <li>
            <a class="language-switcher-item" href="{{ wncms()->locale()->getLocalizedURL($key, null, [], true) }}">{{ $locale['native'] }}</a>
        </li>
        @endforeach
    </ul>
</div>

<div class="user-menu">
    <ul class="">
        {{-- User menu --}}
        <li><a href="{{ route('frontend.plans.index') }}">@lang('wncms::word.plans')</a></li>
        @auth
        <li><a href="{{ route('frontend.users.dashboard') }}">@lang('wncms::word.dashboard')</a></li>
        <li><a href="{{ route('frontend.users.profile') }}">@lang('wncms::word.my_account')</a></li>
        <li><a href="{{ route('frontend.users.card') }}">@lang('wncms::word.card_recharge')</a></li>
        <li><a href="{{ route('frontend.users.subscription') }}">@lang('wncms::word.my_subscriptions')</a></li>
        <li><a href="{{ route('frontend.users.logout') }}">@lang('wncms::word.logout')</a></li>
        @else
        <li><a href="{{ route('frontend.users.login') }}">@lang('wncms::word.login')</a></li>
        <li><a href="{{ route('frontend.users.password.forgot') }}">@lang('wncms::word.forgot_password')</a></li>
        <li><a href="{{ route('frontend.users.register') }}">@lang('wncms::word.register')</a></li>
        @endauth
    </ul>
</div>

{{-- Logo --}}
<div><img src="{{ $website->site_logo ?: asset('wncms/images/logos/logo_black.png') }}" alt="LOGO" class="logo"></div>

