<img 
    class="lazyload" 
    src="{{ asset('wncms/images/placeholders/upload.png') }}" 
    data-src="{{ $model->{$attribute ?? 'thumbnail'} }}" 
    alt=""
    style="width:{{ $image_width ?? 60 }}px;height:{{ $image_height ?? '' }}px"
>