{{-- @dd($tag) --}}
<script>
    $(document).ready(function () {

        var select_parent = $('[name="parent_id"]');
        var select_type = $('[name="type"]');
        var current_tag = @json($tag??'') ;
        console.log(current_tag);
        var type = select_type.val();
        console.log(type)

        get_parent_tags(type)

        select_type.on('change', function () {
            var new_type = $(this).val();
            console.log(new_type)
            get_parent_tags(new_type)
        });

        function get_parent_tags(type){
            $.ajax({
                headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url:"{{ route('api.v1.tags.index', ['locale' => app()->getLocale()]) }}",
                data:{
                    type:type,
                },
                type:"POST",
                success:function(data){
                    console.log(data);

                    var select_parent = $('[name="parent_id"]');

                    // Clear existing options in the parent select
                    select_parent.empty();

                    // Add a default option
                    select_parent.append('<option value="">@lang("word.do_not_have")</option>');

                    // Populate options based on the AJAX response
                    populateSelectWithTags(data);
                }
            });
        }

        function populateSelectWithTags(tags, prefix = '') {
            $.each(tags, function (key, value) {
                var id = value.id;
                var name = prefix + value.name;

                // Check if the current tag's parent ID matches the current option's ID
                var isSelected = (current_tag.parent_id == id || "{{ request()->parent_id }}" == id) ? 'selected' : '';

                select_parent.append('<option value="' + id + '" ' + isSelected + '>' + name + '</option>');

                // Check if children exist and recursively add options for them
                if (value.children && value.children.length > 0) {
                    var childPrefix = '├─' + prefix; // Add '--' as a prefix for child names
                    populateSelectWithTags(value.children, childPrefix);
                }
            });
        }
    });
</script>