@if(!gss('disable_core_update'))
    @if(!request()->routeIs('updates*'))
        <script>
            $(document).ready(function() {
                console.log("@lang('word.checking_for_updates')");
                $.ajax({
                    headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ route('updates.check') }}",
                    type:"POST",
                    success:function(response){
                        console.log(response);
                        if (response.status === 'success') {
                            if (response.has_update) {
                                $('.global-notification-message').show();
                                $('.global-notification-message').addClass('bg-light-success');
                                $('.global-notification-message-title').text(response.message);
                                if(response.url){
                                    $('.global-notification-message-url a').show()
                                    $('.global-notification-message-url a').attr('href', response.url);
                                    if(response.button_text){
                                        $('.global-notification-message-url a').text(response.button_text);
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
@endif
