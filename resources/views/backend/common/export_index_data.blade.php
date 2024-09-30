{{-- export_selected --}}
<a href="{{ route(str($modelName)->snake()->plural() . ".export", ['type' => 'selected']) }}" class="btn btn-sm btn-dark fw-bold mb-1 export-data" data-type="selected">@lang('word.export_selected')</a>
@push('foot_js')
    <script>
         $(document).ready(function() {
            $('.export-data[data-type="selected"]').on('click', function(event) {
                event.preventDefault();
                var url = $(this).attr('href');
                var modelIds = WNCMS.CheckBox.Ids();
                var newUrl = url + (url.includes('?') ? '&' : '?') + 'modelIds=' + modelIds.join(',');

                console.log("selected id is " );
                console.log(WNCMS.CheckBox.Ids());
                console.log(newUrl);

                if(modelIds.length){
                    window.open(newUrl, '_blank');

                }else{
                    Swal.fire({
                        icon: "error",
                        title: "{{ __('word.model_ids_are_not_found') }}",
                    })
                }
            })
        });
    </script>
@endpush

{{-- export_current_page --}}
<a href="{{ route(str($modelName)->snake()->plural() . ".export", ['type' => 'current_page', 'page' => request()->page ?? 1, 'page_size' => request()->page_size ?? 20]) }}{{ request()->getQueryString() ? '&' . request()->getQueryString() : '' }}" class="btn btn-sm btn-dark fw-bold mb-1 export-data" data-type="current_page" daata-current-page="{{ request()->page ?? 1 }}">@lang('word.export_current_page')</a>


{{-- export_current_query --}}
<a href="{{ route(str($modelName)->snake()->plural() . ".export", ['type' => 'current_query']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-sm btn-dark fw-bold mb-1 export-data" data-type="current_query">@lang('word.export_current_query')</a>


{{-- export_current_all --}}
<a href="{{ route(str($modelName)->snake()->plural() . ".export", ['type' => 'all']) }}" class="btn btn-sm btn-dark fw-bold mb-1 export-data" data-type="all">@lang('word.export_current_all')</a>
