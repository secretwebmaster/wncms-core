<script>
    //like 
$('.btn-like').on('click', function(){

    var model_type = $(this).data('model-type');
    var model_id = $(this).data('model-id');

    console.log(model_type);
    console.log(model_id);

    $.ajax({
        headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        url:"{{ route('record_like') }}",
        data:{
            model_type: $(this).data('model-type'),
            model_id: $(this).data('model-id'),
        },
        type:"POST",
        success:function(data){
            console.log(data)
            if(data.status == 'success'){
                $('.like_count').text(data.like_count);
            }
        }
    });
    })

</script>