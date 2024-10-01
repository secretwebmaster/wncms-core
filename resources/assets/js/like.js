window.addEventListener('DOMContentLoaded', function(){

    if($('[wncms-like]').length){
        $('[wncms-like]').each(function(index){

            var model_type = $(this).data('model-type');
            var model_id = $(this).data('model-id');
            var btn_like = $(this);

            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "/api/v1/get_like",
                data: {
                    model_type: model_type,
                    model_id: model_id,
                },
                type: "POST",
                success: function (data) {
                    // console.log(data)
                    if (data.status == 'success') {
                        $('.like_count').text(data.like_count);
                    }
                }
            });

            if(getCookie('liked' + model_type + model_id)){
                btn_like.find('.like_text').text(btn_like.data('liked-text'));
                btn_like.removeAttr('wncms-like');
                btn_like.addAttr('wncms-unlike');
            }
        })

    }

    $('[wncms-like]').on('click', function () {

        var btn_like = $(this);
        var model_type = btn_like.data('model-type');
        var model_id = btn_like.data('model-id');

        if(!getCookie('liked' + model_type + model_id)){
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "/api/v1/record_like",
                data: {
                    model_type: model_type,
                    model_id: model_id,
                },
                type: "POST",
                success: function (data) {
                    console.log(data)
                    if (data.status == 'success') {
                        $('.like_count').text(data.like_count);
                        console.log(btn_like.find('.like_text').text())
                        btn_like.find('.like_text').text(btn_like.data('liked-text'));
                        //likedvideos52227
                        // alert('liked' + model_type + model_id)
                        setCookieSec('liked' + model_type + model_id, true, 60 * 60 * 24);
                        btn_like.removeAttr('wncms-like');
                        btn_like.attr('wncms-unlike', '');

                    }
                }
            });
        }else{

        }

      
    })
});

