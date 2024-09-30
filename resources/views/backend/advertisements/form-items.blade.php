@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush

<div class="card-body border-top p-3 p-md-9">
    {{-- website --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="website_id">@lang('word.website')</label>
        <div class="col-lg-9 fv-row">
            <select id="website_id" name="website_id" class="form-select form-select-sm" required>
                <option value="">@lang('word.please_select')</option>
                @foreach($websites as $website)
                    <option  value="{{ $website->id }}" {{ $website->id === old('website_id', $advertisement?->website?->id ?? '') ? 'selected' : '' }}>{{ $website->domain }} #({{ $website->id }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('word.please_select')</option>
                @foreach(['active','paused','suspended','pending'] as $status)
                    <option  value="{{ $status }}" {{ $status === old('status', $advertisement->status ?? 'active') ? 'selected' :'' }}>@lang('word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- expired_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="expired_at">@lang('word.expired_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="expired_at" name="expired_at" value="{{ !empty($advertisement->expired_at) ? $advertisement->expired_at->format('Y-m-d') : '' }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_leave_blank')" id="date_expired_at" />
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', (event) => {
                $("#expired_at").daterangepicker({
                    autoUpdateInput: false,
                    // autoApply:true,
                    singleDatePicker: true,
                    showDropdowns: true,
                    drops:'down',
                    timePicker: true,
                    minYear: 1901,
                    maxYear: parseInt(moment().format("YYYY"),12),
                    locale: {
                        cancelLabel: '{{ __("word.clear") }}',
                        applyLabel: '{{ __("word.apply") }}',
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

    {{-- name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6 required" for="name">@lang('word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $advertisement->name ?? null) }}" required/>
        </div>
    </div>
    
    {{-- type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value=""@lang('word.please_select')> @lang('word.type')</option>
                @foreach($types ?? [] as $type)
                    <option  value="{{ $type }}" {{ $type === old('type', $advertisement->type ?? null) ? 'selected' :'' }}>@lang('word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>
    
    {{-- position --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.position')</label>
        <div class="col-lg-9 fv-row">
            <select id="position" name="position" class="form-select form-select-sm">
                <option value="">@lang('word.please_select')@lang('word.position')</option>
                @foreach($positions ?? [] as $position)
                    <option  value="{{ $position }}" {{ $position === old('position', $advertisement->position ?? null) ? 'selected' :'' }}>@lang('word.' . $position)</option>
                @endforeach
            </select>
        </div>
    </div>

    
    {{-- order --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="order">@lang('word.order')</label>
        <div class="col-lg-9 fv-row">
            <input id="order" type="text" name="order" class="form-control form-control-sm" value="{{ old('order', $advertisement->order ?? null) }}"/>
        </div>
    </div>

    @foreach([
            'cta_text',
            'url',
            'cta_text_2',
            'url_2',
            'remark',
        ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $advertisement->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- advertisement_thumbnail --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="advertisement_thumbnail">@lang('word.advertisement_thumbnail')</label>
        <div class="col-lg-9">
            <div class="image-input image-input-outline {{ isset($advertisement) && $advertisement->thumbnail ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                <div class="image-input-wrapper w-400px h-150px mw-100" style="background-image: {{ isset($advertisement) && $advertisement->thumbnail ? 'url('.asset($advertisement->thumbnail).')' : 'none' }};background-size: 100% 100%;"></div>

                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="advertisement_thumbnail" accept="image/*"/>
                    {{-- remove image --}}
                    <input type="hidden" name="advertisement_thumbnail_remove"/>
                </label>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                    <i class="fa fa-times"></i>
                </span>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                    <i class="fa fa-times"></i>
                </span>
            </div>

            <div class="form-text">@lang('word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
        </div>
    </div>

    {{-- text_color --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="text_color">@lang('word.text_color')</label>
        <div class="col-lg-3 fv-row">
            <div class="input-group mb-5">
                <input id="text_color" type="text" name="text_color" value="{{ old('text_color', $advertisement->text_color ?? '') }}" class="form-control form-control-sm"/>
                <div class="colorpicker-input" data-input="text_color" data-current="{{ old('text_color', $advertisement->text_color ?? '') }}"></div>
            </div>
        </div>
    </div>
    
    {{-- background_color --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="background_color">@lang('word.background_color')</label>
        <div class="col-lg-3 fv-row">
            <div class="input-group mb-5">
                <input id="background_color" type="text" name="background_color" {{ old('text_color', $advertisement->background_color ?? '') }} class="form-control form-control-sm"/>
                <div class="colorpicker-input" data-input="background_color" data-current="{{ old('text_color', $advertisement->background_color ?? '') }}"></div>
            </div>
        </div>
    </div>
    
    {{-- code --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="code">@lang('word.advertisement_script')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="code" name="code" class="form-control" rows="10">{{ $advertisement->code ?? '' }}</textarea>
        </div>
    </div>
    
    {{-- style --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="style">@lang('word.style')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="style" name="style" class="form-control" rows="10">{{ $advertisement->style ?? '' }}</textarea>
        </div>
    </div>

    {{-- advertisement_tag --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.advertisement_tag')</label>
        <div class="col-lg-9 fv-row">
            <input id="advertisement_tags" class="form-control form-control-sm p-0"  name="advertisement_tags" value="{{ $advertisement?->tagsWithType('advertisement_tag')->implode('name', ',') }}"/>
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#advertisement_tags");
                var advertisement_tags = @json(wncms()->tag()->getTagifyDropdownItems('advertisement_tag'));
    
                console.log(advertisement_tags)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: advertisement_tags,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20,           // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0,             // <- show suggestions on focus
                        closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                    }
                });
            });
        </script>
    </div>

</div>