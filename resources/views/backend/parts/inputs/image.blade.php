@php
    // section and field names
    $sectionKey = $inputNameKey;
    $fieldKey = $option['name'];
    $baseName = "{$sectionKey}[{$fieldKey}]";

    // saved url
    if (is_array($currentValue)) {
        $currentValue = $currentValue['image'] ?? null;
    }

    $rawWidth = $option['width'] ?? null;
    $rawHeight = $option['height'] ?? null;
    $aspect = $option['aspect_ratio'] ?? null;

    // width only (height missing, aspect missing) → default 16:9
    if ($rawWidth && !$rawHeight && !$aspect) {
        $aspect = '16/9';
    }

    // aspect only (no width, no height) → default width = 300px
    if ($aspect && !$rawWidth && !$rawHeight) {
        $rawWidth = 300;
    }

    $aspectData = wncms()->calculateAspect($aspect);
    $hasAspect = $aspectData && $aspectData['ratio'] > 0;

    $width = wncms()->normalizeDimension($rawWidth, '300px');
    $height = wncms()->normalizeDimension($rawHeight, 'auto');

    // auto height from width
    if ($hasAspect && $rawWidth && !$rawHeight && is_numeric($rawWidth)) {
        $height = $rawWidth * $aspectData['ratio'] . 'px';
    }

    // auto width from height
    if ($hasAspect && $rawHeight && !$rawWidth && is_numeric($rawHeight)) {
        $width = $rawHeight * $aspectData['inverse'] . 'px';
    }

    // final fallback
    if (!$width || $width === 'auto' || $width === '0px') {
        $width = '300px';
    }
    if (!$height || $height === 'auto' || $height === '0px') {
        $height = (300 * 9) / 16 . 'px';
    }
@endphp

<div class="image-input image-input-outline mw-100 {{ !empty($currentValue) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image:url('{{ asset('wncms/images/placeholders/upload.png') }}');background-position:center">

    <div class="image-input-wrapper mw-100"
        style="background-image:{{ !empty($currentValue) ? 'url(' . $currentValue . ')' : 'none' }};background-size:100% 100%;background-repeat:no-repeat;background-position:center;width:{{ $width }} !important;height:{{ $height }} !important;">
    </div>

    @if (!empty($currentValue))
        <input type="hidden" name="{{ $baseName }}[image]" value="{{ $currentValue }}">
    @endif

    @if (empty($option['disabled']) && empty($disabled))
        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change">
            <i class="fa fa-pencil fs-7"></i>
            <input type="file" name="{{ $baseName }}[file]" accept="image/*">
            <input type="hidden" name="{{ $baseName }}[remove]" value="0">
        </label>
    @endif

    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel">
        <i class="fa fa-times"></i>
    </span>

    @if (empty($option['disabled']) && empty($disabled))
        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" onclick="this.closest('.image-input').querySelector('input[name=\'{{ $baseName }}[remove]\']').value = 1">
            <i class="fa fa-times"></i>
        </span>
    @endif

</div>
