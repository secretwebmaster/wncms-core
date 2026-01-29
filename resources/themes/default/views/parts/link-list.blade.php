<h2>@lang('wncms::word.link_list')</h2>
<div class="wn-links-container">
    <ul>
        @foreach(wncms()->link()->getList(['count' => 10, 'multi_website' => false]) as $link)
            <li 
                wncms-click-record 
                data-clickable-id="{{ $link->id }}" 
                data-clickable-type="Wncms\Models\Link"
                data-name="{{ $link->name }}"
                data-value="{{ $link->url }}"
            >
                <a href="{{ $link->url }}" target="_blank">{{ $link->name }}</a>
            </li>
        @endforeach
    </ul>
</div>

@if(gss('multi_website'))
<h2>@lang('wncms::word.link_list') ({{ $website->domain }})</h2>
<div class="wn-links-container">
    <ul>
        @foreach(wncms()->link()->getList(['count' => 10]) as $link)
            <li 
                wncms-click-record 
                data-clickable-id="{{ $link->id }}" 
                data-clickable-type="Wncms\Models\Link"
                data-name="{{ $link->name }}"
                data-value="{{ $link->url }}"
            >
                <a href="{{ $link->url }}" target="_blank">{{ $link->name }}</a>
            </li>
        @endforeach
    </ul>
</div>
@endif