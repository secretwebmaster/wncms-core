@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('contact_form_submission', 'create') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('contact_form_submissions.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">


                {{-- status --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.status')</label>

                    <div class="col-lg-9 fv-row">
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="">@lang('word.please_select')</option>
                            @foreach(['active','paused','suspended','pending'] as $status)
                                <option  value="{{ $status }}" {{ $status === old('status', $contact_form_submission->status ?? 'active') ? 'selected' :'' }}>@lang('word.' . $status)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- text_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.text_example')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="text_example" class="form-control form-control-sm" value="{{ old('text_example', $contact_form_submission->text_example ?? null) }}"/>
                    </div>
                </div>

                {{-- number_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.number_example')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="number" name="number_example" class="form-control form-control-sm" value="{{ old('number_example', $contact_form_submission->number_example ?? null) }}"/>
                    </div>
                </div>

                
                {{-- select_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.select_example')</label>
                    <div class="col-lg-9 fv-row">
                        <select name="select_example" class="form-select form-select-sm">
                            <option value=""@lang('word.please_select')> @lang('word.select_example')</option>
                            @foreach($select_examples ?? [] as $select_example)
                                <option  value="{{ $select_example }}" {{ $select_example === old('select_example', $contact_form_submission->select_example ?? null) ? 'selected' :'' }}>@lang('word.' . $select_example)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                

                {{-- image_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.image_example')</label>
         
                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ isset($contact_form_submission) && $contact_form_submission->image_example ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ isset($contact_form_submission) && $contact_form_submission->image_example ? 'url('.asset($contact_form_submission->image_example).')' : 'none' }};"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
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
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.textarea_example')</label>
                    <div class="col-lg-9 fv-row">
                        <textarea name="textarea_example" class="form-control" rows="10">{{ $contact_form_submission->textarea_example ?? '' }}</textarea>
                    </div>
                </div>

                {{-- tinymac_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.tinymac_example')</label>
                    <div class="col-lg-9 fv-row">
                        <textarea id="kt_docs_tinymce_basic" name="tinymac_example" class="tox-target">{{ old('tinymac_example', $contact_form_submission->tinymac_example ?? null) }}</textarea>
                    </div>
                </div>

                {{-- color_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.color_example')</label>
                    <div class="col-lg-3 fv-row">
                        <div class="input-group mb-5">
                            <input type="text" name="color_example" class="form-control form-control-sm"/>
                            <div class="colorpicker-input" data-input="color_example"></div>
                        </div>
                    </div>
                </div>

                {{-- switch_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.switch_example')</label>

                    <div class="col-lg-9 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input type="hidden" name="switch_example" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" id="switch_example" name="switch_example" value="1" {{ old('switch_example', $contact_form_submission->switch_example ?? null) ? 'checked' : '' }}/>
                            <label class="form-check-label" for="switch_example"></label>
                        </div>
                    </div>
                </div>


                {{-- checkbox_example --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.checkbox_example')</label>
                    <div class="col-lg-9 fv-row">
                        <div class="d-flex align-items-center mt-3">
                            <label class="form-check form-check-inline form-check-solid me-5">
                                <input type="hidden" name="checkbox_examples[name1]" value="0">
                                <input class="form-check-input" name="checkbox_examples[name1]" type="checkbox" value="1" {{ old('name1', $contact_form_submission->name1 ?? null) ? 'checked' : '' }}/>
                                <span class="fw-bold ps-2 fs-6">@lang('word.checkbox_1')</span>
                            </label>
                            <label class="form-check form-check-inline form-check-solid me-5">
                                <input type="hidden" name="checkbox_examples[name2]" value="0">
                                <input class="form-check-input" name="checkbox_examples[name2]" type="checkbox" value="2" {{ old('name2', $contact_form_submission->name2 ?? null) ? 'checked' : '' }}/>
                                <span class="fw-bold ps-2 fs-6">@lang('word.checkbox_2')</span>
                            </label>
                            <label class="form-check form-check-inline form-check-solid me-5">
                                <input type="hidden" name="checkbox_examples[name3]" value="0">
                                <input class="form-check-input" name="checkbox_examples[name3]" type="checkbox" value="3" {{ old('name3', $contact_form_submission->name3 ?? null) ? 'checked' : '' }}/>
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
                                <input type="text" name="col_1" class="form-control form-control-sm mb-3 mb-lg-0" placeholder="" value="{{ old('col_1', $contact_form_submission->col_1 ?? '') }}"/>
                            </div>

                            <div class="col-lg-6 fv-row">
                                <input type="text" name="col_2" class="form-control form-control-sm" placeholder="" value="{{ old('col_2', $contact_form_submission->col_2 ?? '') }}"/>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- expired_at --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.expired_at')</label>
                    <div class="col-lg-9 fv-row">
                        <input name="expired_at" value="{{ !empty($contact_form_submission->expired_at) ? $contact_form_submission->expired_at->format('Y-m-d') : '' }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_leave_blank')" id="kt_daterangepicker_3" />
                    </div>
                </div>
                <script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        $("#kt_daterangepicker_3").daterangepicker({
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

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.create')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('common.js.tinymce')
@endpush