<textarea id="editor_{{ $option['name'] }}" name="{{ $inputName }}" class="tox-target">{{ $currentValue }}</textarea>

<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        var options = {
            selector: "#editor_{{ $option['name'] }}",
            height: 480,
            menubar: true,
            promotion: false,
            language: 'zh_TW',
            toolbar: [
                "styles fontsize fontsizeinput forecolor backcolor bold italic underline lineheight alignleft aligncenter alignright alignjustify wordcount accordion anchor undo redo link unlink bullist numlist outdent indent blockquote image emoticons fullscreen insertdatetime searchreplace table code"
            ],
            plugins: "lists advlist code image table wordcount link emoticons fullscreen insertdatetime searchreplace accordion anchor",
            images_file_types: 'jpg,svg,webp,png',
            images_upload_url: '{{ route('uploads.image') }}',
            // images_upload_base_path: '/some/basepath',
            image_class_list: [{
                    title: 'img-fluid',
                    value: 'img-fluid'
                },


            ],
            image_title: true,
            automatic_uploads: true,
            toolbar_sticky: true,
            toolbar_sticky_offset: 0,
            content_style: 'img { max-width: 100%; height: auto; }',
        }


        if (KTThemeMode.getMode() === "dark") {
            options["skin"] = "oxide-dark";
            options["content_css"] = "dark";
        }

        tinymce.init(options);
    });
</script>