@if (!empty($option['repeat']))
    @for ($i = 1; $i <= $option['repeat']; $i++)
        @php
            $suffix = "_{$i}";
            $newOption = $option;
            if (!empty($newOption['sub_items']) && !empty($option['repeat'])) {
                foreach ($newOption['sub_items'] as &$newOptionSubItem) {
                    $newOptionSubItem['name'] .= $suffix;
                }
            }
        @endphp

        <div class="row mb-3 mw-100 mx-0">
            @foreach ($newOption['sub_items'] ?? [] as $sub_item)
                <div class="col">
                    @include('wncms::backend.parts.inputs', ['option' => $sub_item])
                </div>
            @endforeach
        </div>
    @endfor
@else
    <div class="row mb-3 mw-100 mx-0">
        @foreach ($option['sub_items'] ?? [] as $sub_item)
            <div class="col">
                @include('wncms::backend.parts.inputs', ['option' => $sub_item])
            </div>
        @endforeach
    </div>
@endif

{{-- wncms::backend.parts.inputs.inline --}}
{{-- @php
    // number of repeated groups
    $repeatCount = (int) ($option['repeat'] ?? 1);
    if ($repeatCount < 1) $repeatCount = 1;

    // base name key (example: inputs or template_inputs[hero])
    $baseNameKey = $inputNameKey;

    // sub items
    $subItems = $option['sub_items'] ?? [];
@endphp

@if ($repeatCount > 1)

    @for ($i = 1; $i <= $repeatCount; $i++)
        @php
            // clone sub items and append suffix to each name
            $groupSubItems = [];

            foreach ($subItems as $item) {
                $new = $item;
                $new['name'] = $item['name'] . '_' . $i;

                // correct nested input_name_key
                $new['input_name_key'] = $baseNameKey;

                $groupSubItems[] = $new;
            }
        @endphp

        <div class="row mb-3 mw-100 mx-0">
            @foreach ($groupSubItems as $sub)
                <div class="col">
                    @include('wncms::backend.parts.inputs', [
                        'option' => $sub,
                        'optionIndex' => $optionIndex,
                        'currentOptions' => $currentOptions,
                        'inputNameKey' => $baseNameKey,
                        'website' => $website,
                        'disabled' => $disabled,
                    ])
                </div>
            @endforeach
        </div>

    @endfor

@else

    @php
        // only 1 group — no suffix
        $groupSubItems = [];

        foreach ($subItems as $item) {
            $new = $item;
            $new['input_name_key'] = $baseNameKey;
            $groupSubItems[] = $new;
        }
    @endphp

    <div class="row mb-3 mw-100 mx-0">
        @foreach ($groupSubItems as $sub)
            <div class="col">
                @include('wncms::backend.parts.inputs', [
                    'option' => $sub,
                    'optionIndex' => $optionIndex,
                    'currentOptions' => $currentOptions,
                    'inputNameKey' => $baseNameKey,
                    'website' => $website,
                    'disabled' => $disabled,
                ])
            </div>
        @endforeach
    </div>

@endif --}}
