<div id="{{ $option['label'] ?? '' }}" class="row rounded mw-100 mx-0 mb-3 mt-10">
    <h3 class="col-lg-4 col-form-label fw-bold fs-2 text-gray-700 text-decoration-underline">{{ $option['label'] ?? '' }}</h3>
    @if (!empty($option['description']))
        <h6 class="text-gray-900">{!! $option['description'] !!}</h6>
    @endif
</div>