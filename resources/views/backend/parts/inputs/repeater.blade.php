@php
    $rows = [];
    if (is_array($currentValue)) {
        $rows = $currentValue;
    } elseif (is_string($currentValue) && $currentValue !== '') {
        $decoded = json_decode($currentValue, true);
        if (is_array($decoded)) {
            $rows = $decoded;
        }
    }

    $fields = $option['fields'] ?? [];
    $initEmpty = empty($rows);
@endphp

<div class="wncms-repeater-input border rounded p-3" id="{{ $uniqueDomId }}" data-init-empty="{{ $initEmpty ? 'true' : 'false' }}">
    <div data-repeater-list="{{ $inputName }}">
        @forelse($rows as $row)
            <div data-repeater-item class="wncms-repeater-item d-flex align-items-center gap-2 mb-2 overflow-auto">
                @foreach($fields as $field)
                    <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] ?? 'value' }}" class="form-control form-control-sm" placeholder="{{ $field['placeholder'] ?? ($field['label'] ?? '') }}" value="{{ is_array($row) ? ($row[$field['name'] ?? 'value'] ?? '') : '' }}" @disabled(!empty($option['disabled']) || !empty($disabled)) />
                @endforeach
                <button data-repeater-delete type="button" class="btn btn-sm btn-danger" @disabled(!empty($option['disabled']) || !empty($disabled))>X</button>
            </div>
        @empty
            <div data-repeater-item class="wncms-repeater-item d-flex align-items-center gap-2 mb-2 overflow-auto" style="display:none;">
                @foreach($fields as $field)
                    <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] ?? 'value' }}" class="form-control form-control-sm" placeholder="{{ $field['placeholder'] ?? ($field['label'] ?? '') }}" @disabled(!empty($option['disabled']) || !empty($disabled)) />
                @endforeach
                <button data-repeater-delete type="button" class="btn btn-sm btn-danger" @disabled(!empty($option['disabled']) || !empty($disabled))>X</button>
            </div>
        @endforelse
    </div>

    <button type="button" data-repeater-create class="btn btn-sm btn-primary mt-2" @disabled(!empty($option['disabled']) || !empty($disabled))>
        {{ $option['add_label'] ?? __('wncms::word.add_item') }}
    </button>
</div>

@once
    @push('foot_js')
        <script src="{{ asset('wncms/js/jquery.repeater.min.js') . wncms()->addVersion('js') }}"></script>
    @endpush
@endonce

@push('foot_js')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var repeaterRoot = $('#{{ $uniqueDomId }}');
            if (!repeaterRoot.length || typeof repeaterRoot.repeater !== 'function') return;

            repeaterRoot.repeater({
                initEmpty: repeaterRoot.data('init-empty') === true || repeaterRoot.data('init-empty') === 'true',
                defaultValues: {},
                show: function () {
                    $(this).slideDown();
                    $(this).find('input:first').focus();
                },
                hide: function (deleteElement) {
                    $(this).slideUp(deleteElement);
                }
            });
        });
    </script>
@endpush
