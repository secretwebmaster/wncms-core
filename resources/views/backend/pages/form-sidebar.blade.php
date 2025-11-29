{{-- Sidebar Panels --}}

{{-- Publish --}}
<div class="card mb-5">
    <div class="card-header border-0 p-2 p-md-5">
        <h3 class="fw-bolder m-0">@lang('wncms::word.publish_related')</h3>
    </div>

    <div class="card-body p-2 p-md-5">

        {{-- status --}}
        <div class="form-item mb-3">
            <label class="form-label required fw-bold fs-6">@lang('wncms::word.status')</label>
            <select name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}"
                        {{ $status===old('status',$page->status) ? 'selected':'' }}>
                        @lang('wncms::word.'.$status)
                    </option>
                @endforeach
            </select>
        </div>

        {{-- visibility --}}
        <div class="form-item mb-3">
            <label class="form-label required fw-bold fs-6">@lang('wncms::word.visibility')</label>
            <select name="visibility" class="form-select form-select-sm" required>
                @foreach($visibilities as $visibility)
                    <option value="{{ $visibility }}"
                        {{ $visibility===old('visibility',$page->visibility) ? 'selected':'' }}>
                        @lang('wncms::word.'.$visibility)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary w-100" wncms-btn-loading>
                @include('wncms::backend.parts.submit',['label'=>$submitLabelText])
            </button>
        </div>

        @if(!empty($page->website))
            <div class="mb-3">
                <a href="{{ $wncms->getRoute('frontend.pages',['slug'=>$page->slug],false,$page->website->domain) }}"
                   target="_blank"
                   class="btn btn-dark fw-bold w-100">
                    @lang('wncms::word.preview')
                </a>
            </div>
        @endif

    </div>
</div>

{{-- Author --}}
<div class="card mb-5">
    <div class="card-body p-2 p-md-5">
        <div class="form-item mb-3">
            <label class="form-label fw-bold fs-6">@lang('wncms::word.author')</label>
            <select name="user_id" class="form-select form-select-sm" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        {{ $user->id==$page->user?->id ? 'selected':'' }}>
                        #{{ $user->id }} {{ $user->username }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Images --}}
<div class="card mb-5">
    <div class="card-header border-0 p-2 p-md-5">
        <h3 class="fw-bolder m-0">@lang('wncms::word.images')</h3>
    </div>

    <div class="card-body p-2 p-md-5">

        {{-- thumbnail --}}
        <div class="form-item mb-3">
            <label class="form-label fw-bold fs-6">@lang('wncms::word.thumbnail')</label>

            <div class="image-input image-input-outline w-100
                {{ $page->getFirstMediaUrl('page_thumbnail') ? '' : 'image-input-empty' }}"
                 data-kt-image-input="true"
                 style="background-image:url({{ $page->getFirstMediaUrl('page_thumbnail') ?: asset('wncms/images/placeholders/upload.png') }});background-position:center;">

                <div class="image-input-wrapper w-100 h-100"
                     style="background-image:url('{{ $page->getFirstMediaUrl('page_thumbnail') }}');
                            aspect-ratio:16/10;background-size:cover;">
                </div>

                <label class="btn btn-icon btn-circle bg-body shadow"
                       data-kt-image-input-action="change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="page_thumbnail" accept="image/*">
                    <input type="hidden" name="page_thumbnail_remove">
                </label>

                <span class="btn btn-icon btn-circle bg-body shadow"
                      data-kt-image-input-action="remove">
                    <i class="fa fa-times"></i>
                </span>
            </div>

            <div class="form-text">@lang('wncms::word.allow_image_type')</div>
        </div>

        {{-- external thumbnail --}}
        <div class="form-item mb-3">
            <label class="form-label fw-bold fs-6">@lang('wncms::word.external_thumbnail')</label>
            <input type="text" name="external_thumbnail"
                   class="form-control form-control-sm"
                   value="{{ old('external_thumbnail',$page->external_thumbnail) }}">
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary w-100" wncms-btn-loading>
                @include('wncms::backend.parts.submit',['label'=>$submitLabelText])
            </button>
        </div>

    </div>
</div>

{{-- Attributes --}}
<div class="card mb-5">
    <div class="card-header border-0 p-2 p-md-5">
        <h3 class="fw-bolder m-0">@lang('wncms::word.page_attribute')</h3>
    </div>

    <div class="card-body p-2 p-md-5">

        @foreach(['hide_title'] as $option)
            <div class="row mb-1">
                <div class="col d-flex align-items-center">
                    <div class="form-check form-check-custom form-switch fv-row">
                        <input type="hidden" name="options[{{ $option }}]" value="0">
                        <input class="form-check-input w-35px h-20px"
                               type="checkbox"
                               name="options[{{ $option }}]"
                               value="1"
                            {{ $page->getOption($option) ? 'checked':'' }}>
                    </div>
                </div>
                <label class="col-auto col-form-label fw-bold fs-6 py-1">
                    @lang('wncms::word.'.$option)
                </label>
            </div>
        @endforeach

        <div class="mb-3">
            <button type="submit" class="btn btn-primary w-100" wncms-btn-loading>
                @include('wncms::backend.parts.submit',['label'=>$submitLabelText])
            </button>
        </div>

    </div>
</div>
