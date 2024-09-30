{{-- tagType --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tag_type')</label>
    <div class="col-lg-9 fv-row">
        <select name="type" class="form-select form-select-sm" required>
            @foreach($tagTypes as $tagType)
            <option value="{{ $tagType['slug'] }}" @if($tagType['slug'] == $tag->type || $tagType['slug'] == request()->type) selected @endif>{{ $tagType['name'] }} - {{ $tagType['slug'] }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Parent --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.parent_tag')</label>
    <div class="col-lg-9 fv-row">
        <select name="parent_id" class="form-select form-select-sm">
            {{-- load options from js --}}
            <option value="">@lang('word.do_not_have')</option>
        </select>
    </div>
</div>

{{-- Tag name --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.name')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="name" class="form-control form-control-sm" value="{{ $tag->name ?? old('name') }}" />
    </div>
</div>

{{-- Tag slug --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.slug')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="slug" class="form-control form-control-sm" value="{{ $tag->slug ?? old('slug') }}" />
        <div class="text-muted p-2">@lang('word.tag_slug_description')</div>
    </div>
</div>

{{-- Tag description --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tag_description')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $tag->description) }}" />
    </div>
</div>

{{-- Tag background --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tag_background')</label>

    <div class="col-lg-8">
        <div class="image-input image-input-outline {{ $tag->getFirstMediaUrl('tag_background') ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center">
            <div class="image-input-wrapper w-250px h-125px" style="background-image: {{ $tag->getFirstMediaUrl('tag_background') ? 'url('. $tag->getFirstMediaUrl('tag_background') .')' : 'none' }};background-size: 100% 100%;"></div>

            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                <i class="fa fa-pencil fs-7"></i>

                <input type="file" name="tag_background" accept="image/*" />
                <input type="hidden" name="tag_background_remove" />
            </label>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                <i class="fa fa-times"></i>
            </span>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                <i class="fa fa-times"></i>
            </span>
        </div>

        <div class="form-text">@lang('word.allow_file_type'): png, jpg, jpeg. .gif .webp .svg</div>
    </div>
</div>

{{-- Tag image --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tag_thumbnail')</label>
    <div class="col-lg-8">
        <div class="image-input image-input-outline {{ $tag->getFirstMediaUrl('tag_thumbnail') ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center">
            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ $tag->getFirstMediaUrl('tag_thumbnail') ? 'url('. $tag->getFirstMediaUrl('tag_thumbnail') .')' : 'none' }};background-size: 100% 100%;"></div>

            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                <i class="fa fa-pencil fs-7"></i>

                <input type="file" name="tag_thumbnail" accept="image/*" />
                <input type="hidden" name="tag_thumbnail_remove" />
            </label>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                <i class="fa fa-times"></i>
            </span>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                <i class="fa fa-times"></i>
            </span>
        </div>

        <div class="form-text">@lang('word.allow_file_type'): png, jpg, jpeg. .gif .webp .svg</div>
    </div>
</div>

{{-- Icon --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tag_icon')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="icon" class="form-control form-control-sm" value="{{ $tag->icon ?? old('tag_icon') }}" />
        <p>
            icon: <a href="https://fontawesome.com/search?o=r&m=free" target="_blank">https://fontawesome.com/search</a>，只需複製class部分<br>
            例如&#x3C;i class=&#x22;fa-solid fa-thumbs-up&#x22;&#x3E;&#x3C;/i&#x3E;，只填寫fa-solid fa-thumbs-up，其他部分去掉
        </p>
    </div>

</div>

{{-- Order --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.order')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="order_column" class="form-control form-control-sm" value="{{ $tag->order_column ?? old('order_column') }}" />
    </div>
</div>