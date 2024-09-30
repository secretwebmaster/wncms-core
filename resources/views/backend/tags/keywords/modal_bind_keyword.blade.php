<button type="button" class="btn btn-sm px-2 py-1 btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modal_update_keyword_{{ $binding_model->id }}">@lang('word.edit_keyword')</button>
<div class="modal fade" tabindex="-1" id="modal_update_keyword_{{ $binding_model->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form_bind_keyword_{{ $binding_model->id }}" action="{{ route('tags.keywords.update', ['tag' => $binding_model]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title">#{{ $binding_model->id }} {{ $binding_model->name }}@lang('word.keyword')</h3>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <input id="tagify_tag_keyword_{{ $binding_model->id }}"
                            class="w-100"
                            name="tag_keywords"
                            value="{{ $binding_model->keywords()->pluck('name')->implode(" ,", 'name' ) }}" />
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                    <button type="submit" form-id="form_bind_keyword_{{ $binding_model->id }}" class="btn btn-primary btn-loading">@lang('word.submit')</button>
                </div>

            </form>
        </div>
    </div>
</div>

@push('foot_js')
    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_tag_keyword_{{ $binding_model->id }}");
            var keywords = @json($allKeywords);

            // Initialize Tagify
            tagify = new Tagify(input, {
                whitelist: keywords,
                skipInvalid: true,
                duplicates: false,
                tagTextProp: 'name',
                maxTags: {{ $limit ?? 999 }},
                dropdown: {
                    maxItems: 100,
                    mapValueTo : 'name',
                    classname: "tagify__inline__suggestions",
                    enabled: 0,
                    closeOnSelect: false,
                    searchKeys: ['name'],
                },
            });

            // handle value changes
            input.addEventListener('change', function onChange(e){
                console.log(e.target.value);
            });

            
        });
    </script>
@endpush