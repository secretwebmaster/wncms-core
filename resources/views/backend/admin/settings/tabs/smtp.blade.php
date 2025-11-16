{{-- test_smtp --}}
<button type="button" class="btn btn-sm btn-info fw-bold" data-bs-toggle="modal" data-bs-target="#modal_test_smtp">@lang('wncms::word.test_smtp')</button>
<div class="modal fade" tabindex="-1" id="modal_test_smtp">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('wncms::word.test_smtp')</h3>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">@lang('wncms::word.please_save_settings_before_your_smtp_test')</div>
                <div class="form-item mb-3">
                    <label for="recipient" class="form-label">@lang('wncms::word.recipient')</label>
                    <input type="text" id="recipient" class="form-control">
                </div>
                <div class="smtp-test-result">
                    <label for="recipient" class="form-label">@lang('wncms::word.result')</label>
                    <textarea rows="4" class="form-control" disabled></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                <button type="button" class="btn btn-info fw-bold btn-test">@lang('wncms::word.test')</button>
            </div>
        </div>
    </div>
</div>

@push('foot_js')
<script>
    $('#modal_test_smtp .btn-test').on('click', function(){
        console.log('smtp test');
        var button = $(this);
        button.prop('disabled', true)
        var recipient = $('#recipient').val();
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:"{{ route('settings.smtp_test') }}",
            data:{
                recipient:recipient,
            },
            type:"POST",
            success:function(response){
                console.log(response)
                $('.smtp-test-result textarea').val(response.message);
                button.prop('disabled', false)
            }
        });
    })
</script>
@endpush