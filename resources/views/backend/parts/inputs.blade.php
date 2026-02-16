@php
    $randomIdSuffix ??= md5(microtime(true) . rand(10000, 99999));

    // determine name key for this option
    $inputNameKey ??= $option['input_name_key'] ?? 'inputs';

    $optionName = $option['name'] ?? 'input_name_is_not_set';

    // translation mode
    if (!empty($has_translation) && !empty($locale_key)) {
        $inputName = "translations[{$inputNameKey}][{$locale_key}][{$optionName}]";
        $inputNameRemove = "translations[{$inputNameKey}][{$locale_key}][{$optionName}_remove]";
        $currentValue = !empty($option['name']) ? $currentOptions[$option['name']] ?? '' : '';
    } else {
        $inputName = "{$inputNameKey}[{$optionName}]";
        $inputNameRemove = "{$inputNameKey}[{$optionName}_remove]";
        $currentValue = !empty($option['name']) ? $currentOptions[$option['name']] ?? '' : '';
    }

    // page template override mode
    if (!empty($isPageTemplateValue) && !empty($pageWidgetId) && !empty($option['name'])) {
        if ($option['name'] === 'widget_key') {
            $currentValue = $currentOptions[$pageWidgetId]['widget_key'] ?? null;
        } else {
            $currentValue = $currentOptions[$pageWidgetId]['fields'][$option['name']] ?? null;
        }
    }

    // dom id
    $uniqueDomId = str_replace(['[', ']'], '_', $inputName) . '_' . substr($randomIdSuffix, 0, 6);

    // unique per-field index
    $optionIndex = $uniqueDomId . '_' . Str::random(5);
@endphp

@if ($option['type'] === 'heading')
    @include('wncms::backend.parts.inputs.heading', [
        'option' => $option,
        'optionIndex' => $optionIndex,
    ])
@elseif($option['type'] === 'sub_heading')
    @include('wncms::backend.parts.inputs.sub_heading', [
        'option' => $option,
    ])
@elseif($option['type'] === 'display_image')
    @include('wncms::backend.parts.inputs.display_image', [
        'option' => $option,
    ])
@elseif($option['type'] === 'hidden')
    @include('wncms::backend.parts.inputs.hidden', [
        'inputName' => $inputName,
        'currentValue' => $currentValue,
    ])
@elseif($option['type'] === 'inline')
    @include('wncms::backend.parts.inputs.inline', [
        'option' => $option,
        'optionIndex' => $optionIndex,
        'currentOptions' => $currentOptions,
        'inputNameKey' => $inputNameKey,
        'website' => $website ?? null,
        'disabled' => $disabled ?? null,
    ])
@else
    <div class="row mb-3 mw-100 mx-0 @if (!empty($option['align_items_center'])) align-items-center @endif">

        <label class="col-lg-3 col-form-label fw-bold fs-6 text-nowrap text-truncate @required(!empty($option['required']))"
            title="{{ $option['label'] ?? $option['name'] }}">
            {{ $option['label'] ?? $option['name'] }}
            @if (gss('show_developer_hints'))
                <br><span class="text-secondary small">{{ $option['name'] ?? '' }}</span>
            @endif
        </label>

        <div class="col-lg-9 @if ($option['type'] === 'boolean') d-flex align-items-center @endif">

            @if ($option['type'] === 'text')
                @include('wncms::backend.parts.inputs.text', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'number')
                @include('wncms::backend.parts.inputs.number', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'image')
                @include('wncms::backend.parts.inputs.image', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'select')
                @include('wncms::backend.parts.inputs.select', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'currentOptions' => $currentOptions,
                    'themeId' => $themeId ?? null,
                    'website' => $website ?? null,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'boolean')
                @include('wncms::backend.parts.inputs.boolean', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'editor')
                @include('wncms::backend.parts.inputs.editor', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'uniqueDomId' => $uniqueDomId,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'textarea')
                @include('wncms::backend.parts.inputs.textarea', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'color')
                @include('wncms::backend.parts.inputs.color', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'currentOptions' => $currentOptions,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'repeater')
                @include('wncms::backend.parts.inputs.repeater', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'uniqueDomId' => $uniqueDomId,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'tagify')
                @include('wncms::backend.parts.inputs.tagify', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'uniqueDomId' => $uniqueDomId,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'accordion')
                @include('wncms::backend.parts.inputs.accordion', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => null,
                    'currentOptions' => $currentOptions,
                    'inputNameKey' => $inputNameKey,
                    'website' => $website ?? null,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'gallery')
                @include('wncms::backend.parts.inputs.gallery', [
                    'option' => $option,
                    'inputNameKey' => $inputNameKey,
                    'currentValue' => $currentValue,
                    'currentOptions' => $currentOptions,
                    'website' => $website ?? null,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'package')
                @include('wncms::backend.parts.inputs.package', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'currentOptions' => $currentOptions,
                    'website' => $website ?? null,
                    'disabled' => $disabled ?? null,
                ])
            @elseif($option['type'] === 'custom')
                @includeIf($option['custom_view'] ?? '', [
                    'option' => $option,
                    'inputName' => $inputName,
                    'currentValue' => $currentValue,
                    'currentOptions' => $currentOptions,
                    'website' => $website ?? null,
                    'disabled' => $disabled ?? null,
                ])
            @endif

            @if (!empty($option['description']))
                <div class="text-muted p-2">{{ $option['description'] }}</div>
            @endif
        </div>

    </div>
@endif
