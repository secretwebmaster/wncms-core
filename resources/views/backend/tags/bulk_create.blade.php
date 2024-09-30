@extends('layouts.backend')
@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush
@section('content')

@include('backend.parts.message')
<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">@lang('word.bulk_create_tag')</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('tags.bulk_store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">

                {{-- tag data --}}
                <div class="row mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold fs-6">@lang('word.bulk_tag_data_input_rules')</label>

                        <div class="mb-3">
                            <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit w-100">
                                @include('backend.parts.submit', ['label' => __('word.bulk_create_tag')])
                            </button>
                        </div>

                        <div class="alert alert-info">
                            <ul class="mb-0">
                                <li>格式為: 分類名|代稱|描述</li>
                                <li>分類名為必填，代稱及描述選填</li>
                                <li>一行一個</li>
                                <li>格式:
                                    <p>
                                        分類名稱|代稱|類型|描述|父分類名稱|圖標|排序<br>
                                        name|slug|type|description|parentName|icon|order_column<br><br>

                                        name | 必填 | 應為字符串<br>
                                        slug | 可選 | 默認 = 隨機唯壹的8位字母和數字組合<br>
                                        type | 可選 | 默認 = "post_category"<br>
                                        description | 可選 | 默認 = null<br>
                                        parentName | 可選 | 默認 = null<br>
                                        icon | 可選 | 默認 = null<br>
                                        order_column | 可選 | 默認 = null<br>
                                    </p>
                                </li>
                                <li>例子:
                                    <p>
                                        分類1|代稱01|描述01<br>
                                        分類2|代稱02|描述03<br>
                                        分類3|代稱03<br>
                                        分類4||描述04<br>
                                    </p>
                                </li>
                            </ul>

                        </div>


                    </div>

                    <div class="col-12 col-md-8">
                        <label class="form-label fw-bold fs-6">@lang('word.bulk_tag_data_input')</label>
                        <div class="d-flex flex-nowrap mb-3">
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_name" placeholder="@lang('word.tag_name')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_slug" placeholder="@lang('word.tag_slug')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_type" placeholder="@lang('word.tag_type')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_description" placeholder="@lang('word.tag_description')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_parent_name" placeholder="@lang('word.tag_parent_name')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_icon" placeholder="@lang('word.tag_icon')"></div>
                            <div class="col p-1"><input class="form-control" type="text" data-column="tag_order" placeholder="@lang('word.tag_order')"></div>
                        </div>

                        <div class="d-flex">
                            <div class="p-1"><button class="btn btn-info fw-bold btn-clear" type="button">@lang('word.clear')</button></div>
                            <div class="p-1"><button class="btn btn-success fw-bold btn-restore" type="button">@lang('word.restore')</button></div>
                            <div class="p-1"><button class="btn btn-dark fw-bold btn-append" type="button">@lang('word.add_new_row')</button></div>
                        </div>

                        <textarea name="bulk_tag_data_input" class="tox-target form-control" rows="25">{{ old('bulk_tag_data_input', $placeholder) }}</textarea>
                    </div>
                </div>


            </div>


        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('common.js.tinymce')

<script>
    $(".btn-append").on("click", function() {

            // Get the value of the "tag_name" input field
            var tagName = $(".col input[data-column='tag_name']").val();

            // Check if the "tag_name" is empty
            if (tagName === "") {
                // Show an alert and return, preventing further processing
                Swal.fire({
                    icon: 'error',
                    title: "@lang('word.oops')",
                    text: "@lang('word.tag_name_cannot_be_empty')",
                });

                return;
            }

            // Initialize an array to store the data-column values
            var dataValues = [];

            // Iterate over each input field with data-column attribute
            $(".col input[data-column]").each(function() {
                var value = $(this).val();
                
                // If the value is empty, set it to 0
                if (value === "") {
                    value = "0";
                }

                dataValues.push(value);
            });

            // Join the values with '|'
            var joinedData = dataValues.join('|');

            // Append the joined string to the textarea with a newline
            var $textarea = $("textarea[name='bulk_tag_data_input']");
            
            // Check if the textarea is not empty before adding a new line
            if ($textarea.val().trim() !== "") {
                $textarea.val($textarea.val() + '\n' + joinedData);
            } else {
                $textarea.val(joinedData);
            }
        });

        // Clear button click handler
        $('.btn-clear').on('click', function() {
            var $textarea = $("textarea[name='bulk_tag_data_input']");
            // Get the current content
            var currentContent = $textarea.val();

            // Check if the current content is not empty before clearing and saving
            if (currentContent.trim() !== "") {
                // Save the current content to a data attribute for restoration
                $textarea.data('original-content', currentContent);
            }

            // Clear the textarea
            $textarea.val("");
        });


        // Restore button click handler
        $('.btn-restore').on('click', function() {
            var $textarea = $("textarea[name='bulk_tag_data_input']");
            // Retrieve the original content from the data attribute and set it back to the textarea
            var originalContent = $textarea.data('original-content');
            $textarea.val(originalContent);
        });

</script>
@endpush