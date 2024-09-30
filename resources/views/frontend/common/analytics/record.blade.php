@if(!empty($website->id) && !empty($modelType) && !empty($modelId))
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            // console.log('recording view');
            var cookieName = 'recordView' + '_' + '{{ $website->id }}' + '_' +'{{ $modelType }}' + '_' + '{{ $modelId }}';
            // console.log("cookieName = " + cookieName);
            var recorded = WNCMS.Cookie.Get(cookieName)
            if(!recorded){
                $.ajax({
                    headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url:"{{ route('api.v1.analytics.record') }}",
                    data:{
                        modelType: "{{ $modelType }}",
                        modelId: "{{ $modelId }}",
                        collection: "{{ $collection ?? 'view' }}",
                    },
                    type:"POST",
                    success:function(data){
                        // console.log(data);
                        WNCMS.Cookie.Set(cookieName, 1, 86400);
                        // console.log('record view complete');
                    }
                });
            }else{
                // console.log('already recorded view');
            }
        })
    </script>
@endif