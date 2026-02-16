@if($url)
<a href="{{ wncms()->addHttps($url) }}" target="_blank">{{ $url }}</a>
@else
<span>{{ $url }}</span>
@endif
