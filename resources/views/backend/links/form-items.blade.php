<div class="card-body border-top p-3 p-md-9">
    {{-- status --}}
    @if(!empty($statuses))
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
            <div class="col-lg-9 fv-row">
                <select id="status" name="status" class="form-select form-select-sm" required>
                    <option value="">@lang('wncms::word.please_select')</option>
                    @foreach($statuses ?? [] as $status)
                        <option  value="{{ $status }}" {{ $status === old('status', $link->status ?? 'active') ? 'selected' :'' }}>@lang('wncms::word.' . $status)</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    {{-- link_category --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.link_category')</label>
        <div class="col-lg-9 fv-row">
            <input id="link_categories" class="form-control form-control-sm p-0"  name="link_categories" value="{{ $link->tagsWithType('link_category')->implode('name', ',') }}"/>
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#link_categories");
                var link_categories = @json(wncms()->tag()->getTagifyDropdownItems('link_category'));
    
                console.log(link_categories)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: link_categories,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20,           // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0,             // <- show suggestions on focus
                        closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                        searchKeys: ['name','value'],
                    }
                });
            });
        </script>
    </div>
    
    {{-- link_tag --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.link_tag')</label>
        <div class="col-lg-9 fv-row">
            <input id="link_tags" class="form-control form-control-sm p-0"  name="link_tags" value="{{ $link->tagsWithType('link_tag')->implode('name', ',') }}"/>
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#link_tags");
                var link_tags = @json(wncms()->tag()->getTagifyDropdownItems('link_tag'));
    
                console.log(link_tags)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: link_tags,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20,           // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0,             // <- show suggestions on focus
                        closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                        searchKeys: ['name','value'],
                    }
                });
            });
        </script>
    </div>

    @foreach([
        'name',
        'url',
    ] as $field)
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label required fw-bold fs-6" for="{{ $field }}">@lang('wncms::word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $link->{$field} ?? null) }}" required/>
            </div>
        </div>
    @endforeach

    @foreach([
        'tracking_code',
        'slug',
        'slogan',
        'external_thumbnail',
        'contact',
        'remark',
    ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('wncms::word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $link->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="description">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="kt_docs_tinymce_basic" name="description" class="tox-target">{{ old('description', $link->description ?? null) }}</textarea>
        </div>
    </div>

    {{-- sort --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="sort">@lang('wncms::word.sort')</label>
        <div class="col-lg-9 fv-row">
            <input id="sort" type="number" name="sort" class="form-control form-control-sm" value="{{ old('sort', $link->sort ?? null) }}"/>
        </div>
    </div>

    {{-- link_icon --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="link_icon">@lang('wncms::word.link_icon')</label>
        <div class="col-lg-9">
            <div class="image-input image-input-outline {{ isset($link) && $link->icon ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ isset($link) && $link->icon ? 'url('.asset($link->icon).')' : 'none' }};"></div>

                <label ignore-developer-hint class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="link_icon" accept="image/*"/>
                    {{-- remove image --}}
                    <input type="hidden" name="link_icon_remove"/>
                </label>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                    <i class="fa fa-times"></i>
                </span>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                    <i class="fa fa-times"></i>
                </span>
            </div>

            <div class="form-text">@lang('wncms::word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
        </div>
    </div>

    {{-- link_thumbnail --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="link_thumbnail">@lang('wncms::word.link_thumbnail')</label>
        <div class="col-lg-9">
            <div class="image-input image-input-outline {{ isset($link) && $link->thumbnail ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                <div class="image-input-wrapper w-300px h-150px" style="background-image: {{ isset($link) && $link->thumbnail ? 'url('.asset($link->thumbnail).')' : 'none' }};background-size:100% 100%;"></div>

                <label ignore-developer-hint class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="link_thumbnail" accept="image/*"/>
                    {{-- remove image --}}
                    <input type="hidden" name="link_thumbnail_remove"/>
                </label>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                    <i class="fa fa-times"></i>
                </span>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                    <i class="fa fa-times"></i>
                </span>
            </div>

            <div class="form-text">@lang('wncms::word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
        </div>
    </div>
    
    {{-- color --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="color">@lang('wncms::word.color')</label>
        <div class="col-lg-3 fv-row">
            <div class="input-group mb-5">
                <input id="color" type="text" name="color" value="{{ old('color', $link->color ?? '') }}" class="form-control form-control-sm"/>
                <div class="colorpicker-input" data-input="color" data-current="{{ old('color', $link->color ?? '') }}"></div>
            </div>
        </div>
    </div>
    
    {{-- background --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="background">@lang('wncms::word.background')</label>
        <div class="col-lg-3 fv-row">
            <div class="input-group mb-5">
                <input id="background" type="text" name="background" value="{{ old('background', $link->background ?? '') }}" class="form-control form-control-sm"/>
                <div class="colorpicker-input" data-input="background" data-current="{{ old('background', $link->background ?? '') }}"></div>
            </div>
        </div>
    </div>

    {{-- is_pinned --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_pinned">@lang('wncms::word.is_pinned')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_pinned" type="hidden" name="is_pinned" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_pinned" name="is_pinned" value="1" {{ old('is_pinned', $link->is_pinned ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_pinned"></label>
            </div>
        </div>
    </div>

    {{-- is_recommended --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_recommended">@lang('wncms::word.is_recommended')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_recommended" type="hidden" name="is_recommended" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_recommended" name="is_recommended" value="1" {{ old('is_recommended', $link->is_recommended ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_recommended"></label>
            </div>
        </div>
    </div>

    {{-- expired_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="expired_at">@lang('wncms::word.expired_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="expired_at" name="expired_at" value="{{ !empty($link->expired_at) ? $link->expired_at->format('Y-m-d H:i:s') : '' }}" class="form-control form-control-sm" placeholder="@lang('wncms::word.choose_date_or_leave_blank')" />
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
                    timePicker24Hour: true,
                    timePickerSeconds: true,
                    minYear: 1901,
                    maxYear: parseInt(moment().format("YYYY"),12),
                    locale: {
                        cancelLabel: '{{ __("wncms::word.clear") }}',
                        applyLabel: '{{ __("wncms::word.setup") }}',
                        format: 'YYYY-MM-DD HH:mm:ss'
                    }
                }).on("apply.daterangepicker", function (e, picker) {
                    picker.element.val(picker.startDate.format(picker.locale.format));
                }).on('cancel.daterangepicker', function(e, picker) {
                    picker.element.val('');
                });
            });
        </script> 
    </div>

    {{-- hit_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="hit_at">@lang('wncms::word.hit_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="hit_at" name="hit_at" value="{{ !empty($link->hit_at) ? $link->hit_at->format('Y-m-d H:i:s') : '' }}" disabled class="form-control form-control-sm" placeholder="@lang('wncms::word.choose_date_or_leave_blank')" id="date_hit_at" />
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', (event) => {
                $("#hit_at").daterangepicker({
                    autoUpdateInput: false,
                    // autoApply:true,
                    singleDatePicker: true,
                    showDropdowns: true,
                    drops:'down',
                    timePicker: true,
                    timePicker24Hour: true,
                    timePickerSeconds: true,
                    minYear: 1901,
                    maxYear: parseInt(moment().format("YYYY"),12),
                    locale: {
                        cancelLabel: '{{ __("wncms::word.clear") }}',
                        applyLabel: '{{ __("wncms::word.setup") }}',
                        format: 'YYYY-MM-DD HH:mm:ss'
                    }
                }).on("apply.daterangepicker", function (e, picker) {
                    picker.element.val(picker.startDate.format(picker.locale.format));
                }).on('cancel.daterangepicker', function(e, picker) {
                    picker.element.val('');
                });
            });
        </script> 
    </div>

    {{-- created_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="created_at">@lang('wncms::word.created_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="created_at" name="created_at" value="{{ !empty($link->created_at) ? $link->created_at->format('Y-m-d H:i:s') : '' }}" disabled class="form-control form-control-sm" placeholder="@lang('wncms::word.choose_date_or_leave_blank')" id="date_created_at" />
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', (event) => {
                $("#created_at").daterangepicker({
                    autoUpdateInput: false,
                    // autoApply:true,
                    singleDatePicker: true,
                    showDropdowns: true,
                    drops:'down',
                    timePicker: true,
                    timePicker24Hour: true,
                    timePickerSeconds: true,
                    minYear: 1901,
                    maxYear: parseInt(moment().format("YYYY"),12),
                    locale: {
                        cancelLabel: '{{ __("wncms::word.clear") }}',
                        applyLabel: '{{ __("wncms::word.setup") }}',
                        format: 'YYYY-MM-DD HH:mm:ss'
                    }
                }).on("apply.daterangepicker", function (e, picker) {
                    picker.element.val(picker.startDate.format(picker.locale.format));
                }).on('cancel.daterangepicker', function(e, picker) {
                    picker.element.val('');
                });
            });
        </script> 
    </div>

    {{-- updated_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="updated_at">@lang('wncms::word.updated_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="updated_at" name="updated_at" value="{{ !empty($link->updated_at) ? $link->updated_at->format('Y-m-d H:i:s') : '' }}" disabled class="form-control form-control-sm" placeholder="@lang('wncms::word.choose_date_or_leave_blank')" id="date_updated_at" />
        </div>
    </div>

    {{-- clicks --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="clicks">@lang('wncms::word.clicks')</label>
        <div class="col-lg-9 fv-row">
            <input id="clicks" type="number" name="clicks" class="form-control form-control-sm" value="{{ old('clicks', $link->clicks ?? 0) }}"/>
        </div>
    </div>

</div>

@include('wncms::backend.common.developer-hints')