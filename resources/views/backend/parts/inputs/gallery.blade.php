@php
    $sectionKey = $inputNameKey;
    $fieldKey = $option['name'] ?? 'gallery';

    $fieldBaseName = "{$sectionKey}[{$fieldKey}]";
    $galleryId = 'gallery_' . $fieldKey . '_' . $randomIdSuffix;

    // Responsive parameters
    $desktopCols = $option['desktop_columns'] ?? 4;
    $mobileCols  = $option['mobile_columns'] ?? 2;
    $gap         = $option['gap'] ?? '12px';

    // Prepare current images
    $currentImages = [];

    if (is_array($currentValue)) {
        foreach ($currentValue as $item) {
            $currentImages[] = [
                'image' => $item['image'] ?? '',
                'text'  => $item['text']  ?? '',
                'url'   => $item['url']   ?? '',
            ];
        }
    } elseif (!empty($currentValue) && is_string($currentValue)) {
        $currentImages[] = ['image' => $currentValue, 'text' => '', 'url' => ''];
    }
@endphp

<style>
    #{{ $galleryId }}_preview {
        display: grid;
        grid-template-columns: repeat({{ $mobileCols }}, 1fr);
        gap: {{ $gap }};
    }

    @media (min-width: 768px) {
        #{{ $galleryId }}_preview {
            grid-template-columns: repeat({{ $desktopCols }}, 1fr);
        }
    }

    #{{ $galleryId }}_preview .gallery-item img {
        width: 100%;
        height: auto;
        border-radius: 6px;
        object-fit: cover;
    }
</style>

<div class="mb-3">

    {{-- Existing images --}}
    <div id="{{ $galleryId }}_preview" class="mb-3">

        @foreach ($currentImages as $idx => $img)
            @php
                $url = $img['image'] ?? '';
                $text = $img['text'] ?? '';
                $link = $img['url'] ?? '';
            @endphp

            <div class="gallery-item position-relative">

                <img src="{{ $url }}">

                {{-- hidden fields --}}
                <input type="hidden" name="{{ $fieldBaseName }}[image][]" value="{{ $url }}">

                @if (!empty($option['has_text']))
                    <input type="text" name="{{ $fieldBaseName }}[text][]" class="form-control form-control-sm mt-1" placeholder="Text" value="{{ $text }}">
                @endif

                @if (!empty($option['has_url']))
                    <input type="text" name="{{ $fieldBaseName }}[url][]" class="form-control form-control-sm mt-1" placeholder="URL" value="{{ $link }}">
                @endif

                <input type="hidden" name="{{ $fieldBaseName }}[remove][]" value="0" class="gallery-remove-flag">

                <span class="gallery-remove-existing btn btn-danger position-absolute top-0 end-0 p-0">
                    <i class="fa-solid fa-xmark pe-0"></i>
                </span>
            </div>
        @endforeach

    </div>

    {{-- Upload area --}}
    <div id="{{ $galleryId }}"
         class="wncms-gallery-droparea position-relative text-center p-5 w-100 border border-dashed rounded bg-light cursor-pointer">
        <div class="text-gray-600">@lang('wncms::word.gallery_drag_or_click')</div>
        <input type="file"
               id="{{ $galleryId }}_input"
               name="{{ $fieldBaseName }}[file][]"
               accept="image/*"
               multiple
               class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer">
    </div>

</div>

@push('foot_js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var preview = document.getElementById('{{ $galleryId }}_preview');
    var fileInput = document.getElementById('{{ $galleryId }}_input');
    var filesStore = [];

    // Remove existing
    preview.querySelectorAll('.gallery-remove-existing').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.gallery-item');
            var flag = item.querySelector('.gallery-remove-flag');
            if (!item || !flag) return;

            if (flag.value == "0") {
                flag.value = "1";
                item.style.opacity = "0.4";
                item.style.filter = "grayscale(1)";
                this.classList.replace('btn-danger', 'btn-secondary');
                this.innerHTML = '<i class="fa-solid fa-rotate-left p-0"></i>';
            } else {
                flag.value = "0";
                item.style.opacity = "1";
                item.style.filter = "none";
                this.classList.replace('btn-secondary', 'btn-danger');
                this.innerHTML = '<i class="fa-solid fa-xmark pe-0"></i>';
            }
        });
    });

    function syncFileInput() {
        var dt = new DataTransfer();
        filesStore.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    function createNewPreview(file) {
        var objectUrl = URL.createObjectURL(file);

        var div = document.createElement('div');
        div.classList.add('gallery-item', 'position-relative');
        div.fileObj = file;

        var img = document.createElement('img');
        img.src = objectUrl;

        div.appendChild(img);

        @if (!empty($option['has_text']))
        var textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.name = '{{ $fieldBaseName }}[text][]';
        textInput.classList.add('form-control', 'form-control-sm', 'mt-1');
        textInput.placeholder = 'Text';
        div.appendChild(textInput);
        @endif

        @if (!empty($option['has_url']))
        var urlInput = document.createElement('input');
        urlInput.type = 'text';
        urlInput.name = '{{ $fieldBaseName }}[url][]';
        urlInput.classList.add('form-control', 'form-control-sm', 'mt-1');
        urlInput.placeholder = 'URL';
        div.appendChild(urlInput);
        @endif

        var removeBtn = document.createElement('span');
        removeBtn.classList.add('gallery-remove-new', 'btn', 'btn-danger', 'position-absolute', 'top-0', 'end-0', 'p-0');
        removeBtn.innerHTML = '<i class="fa-solid fa-xmark pe-0"></i>';

        removeBtn.addEventListener('click', function() {
            if (!div.classList.contains('marked-for-remove')) {
                div.classList.add('marked-for-remove');
                div.style.opacity = "0.4";
                div.style.filter = "grayscale(1)";
                this.classList.replace('btn-danger', 'btn-secondary');
                this.innerHTML = '<i class="fa-solid fa-rotate-left p-0"></i>';
                filesStore = filesStore.filter(f => f !== file);
                syncFileInput();
            } else {
                div.classList.remove('marked-for-remove');
                div.style.opacity = "1";
                div.style.filter = "none";
                this.classList.replace('btn-secondary', 'btn-danger');
                this.innerHTML = '<i class="fa-solid fa-xmark pe-0"></i>';
                filesStore.push(file);
                syncFileInput();
            }
        });

        div.appendChild(removeBtn);
        preview.appendChild(div);
    }

    fileInput.addEventListener('change', function() {
        Array.from(this.files).forEach(file => {
            filesStore.push(file);
            createNewPreview(file);
        });
        syncFileInput();
    });
});
</script>
@endpush
