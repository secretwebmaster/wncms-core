@if($url)
<a href="{{ wncms_add_https($url) }}" target="_blank">{{ $url }}</a>
@else
<span>{{ $url }}</span>
@endif
