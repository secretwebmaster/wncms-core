@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('contact_form_submission', 'edit') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('contact_form_submissions.update', $contact_form_submission) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body border-top p-3 p-md-9">

                {{-- Status --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label  fw-bold fs-6">@lang('wncms::word.status')</label>

                    <div class="col-lg-8 fv-row">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">@lang('wncms::word.please_select')</option>
                            @foreach(['active','paused','suspended','pending'] as $key => $value)
                                <option  value="{{ $value }}" {{ $value === $contact_form_submission->status ? 'selected' :'' }}><b>@lang('wncms::word.' . $value)</b></option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- 分類 --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label  fw-bold fs-6">@lang('wncms::word.status')</label>
                    <div class="col-lg-8 fv-row">
                        <input class="form-control" name="link_categories" value="{{ implode(',', $contact_form_submission->tagsWithType('contact_form_submission_category')->pluck('name')->toArray()) }}" id="link_categories"/>
                    </div>
                </div>

                <script type="text/javascript">
                    window.addEventListener('DOMContentLoaded', (event) => {
                        //Tagify
                        var input = document.querySelector("#link_categories");
                        var categories = @json($categories);
            
                        // Initialize Tagify script on the above inputs
                        new Tagify(input, {
                            whitelist: categories,
                            maxTags: 10,
                            dropdown: {
                                maxItems: 20,           // <- mixumum allowed rendered suggestions
                                classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                                enabled: 0,             // <- show suggestions on focus
                                closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                                originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(',')
                            }
                        });
                    });
                </script>


                {{-- Site Logo --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.site_logo')</label>
          
                    <div class="col-lg-8">
                        <div class="image-input image-input-outline {{ $contact_form_submission->getFirstMediaUrl('site_logo') ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ $contact_form_submission->getFirstMediaUrl('site_logo') ? 'url('. $contact_form_submission->getFirstMediaUrl('site_logo') .')' : 'none' }};background-size: 100% 100%;"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                <i class="fa fa-pencil fs-7"></i>

                                <input type="file" name="site_logo" accept="image/*"/>
                                <input type="hidden" name="logo_remove"/>
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>

                        <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.site_name')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="site_name" class="form-control form-control-sm" value="{{ $contact_form_submission->site_name ?? old('site_name') }}"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.site_url')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="site_url" class="form-control form-control-sm" value="{{ $contact_form_submission->site_url ?? old('site_url') }}"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.site_slogan')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="site_slogan" class="form-control form-control-sm" value="{{  $contact_form_submission->site_slogan ?? old('site_slogan') }}"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.site_description')</label>
                    <div class="col-lg-8 fv-row">
                        <textarea id="kt_docs_tinymce_basic" name="site_description" class="tox-target">{{ $contact_form_submission->site_description ?? old('site_description') }}</textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.order')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="number" name="order" class="form-control form-control-sm" value="{{ $contact_form_submission->order ?? old('order') }}"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.color')</label>
                    <div class="col-lg-4 fv-row">
                        <div class="input-group mb-5">
                            <input type="text" name="color" class="form-control form-control-sm" value="{{ $contact_form_submission->color ?? old('color') }}"/>
                            <div class="colorpicker-input" data-input="color"></div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.background')</label>
                    <div class="col-lg-4 fv-row">
                        <div class="input-group mb-5">
                            <input type="text" name="background" class=" form-control form-control-sm" value="{{ $contact_form_submission->background ?? old('background') }}"/>
                            <div type="text" class="colorpicker-input" data-input="background"></div>
                        </div>
                    </div>
                </div>

                
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.is_pinned')</label>

                    <div class="col-lg-8 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input type="hidden" name="is_pinned" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" id="is_pinned" name="is_pinned" value="1" {{ $contact_form_submission->is_pinned ? 'checked' : '' }}/>
                            <label class="form-check-label" for="is_pinned"></label>
                        </div>
                    </div>
                </div>



                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.remark')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="remark" class="form-control form-control-sm" value="{{ $contact_form_submission->remark ?? old('remark') }}"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('wncms::word.contact')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="contact" class="form-control form-control-sm" value="{{ $contact_form_submission->contact ?? old('contact') }}"/>
                    </div>
                </div>

        
                {{-- Checkbox --}}
                {{-- <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Communication') }}</label>

                    <div class="col-lg-8 fv-row">
                        <div class="d-flex align-items-center mt-3">
                            <label class="form-check form-check-inline form-check-solid me-5">
                                <input type="hidden" name="communication[email]" value="0">
                                <input class="form-check-input" name="communication[email]" type="checkbox" value="1" {{ old('marketing', $info->communication['email'] ?? '') ? 'checked' : '' }}/>
                                <span class="fw-bold ps-2 fs-6">
                                    {{ __('Email') }}
                                </span>
                            </label>

                            <label class="form-check form-check-inline form-check-solid">
                                <input type="hidden" name="communication[phone]" value="0">
                                <input class="form-check-input" name="communication[phone]" type="checkbox" value="1" {{ old('email', $info->communication['phone'] ?? '') ? 'checked' : '' }}/>
                                <span class="fw-bold ps-2 fs-6">
                                    {{ __('Phone') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div> --}}

                {{-- Select --}}
                {{-- <div class="row mb-3">
                    <label class="col-lg-4 col-form-label  fw-bold fs-6">{{ __('Currency') }}</label>

                    <div class="col-lg-8 fv-row">
                        <select name="currency" aria-label="{{ __('Select a Currency') }}" data-control="select2" data-placeholder="{{ __('Select a currency..') }}" class="form-select form-select-sm">
                            <option value="">{{ __('Select a currency..') }}</option>
                            @foreach(\Wncms\Core\Data::getCurrencyList() as $key => $value)
                                <option data-kt-flag="{{ $value['country']['flag'] }}" value="{{ $key }}" {{ $key === old('currency', $info->currency ?? '') ? 'selected' :'' }}><b>{{ $key }}</b>&nbsp;-&nbsp;{{ $value['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}

                {{-- Switch --}}
                {{-- <div class="row mb-0">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Allow Marketing') }}</label>

                    <div class="col-lg-8 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input type="hidden" name="marketing" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" id="allowmarketing" name="marketing" value="1" {{ old('marketing', $info->marketing ?? '') ? 'checked' : '' }}/>
                            <label class="form-check-label" for="allowmarketing"></label>
                        </div>
                    </div>
                </div> --}}

                {{-- Two Column --}}
                {{-- <div class="row mb-3">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('Full Name') }}</label>

                    <div class="col-lg-8">
                        <div class="row">
                            <div class="col-lg-6 fv-row">
                                <input type="text" name="first_name" class="form-control form-control-sm mb-3 mb-lg-0" placeholder="First name" value="{{ old('first_name', auth()->user()->first_name ?? '') }}"/>
                            </div>

                            <div class="col-lg-6 fv-row">
                                <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Last name" value="{{ old('last_name', auth()->user()->last_name ?? '') }}"/>
                            </div>
                        </div>
                    </div>
                </div> --}}

            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-white btn-active-light-primary me-2">@lang('wncms::word.cancel')</button>

                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('wncms::backend.parts.submit', ['label' => __('word.edit')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection