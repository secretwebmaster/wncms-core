<button wncms-check-all data-check-target="{{ $check_all_target_class ?? 'item_of_check_all' }}" type="button" class="btn btn-sm btn-dark {{ $custom_class ?? 'px-2 py-1' }} fw-bold">@lang('word.check_all')</button>
@push('foot_js')
<script>
    $('[wncms-check-all]').on('click', function(){
        var target_class = $(this).data('check-target');
        var $target_item = $('.' + target_class);
        
        var is_all_checked = $target_item.length === $target_item.filter(':checked').length;
        
        if (is_all_checked) {
            $target_item.prop('checked', false);
        } else {
            $target_item.prop('checked', true);
        }
    })
</script>
@endpush