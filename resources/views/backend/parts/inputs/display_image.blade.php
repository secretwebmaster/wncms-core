@php
    // Image path
    $imgPath = $option['path'] ?? 'wncms/images/placeholders/upload.png';

    // Bootstrap column classes (e.g. "col-12 col-md-3")
    $colClass = $option['col'] ?? null;

    // Aspect ratio support (e.g. "16/9")
    $aspect = $option['aspect_ratio'] ?? null;
    $aspectData = wncms()->calculateAspect($aspect);

    // Raw width/height
    $rawWidth = $option['width'] ?? null;
    $rawHeight = $option['height'] ?? null;

    // ------------------------------------------------------------
    // Normalize dimensions (safe defaults)
    // ------------------------------------------------------------
    $width = wncms()->normalizeDimension($rawWidth, '100%');   // default responsive width
    $height = wncms()->normalizeDimension($rawHeight, 'auto'); // default auto height

    // ------------------------------------------------------------
    // Aspect ratio automatic calculation
    // ------------------------------------------------------------
    if ($aspectData) {

        // width only → auto height
        if ($rawWidth && !$rawHeight && is_numeric($rawWidth)) {
            $calcH = $rawWidth * $aspectData['ratio']; // h = w * (h/w)
            $height = $calcH . 'px';
        }

        // height only → auto width
        if ($rawHeight && !$rawWidth && is_numeric($rawHeight)) {
            $calcW = $rawHeight * $aspectData['inverse']; // w = h * (w/h)
            $width = $calcW . 'px';
        }

        // no width and no height → fully responsive
        if (!$rawWidth && !$rawHeight) {
            $width = '100%';
            $height = 'auto';
        }
    }

    // ------------------------------------------------------------
    // Final fallback safety
    // ------------------------------------------------------------
    if (!$width) $width = '100%';
    if (!$height) $height = 'auto';

    // Final style
    $style = "width:{$width};height:{$height};object-fit:fill;";
@endphp

<div class="row mb-3 mw-100 mx-0">
    @if ($colClass)
        <div class="{{ $colClass }}">
            <img class="rounded my-3" src="{{ asset($imgPath) }}" style="{{ $style }}">
        </div>
    @else
        <div>
            <img class="rounded my-3" src="{{ asset($imgPath) }}" style="{{ $style }}">
        </div>
    @endif
</div>
