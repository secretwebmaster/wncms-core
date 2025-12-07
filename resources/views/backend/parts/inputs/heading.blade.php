<div id="{{ $option['label'] ?? '' }}" class="row mb-3 bg-dark rounded mx-0 @if ((!empty($loop) && $loop->iteration !== 1)) mt-20 @endif">
    <h2 class="col-lg-4 col-form-label fw-bold fs-3 text-gray-100 d-inline-block">{{ $option['label'] ?? '' }}</h2>
    @if (!empty($option['description']))
        <h6 class="text-muted">{!! $option['description'] !!}</h6>
    @endif
</div>