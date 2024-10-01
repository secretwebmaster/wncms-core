window.addEventListener('DOMContentLoaded', (event) => {

    //color picker
    var input_names = [];
    var current_colors = [];
    var pickrs = [];
    $(".colorpicker-input").each(function(index , item){

        input_names[index] = $(this).attr('data-input');
        current_colors[index] = $(this).attr('data-current');
        var input_el = $(this).parent().find('input[name="'+ input_names[index] +'"]');


        var pickr = Pickr.create({
            el: ".colorpicker-input",
            theme: "classic", // or 'monolith', or 'nano'
            // default: "#405189",
            useAsButton: false,
            padding: 8,
            closeWithKey: 'Escape',
            defaultRepresentation: 'HEX',
            swatches: [
                "rgba(244, 67, 54, 1)",
                "rgba(233, 30, 99, 0.95)",
                "rgba(156, 39, 176, 0.9)",
                "rgba(103, 58, 183, 0.85)",
                "rgba(63, 81, 181, 0.8)",
                "rgba(33, 150, 243, 0.75)",
                "rgba(3, 169, 244, 0.7)",
                "rgba(0, 188, 212, 0.7)",
                "rgba(0, 150, 136, 0.75)",
                "rgba(76, 175, 80, 0.8)",
                "rgba(139, 195, 74, 0.85)",
                "rgba(205, 220, 57, 0.9)",
                "rgba(255, 235, 59, 0.95)",
                "rgba(255, 193, 7, 1)",
            ],

            components: {
                // Main components
                preview: true,
                opacity: true,
                hue: true,

                // Input / output Options
                interaction: {
                    hex: true,
                    rgba: true,
                    hsva: false,
                    input: true,
                    clear: false,
                    save: false,
                },
            },
            default:current_colors[index] ? current_colors[index] : "#000000"

        });



        pickr[index] = pickr;
        input_el.on('change',function(){
            pickr.setColor(input_el.val())
        })

        pickr.on('change', function(color, source, instance){
            // console.log($(pickr.getRoot().root).find('input[type="text"]'));
            // console.log(color.toHEXA().toString());
            // console.log($(pickr).parent().find('input[type="text"]').length)
            var hex = color.toHEXA().toString();
            // console.log('input[name="'+ input_names[index] +'"]');
            // console.log($('input[name="'+ input_names[index] +'"]'));
            $('input[name="'+ input_names[index] +'"]').val(hex)
        }).on('changestop',function(){
            pickr.applyColor();
            pickr.hide();
        });

      

    });

    //hove url show image
    $('.hover-show-input-image').each(function(){
        var input = $(this);
        var el_id = input.attr('data-hover-show-id');

        input.on('mouseover',function(){
            if(input.val()){
                $('#' + el_id).attr('src', input.val())
                $('#' + el_id).show();
            }
        }).on('mouseout',function(){
            $('#' + el_id).hide();
        })
    });

    // bulk delete model
    $('.bulk_delete_models').off().on('click',function(e){

        e.preventDefault();
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model = $(this).attr('data-model');
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            model_ids.push($(this).attr('data-model-id'));
        });
        if(model_ids.length == 0){
            this.setAttribute("data-kt-indicator", "off");
            $(this).prop('disabled',false);
            return;
        }
        var url = $(this).attr('data-route') ?? "/panel/admin/"+model+"/bulk_delete";
        var force = $(this).attr('data-force') ?? false;

        $.ajax({
            type:'POST',
            url:url,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data:{
                model:model,
                model_ids:model_ids,
                force:force,
                is_ajax:true,
            },
            success:function(data){
                if(data.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: data.message,
                        text: "",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }else{
                    $('.ajax_message').hide();
                    $('.ajax_message.fail').find('.message_content').text(data.message);
                    $('.ajax_message.fail').fadeIn();
                }
            }
        });
    })

    // bulk force delete model
    $('.bulk_force_delete_models').off().on('click',function(e){

        e.preventDefault();
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model = $(this).attr('data-model');
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            model_ids.push($(this).attr('data-model-id'));
        });
        if(model_ids.length == 0){
            this.setAttribute("data-kt-indicator", "off");
            $(this).prop('disabled',false);
            return;
        }
        var url = $(this).attr('data-route') ?? "/panel/admin/"+model+"/bulk_force_delete";
        var force = $(this).attr('data-force') ?? false;

        $.ajax({
            type:'POST',
            url:url,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data:{
                model:model,
                model_ids:model_ids,
                force:force,
                is_ajax:true,
            },
            success:function(data){
                if(data.status == 'success'){
                    location.reload();
                }else{
                    $('.ajax_message').hide();
                    $('.ajax_message.fail').find('.message_content').text(data.message);
                    $('.ajax_message.fail').fadeIn();
                }
            }
        });
    })

    //bulk move
    $('.bulk_move_submit').off().on('click', function(e){
        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            model_ids.push($(this).attr('data-model-id'));
        });
        var website_id = $('.bulk_move_select_website_id[name="website_id"] option:selected').val()

        var url = $(this).attr('data-route') ?? "";

        //ajax move link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                model_ids:model_ids,
                website_id:website_id,
            },
            type:"POST",
            success:function(data){
                alert(data.message);
                location.reload();
            }
        });
    })

    //bulk_delete_demo
    $('.bulk_delete_demo').off().on('click', function(e){
        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var website_id = $('.bulk_delete_demo_select_website_id[name="website_id"] option:selected').val()
        var demo_remark = $('input[name="bulk_delete_demo_remark"]').val()
        var url = $(this).attr('data-route') ?? "";
        if(website_id && url){
            $.ajax({
                headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url:url,
                data:{
                    website_id:website_id,
                    demo_remark:demo_remark,
                },
                type:"POST",
                success:function(data){
                    location.reload()
                }
            });
        }else{
            this.setAttribute("data-kt-indicator", "off");
            $(this).prop('disabled',false);
        }

       
    })

    //bulk edit
    $('.bulk_edit_submit').off().on('click', function(e){

        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            model_ids.push($(this).attr('data-model-id'));
        });

        var url = $(this).attr('data-route') ?? "";


        //ajax edit link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                model_ids:model_ids,
                new_data: $("#form_bulk_edit_link").serialize(),
            },
            type:"POST",
            success:function(data){
                alert(data.message);
                location.reload();
            }
        });
    })

    //bulk clone
    $('.bulk_clone_submit').off().on('click', function(e){

        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            model_ids.push($(this).attr('data-model-id'));
        });

        var website_id = $('.bulk_clone_select_website_id[name="website_id"] option:selected').val()
        var url = $(this).attr('data-route') ?? "";

        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                model_ids:model_ids,
                website_id:website_id,
            },
            type:"POST",
            success:function(data){
                if(data.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: data.message,
                        text: "",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: data.message,
                        text: "",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
            }
        });
    })

    //bulk clone all
    $('.bulk_clone_all_submit').off().on('click', function(e){
        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');

        var from_website_id = $('.bulk_clone_select_website_id[name="from_website_id"] option:selected').val()
        var to_website_id = $('.bulk_clone_select_website_id[name="to_website_id"] option:selected').val()
        var url = $(this).attr('data-route') ?? "";

        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                from_website_id:from_website_id,
                to_website_id:to_website_id,
            },
            type:"POST",
            success:function(data){
                alert(data.message);
                location.reload();
            }
        });
    })

    //bulk assign
    $('.assign_user_to_manage_submit').off().on('click', function(e){

        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var website_ids = [];
        $('.form-check-input:checked').each(function(){
            website_ids.push($(this).attr('data-model-id'));
        });

        var user_ids = $('#assign_user_to_manage input[name="user_ids"]').val();
        var url = $(this).attr('data-route') ?? "";


        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                website_ids:website_ids,
                user_ids:user_ids,
            },
            type:"POST",
            success:function(data){
                alert(data.message);
                location.reload();
            }
        });
    })

    //bulk remove
    $('.remove_user_from_managing_submit').off().on('click', function(e){

        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var website_ids = [];
        $('.form-check-input:checked').each(function(){
            website_ids.push($(this).attr('data-model-id'));
        });

        var user_ids = $('#remove_user_from_managing input[name="user_ids"]').val();
        var url = $(this).attr('data-route') ?? "";

        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                website_ids:website_ids,
                user_ids:user_ids,
            },
            type:"POST",
            success:function(data){
                alert(data.message);
                location.reload();
            }
        });
    })

    //! Checking
    $('.bulk_submit_recheck').off().on('click', function(e){
        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).prop('disabled',true);
        var model_ids = [];
        $('.form-check-input:checked').each(function(){
            if($(this).attr('data-model-id')){
                model_ids.push($(this).attr('data-model-id'));
            }
        });

        if(model_ids.length == 0){
            this.setAttribute("data-kt-indicator", "off");
            $(this).prop('disabled',false);
            return;
        }

        var url = $(this).attr('data-route') ?? "";
        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                model_ids:model_ids,
            },
            type:"POST",
            success:function(data){
                location.reload();
            }
        });
    });

    //! Common Model Editing
    $('.bulk_update_model').off().on('click', function(e){
        e.preventDefault();
        //get ids
        this.setAttribute("data-kt-indicator", "on");
        $(this).attr('disabled','disabled');
        var model_ids = [];
        var model_id_source = $(this).attr('data-id-source') ?? 'data-model-id';
        $('.form-check-input:checked').each(function(){
            if($(this).attr(model_id_source)){
                model_ids.push($(this).attr(model_id_source));
            }
        });

        if(model_ids.length == 0){
            this.setAttribute("data-kt-indicator", "off");
            $(this).prop('disabled',false);
            return;
        }

        var url = $(this).attr('data-route') ?? "";
        var model = $(this).attr('data-model') ?? "";
        var column = $(this).attr('data-column') ?? "";
        var value = $(this).attr('data-value') ?? "";

        //ajax clone link
        $.ajax({
            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            url:url,
            data:{
                model_ids:model_ids,
                model:model,
                column:column,
                value:value,
                
            },
            type:"POST",
            success:function(data){
                location.reload();
            }
        });
    });


});