<div class="card-body border-top p-3 p-md-9">
    {{-- status --}}
    @if(!empty($statuses))
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('word.please_select')</option>
                @foreach($statuses ?? [] as $status)
                    <option  value="{{ $status }}" {{ $status === old('status', $starter->status ?? 'active') ? 'selected' :'' }}>@lang('word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    {{-- user --}}
    {{-- <div class="row mb-3">
        <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user" name="user_id" class="form-select form-select-sm">
                <option value=""@lang('word.please_select')> @lang('word.user')</option>
                @foreach($users ?? [] as $user)
                    <option  value="{{ $user->id }}" {{ $user->id === old('user_id', $starter->user?->id ?? null) ? 'selected' :'' }}>{{ $user->username }} #{{ $user->id }}</option>
                @endforeach
            </select>
        </div>
    </div> --}}

    {{-- text_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="text_example">@lang('word.text_example')</label>
        <div class="col-lg-9 fv-row">
            <input id="text_example" type="text" name="text_example" class="form-control form-control-sm" value="{{ old('text_example', $starter->text_example ?? null) }}"/>
        </div>
    </div>

    @foreach([
        'aaa',
        'bbb',
    ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $starter->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- number_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="number_example">@lang('word.number_example')</label>
        <div class="col-lg-9 fv-row">
            <input id="number_example" type="number" name="number_example" class="form-control form-control-sm" value="{{ old('number_example', $starter->number_example ?? null) }}"/>
        </div>
    </div>

    {{-- select_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.select_example')</label>
        <div class="col-lg-9 fv-row">
            <select id="select_example" name="select_example" class="form-select form-select-sm">
                <option value=""@lang('word.please_select')> @lang('word.select_example')</option>
                @foreach($select_examples ?? [] as $select_example)
                    <option  value="{{ $select_example }}" {{ $select_example === old('select_example', $starter->select_example ?? null) ? 'selected' :'' }}>@lang('word.' . $select_example)</option>
                @endforeach
            </select>
        </div>
    </div>
    
    {{-- image_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="image_example">@lang('word.image_example')</label>
        <div class="col-lg-9">
            <div class="image-input image-input-outline {{ isset($starter) && $starter->thumbnail ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ isset($starter) && $starter->thumbnail ? 'url('.asset($starter->thumbnail).')' : 'none' }};"></div>

                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="image_example" accept="image/*"/>
                    {{-- remove image --}}
                    <input type="hidden" name="image_example_remove"/>
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
    
    {{-- textarea_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="textarea_example">@lang('word.textarea_example')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="textarea_example" name="textarea_example" class="form-control" rows="10">{{ $starter->textarea_example ?? '' }}</textarea>
        </div>
    </div>

    {{-- tinymac_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="tinymac_example">@lang('word.tinymac_example')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="kt_docs_tinymce_basic" name="tinymac_example" class="tox-target">{{ old('tinymac_example', $starter->tinymac_example ?? null) }}</textarea>
        </div>
    </div>

    {{-- color_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="color_example">@lang('word.color_example')</label>
        <div class="col-lg-3 fv-row">
            <div class="input-group mb-5">
                <input id="color_example" type="text" name="color_example" {{ old('text_color', $starter->color_example ?? '') }} class="form-control form-control-sm"/>
                <div class="colorpicker-input" data-input="color_example" data-current="{{ old('text_color', $starter->color_example ?? '') }}"></div>
            </div>
        </div>
    </div>

    {{-- switch_example --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="switch_example">@lang('word.switch_example')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="switch_example" type="hidden" name="switch_example" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="switch_example" name="switch_example" value="1" {{ old('switch_example', $starter->switch_example ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="switch_example"></label>
            </div>
        </div>
    </div>

    {{-- checkbox_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="checkbox_example">@lang('word.checkbox_example')</label>
        <div class="col-lg-9 fv-row">
            <div class="d-flex flex-wrap align-items-center mt-3">
                <label class="form-check form-check-inline form-check-solid me-5 mb-1">
                    <input type="hidden" name="checkbox_examples[name1]" value="0">
                    <input class="form-check-input" name="checkbox_examples[name1]" type="checkbox" value="1" {{ old('name1', $starter->name1 ?? null) ? 'checked' : '' }}/>
                    <span class="fw-bold ps-2 fs-6">@lang('word.checkbox_1')</span>
                </label>
                <label class="form-check form-check-inline form-check-solid me-5 mb-1">
                    <input type="hidden" name="checkbox_examples[name2]" value="0">
                    <input class="form-check-input" name="checkbox_examples[name2]" type="checkbox" value="2" {{ old('name2', $starter->name2 ?? null) ? 'checked' : '' }}/>
                    <span class="fw-bold ps-2 fs-6">@lang('word.checkbox_2')</span>
                </label>
                <label class="form-check form-check-inline form-check-solid me-5 mb-1">
                    <input type="hidden" name="checkbox_examples[name3]" value="0">
                    <input class="form-check-input" name="checkbox_examples[name3]" type="checkbox" value="3" {{ old('name3', $starter->name3 ?? null) ? 'checked' : '' }}/>
                    <span class="fw-bold ps-2 fs-6">@lang('word.checkbox_3')</span>
                </label>
            </div>
        </div>
    </div>

    {{-- two_collumns_example --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.two_collumns_example')</label>
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 fv-row">
                    <input type="text" name="col_1" class="form-control form-control-sm mb-3 mb-lg-0" placeholder="" value="{{ old('col_1', $starter->col_1 ?? '') }}"/>
                </div>

                <div class="col-lg-6 fv-row">
                    <input type="text" name="col_2" class="form-control form-control-sm" placeholder="" value="{{ old('col_2', $starter->col_2 ?? '') }}"/>
                </div>
            </div>
        </div>
    </div>

    {{-- expired_at --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="expired_at">@lang('word.expired_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="expired_at" name="expired_at" value="{{ !empty($starter->expired_at) ? $starter->expired_at->format('Y-m-d') : '' }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_leave_blank')" id="date_expired_at" />
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

    {{-- starter_tag --}}
    {{-- <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.starter_tag')</label>
        <div class="col-lg-9 fv-row">
            <input id="starter_tags" class="form-control form-control-sm p-0"  name="starter_tags" value="{{ $starter->tagsWithType('starter_tag')->implode('name', ',') }}"/>
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#starter_tags");
                var starter_tags = @json(wncms()->tag()->getTagifyDropdownItems('starter_tag'));
    
                console.log(starter_tags)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: starter_tags,
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
    </div> --}}

</div>