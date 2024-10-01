window.addEventListener('DOMContentLoaded', (event) => {
    $('.wndh_click').on('click', function(e){
        var link_id = $(this).attr('data-id');
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:"/click",
            data:{
                'link_id':link_id
            },
            type:"POST",
            success:function(data){
                console.log(data.message);
            }
        });
    });

    $('.wndh_banner_click').on('click', function(e){
        var banner_id = $(this).attr('data-id');
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:"/click/banner",
            data:{
                'banner_id':banner_id
            },
            type:"POST",
            success:function(data){
                console.log(data.message);
            }
        });
    });
  
});