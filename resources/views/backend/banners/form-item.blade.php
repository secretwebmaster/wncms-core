{{-- Website --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.website')</label>
    <div class="col-lg-9 fv-row">
        <div class="d-flex flex-wrap align-items-center mt-3">
            @foreach($websites as $_website)
                <label class="form-check form-check-inline form-check-solid me-5 mb-2">
                    <input class="form-check-input" name="website_ids[]" type="checkbox" value="{{ $_website->id }}"/>
                    <span class="fw-bold ps-2 fs-6">{{ $_website->domain }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>


{{-- Status --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.status')</label>
    <div class="col-lg-9 fv-row">
        <select name="status" class="form-select form-select-sm" required>
            <option value="">@lang('word.please_select')</option>
            @foreach($statuses as $status)
                <option  value="{{ $status }}" {{ $status === ($banner->status ?? old('status', 'active')) ? 'selected' :'' }}><b>@lang('word.' . $status)</b></option>
            @endforeach
        </select>
    </div>
</div>

{{-- banner_thumbnail --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6" for="banner_thumbnail">@lang('word.thumbnail')</label>
    <div class="col-lg-9">
        <div class="image-input image-input-outline {{ isset($banner) && $banner->thumbnail ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
            <div class="image-input-wrapper w-700px h-125px mw-100 mw-100" style="background-image: {{ isset($banner) && $banner->thumbnail ? 'url('.asset($banner->thumbnail).')' : 'none' }};background-size: 100% 100%;"></div>

            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                <i class="fa fa-pencil fs-7"></i>
                <input type="file" name="banner_thumbnail" accept="image/*"/>
                {{-- remove image --}}
                <input type="hidden" name="banner_thumbnail_remove"/>
            </label>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                <i class="fa fa-times"></i>
            </span>

            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                <i class="fa fa-times"></i>
            </span>
        </div>

        <div class="form-text">@lang('word.allow_file_types', ['types' => 'png, jpg, jpeg, gif, webp, svg'])</div>
    </div>
</div>


{{-- Url --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.url')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="url" class="form-control form-control-sm" value="{{ $banner->url ?? old('url') }}"/>
    </div>
</div>

{{-- Order --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.order')</label>
    <div class="col-lg-9 fv-row">
        <input type="number" name="order" class="form-control form-control-sm" value="{{ $banner->order ?? old('order') }}"/>
    </div>
</div>

{{-- contact --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.contact')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="contact" class="form-control form-control-sm" value="{{ $banner->contact ?? old('contact') }}"/>
    </div>
</div>

{{-- remark --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.remark')</label>
    <div class="col-lg-9 fv-row">
        <input type="text" name="remark" class="form-control form-control-sm" value="{{ $banner->remark ?? old('remark') }}"/>
    </div>
</div>

{{-- expired_at --}}
<div class="row mb-1">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.expired_at')</label>
    <div class="col-lg-9 fv-row">
        <input name="expired_at" value="{{ !empty($banner->expired_at) ? $banner->expired_at->format('Y-m-d') : '' }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_leave_blank')" id="kt_daterangepicker_3" />
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            $("#kt_daterangepicker_3").daterangepicker({
                autoUpdateInput: false,
                // autoApply:true,
                singleDatePicker: true,
                showDropdowns: true,
                drops:'up',
                timePicker: true,
                minYear: 1901,
                maxYear: parseInt(moment().format("YYYY"),12),
                locale: {
                    cancelLabel: '清空',
                    applyLabel: '設定',
                    format: 'YYYY-MM-DD'
                }
            }).on("apply.daterangepicker", function (e, picker) {
                picker.element.val(picker.startDate.format(picker.locale.format));
            }).on('cancel.daterangepicker', function(e, picker) {
                picker.element.val('');
            });
        });
    </script> 
</div>

{{-- positions --}}
<div class="row mb-3">
    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.position')</label>
    <div class="col-lg-9">
        <div class="align-items-center mt-3">
            <div class="row w-100 m-0">
                @foreach($positions as $position)
                    <label class="col-12 col-md-3 form-check form-check-inline form-check-solid mx-0 mb-2">
                        <input class="form-check-input" name="positions[]" type="checkbox" value="{{ $position }}" @if(in_array($position, $banner->positions)) checked @endif/>
                        <span class="fw-bold ps-2 fs-6">@lang('word.' . $position)</span>
                    </label>
                @endforeach
            </div>

        </div>
    </div>
</div>