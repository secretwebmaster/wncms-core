@if(!empty($dateRangePickerSelector))
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            $("{{ $dateRangePickerSelector }}").daterangepicker({
                autoUpdateInput: false,
                // autoApply:true,
                singleDatePicker: true,
                showDropdowns: true,
                drops:'down',
                timePicker: true,
                minYear: 1901,
                maxYear: parseInt(moment().format("YYYY"),12),
                locale: {
                    cancelLabel: '清空',
                    applyLabel: '設定',
                    format: 'YYYY-MM-DD'
                }
            }).on("apply.daterangepicker", function (e, picker) {
                picker.element.val(picker.startDate.format(picker.locale.format));
            }).on('cancel.daterangepicker', function(e, picker) {
                picker.element.val('');
            });
        });
    </script> 
@endif