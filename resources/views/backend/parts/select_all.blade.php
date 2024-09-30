<script>
     window.addEventListener('DOMContentLoaded', (event) => {
        $('.check-all').on('click', function(e){
            if($(this).prop('checked')){
                $(".check-all-wrapper").find('input[type="checkbox"]').prop("checked",true);
            }else{
                $(".check-all-wrapper").find('input[type="checkbox"]').prop("checked",false);
            }
        })
    });
</script>