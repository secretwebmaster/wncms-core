@php
    // Base key, e.g. "accordion_single"
    $accordionName = $option['name'] ?? null;
    if (!$accordionName) {
        // Invalid config; nothing to render
        return;
    }

    $accordionKey = $accordionName;

    // Repeat count from config
    $repeat = (int) ($option['repeat'] ?? 1);
    if ($repeat < 1) {
        $repeat = 1;
    }

    // Sortable flag
    $sortable = !empty($option['sortable']);

    // Ensure inputNameKey exists
    $inputNameKey ??= 'inputs';

    // DOM id seed
    $accordionDomId = ($uniqueDomId ?? 'accordion_' . $accordionKey) . '_accordion_wrapper';

    // Current saved values:
    // - prefer $currentOptions[$accordionKey] if array
    // - fall back to $currentValue if that holds JSON
    $currentList = $currentOptions[$accordionKey] ?? ($currentValue ?? []);

    if (!is_array($currentList)) {
        $decoded = is_string($currentList) ? json_decode($currentList, true) : [];
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $currentList = $decoded;
        } else {
            $currentList = [];
        }
    }

    // If nothing saved but repeat=1, prepare a single empty row
    if (empty($currentList) && $repeat === 1) {
        $currentList = [[]];
    }

    // Render row count = max(config repeat, saved rows)
    // $rowCount = max($repeat, count($currentList), 1);
    // $rowCount = max(min(count($currentList), $repeat), 1);
    $rowCount = max($repeat, 1);

    // 0..rowCount-1
    $itemOrder = range(0, $rowCount - 1);
@endphp

@if ($rowCount > 1)
    <div class="mb-3">
        <button type="button" class="btn btn-sm btn-dark fw-bold expand-all-accordion-items" data-target=".accordion_{{ $accordionDomId }}">@lang('wncms::word.expand_all')</button>
        <button type="button" class="btn btn-sm btn-dark fw-bold collapse-all-accordion-items" data-target=".accordion_{{ $accordionDomId }}">@lang('wncms::word.collapse_all')</button>
    </div>
@endif

<div class="accordion accordion_{{ $accordionDomId }}" id="{{ $accordionDomId }}">
    @foreach ($itemOrder as $groupIndex)
        @php
            // One row of values, e.g.:
            // ['acc_title' => '11', 'acc_image' => ['remove' => '0'], 'sub_t' => '22', 'sub_n' => '33']
            $rowValue = $currentList[$groupIndex] ?? [];

            // Child input_name_key example:
            // inputs[accordion_single][0]
            $childNameKey = $inputNameKey . '[' . $accordionKey . '][' . $groupIndex . ']';

            // Collapse target id
            $bodyId = $accordionDomId . '_body_' . $groupIndex;
        @endphp

        <div class="accordion-item">
            @if ($sortable)
                <input type="hidden" class="sort-input" name="{{ $inputNameKey }}[{{ $accordionKey }}][{{ $groupIndex }}][sort]" value="{{ $groupIndex }}">
            @endif

            <h2 class="accordion-header">
                <button class="accordion-button collapsed fs-4 text-gray-800 fw-bold p-3 bg-gray-300" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bodyId }}">
                    @if ($sortable)
                        <i class="fa-solid fa-bars me-2 drag-handle"></i>
                    @endif
                    {{ $option['label'] . ' #' . ($groupIndex + 1) }}
                </button>
            </h2>

            <div id="{{ $bodyId }}" class="accordion-collapse collapse" data-bs-parent="#{{ $accordionDomId }}">
                <div class="accordion-body p-3">
                    @foreach ($option['sub_items'] ?? [] as $sub)
                        @php
                            $subType = $sub['type'] ?? null;
                        @endphp

                        @if ($subType === 'inline')
                            @php
                                // Inline group inside accordion:
                                // use the row's values as currentOptions so sub_t/sub_n pick from $rowValue
$indexed = $sub;
$indexed['input_name_key'] = $childNameKey;
                            @endphp

                            @include('wncms::backend.parts.inputs.inline', [
                                'option' => $indexed,
                                'optionIndex' => $optionIndex ?? null,
                                'currentOptions' => $rowValue,
                                'inputNameKey' => $childNameKey,
                                'rowIndex' => $groupIndex,
                                'website' => $website ?? null,
                                'disabled' => $disabled ?? null,
                            ])
                        @else
                            @php
                                $subKey = $sub['name'] ?? null;
                                if (!$subKey) {
                                    // skip invalid child
                                    continue;
                                }

                                $indexed = $sub;
                                $indexed['input_name_key'] = $childNameKey;

                                // Row-level options: ['acc_title' => '11', 'acc_image' => [...], 'sub_t' => '22', 'sub_n' => '33']
                                $nestedCurrentOptions = is_array($rowValue) ? $rowValue : [];
                            @endphp

                            @include('wncms::backend.parts.inputs', [
                                'option' => $indexed,
                                'inputNameKey' => $indexed['input_name_key'],
                                'currentOptions' => $nestedCurrentOptions,
                                'rowIndex' => $groupIndex,
                                'website' => $website ?? null,
                                'disabled' => $disabled ?? null,
                            ])
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    @if ($sortable)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var accEl = document.getElementById('{{ $accordionDomId }}');
                if (!accEl) return;

                new Sortable(accEl, {
                    draggable: '.accordion-item',
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updateSortInputs(evt.from.children);
                    }
                });

                function updateSortInputs(items) {
                    Array.prototype.forEach.call(items, function(item, index) {
                        let input = item.querySelector('.sort-input');
                        if (input) {
                            input.value = index;
                        }
                    });
                }
            });
        </script>
    @endif
</div>
