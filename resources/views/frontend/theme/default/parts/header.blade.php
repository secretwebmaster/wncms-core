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
</style>

<div class="language-switcher-wrapper">
    <ul class="language-switcher-item-list">
        @foreach($wncms->getLocaleList() as $key => $locale)
        <li>
            <a class="language-switcher-item" href="{{\LaravelLocalization::getLocalizedURL($key, null, [], true) }}">{{ $locale['native'] }}</a>
        </li>
        @endforeach
    </ul>
</div>

{{-- Logo --}}
<div><img src="{{ $website->site_logo }}" alt="LOGO" class="logo"></div>