@if(!empty($website->id) && !empty($modelType) && !empty($modelId))
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            $.ajax({
                headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url:"{{ route('api.v1.analytics.get') }}",
                data:{
                    website_id: "{{ $website->id }}",
                    model_type: "{{ $modelType }}",
                    model_id: "{{ $modelId }}",
                    period: "{{ $period }}",
                    collection: "{{ $collection ?? 'view' }}",
                },
                type:"POST",
                success:function(data){
                    console.log(data);
                    $("{{ $view_count_marker ?? '.view_count_marker' }}").text(data.view_counts);
                }
            });
        })
    </script>
@endif