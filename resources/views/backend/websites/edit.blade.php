@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

<div class="card">
    <form class="form" method="POST" action="{{ route('websites.update' , $website) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark" role="button">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0 d-block d-md-flex align-items-center">
                    <span class="text-gray-100">@lang('wncms::word.edit_website')</span>
                    <div class="d-block d-md-flex">
                        <a href="{{ wncms_add_https($website->domain) }}" target="_blank" class="btn btn-sm btn-info fw-bold ms-0 ms-md-3">@lang('wncms::word.current_website'): {{ $website->site_name }} ({{ $website->domain }})</a>
                    </div>
                </h3>
                <div class="card-toolbar flex-row-fluid justify-content-end text-nowrap ms-3">
                    <a href="{{ route('websites.theme.options', $website) }}" class="btn btn-sm btn-light fw-bold">@lang('wncms::word.switch_to_theme_options')</a>
                </div>
            </div>

            <div class="card-title m-0">
                <div class="card-toolbar flex-row-fluid justify-content-end text-nowrap">
                    <button type="submit" wncms-btn-loading class="btn btn-sm btn-primary wncms-submit">
                        @include('wncms::backend.parts.submit', ['label' => __('wncms::word.save_all')])
                    </button>
                </div>
            </div>
        </div>

        <div class="collapse show">
            <div class="card-body border-top p-3 p-md-9">

                {{-- Domain --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.domain')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="domain" class="form-control form-control-sm" value="{{ $website->domain }}" />
                    </div>
                </div>

                {{-- Site Name --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.site_name')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="site_name" class="form-control form-control-sm" value="{{ $website->site_name ?? old('site_name') }}" />
                    </div>
                </div>

                {{-- Other name--}}

                <div class="row mb-1">
                    @php
                    $aliases = $website?->domain_aliases ?? collect();
                    @endphp
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.domain_aliases')</label>
                    <div class="col-lg-9 fv-row">
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-dark px-2 py-1" onclick="sortAliases('created_at')">@lang('wncms::word.created_at')</button>
                            <button type="button" class="btn btn-sm btn-dark px-2 py-1" onclick="sortAliases('domain')">@lang('wncms::word.alphabetic')</button>
                        </div>
                        <textarea class="form-control form-control-sm" name="domain_aliases" id="domain_aliases_textarea" rows="20">{{ $aliases->sortByDesc('created_at')->sortByDesc('id')->implode('domain', "\r\n") }}</textarea>
                    </div>
                </div>

                {{-- Theme --}}
                <div class="row mb-6">
                    <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('wncms::word.theme')</label>
                    <div class="col-lg-9 fv-row">
                        <select name="theme" class="form-select form-select-sm">
                            <option value="">@lang('wncms::word.please_select_theme')</option>
                            @foreach($themes as $theme)
                            <option value="{{ str_replace('frontend/theme/','',$theme) }}" {{ str_replace('frontend/theme/','',$theme)===($website->theme ?? old('theme')) ? 'selected' : '' }}><b>{{ str_replace('frontend/theme/','',$theme) }}</b></option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- site_slogan --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.site_slogan')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="site_slogan" class="form-control form-control-sm" value="{{ $website->site_slogan ?? old('site_slogan') }}" />
                    </div>
                </div>

                {{-- site_seo_escription --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.site_seo_description')</label>
                    <div class="col-lg-9 fv-row">

                        <input type="text" name="site_seo_description" class="form-control form-control-sm" value="{{ $website->site_seo_description ?? old('site_seo_description') }}" />
                    </div>
                </div>

                {{-- site_seo_keywords --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.site_seo_keywords')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="site_seo_keywords" class="form-control form-control-sm" value="{{ $website->site_seo_keywords ?? old('site_seo_keywords') }}" />
                    </div>
                </div>

                {{-- Favicon --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.favicon')</label>

                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ !empty($website->site_favicon) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{!empty($website->site_favicon) ? 'url("'. $website->site_favicon .'")' : 'none' }};background-size: contain;"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="@lang('wncms::word.change_favicon')">
                                <i class="fa fa-pencil fs-7"></i>
                                <input type="file" name="site_favicon" accept="image/*" />
                                <input type="hidden" name="site_favicon_remove" />
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('wncms::word.edit')">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="@lang('wncms::word.remove')">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.logo_black')</label>

                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ !empty($website->site_logo) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url('{{ asset('wncms/images/placeholders/upload.png') }}');background-position:center">
                            <div class="image-input-wrapper w-250px h-50px" style="background-image: {{!empty($website->site_logo) ? 'url("'. $website->site_logo .'")' : 'none' }};background-size: contain;"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="@lang('wncms::word.change_favicon')">
                                <i class="fa fa-pencil fs-7"></i>

                                <input type="file" name="site_logo" accept="image/*" />
                                <input type="hidden" name="site_logo_remove" />
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('wncms::word.edit')">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="@lang('wncms::word.remove')">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.logo_white')</label>

                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ !empty($website->site_logo_white) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center">
                            <div class="image-input-wrapper w-250px h-50px" style="background-image: {{!empty($website->site_logo_white) ? 'url("'. $website->site_logo_white .'")' : 'none' }};background-size: contain;"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="@lang('wncms::word.change_favicon')">
                                <i class="fa fa-pencil fs-7"></i>

                                <input type="file" name="site_logo_white" accept="image/*" />
                                <input type="hidden" name="site_logo_white_remove" />
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('wncms::word.edit')">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="@lang('wncms::word.remove')">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Codes --}}
                @foreach(['meta_verification','head_code','body_code'] as $code_field)
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.' . $code_field)</label>
                    <div class="col-lg-9">
                        <textarea class="form-control form-control-sm" name="{{ $code_field }}" cols="30" rows="4">{{ $website->{$code_field} }}</textarea>
                    </div>
                </div>
                @endforeach

                {{-- analytics_code --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.analytics_code')</label>
                    <div class="col-lg-9">
                        <textarea class="form-control form-control-sm" name="analytics" cols="30" rows="10">{{ $website->analytics }}</textarea>
                    </div>
                </div>

                {{-- enabled_page_cache --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6" for="enabled_page_cache">@lang('wncms::word.enabled_page_cache')</label>
                    <div class="col-lg-9 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input id="enabled_page_cache" type="hidden" name="enabled_page_cache" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" id="enabled_page_cache" name="enabled_page_cache" value="1" {{ old('enabled_page_cache', $website->enabled_page_cache ?? null) ? 'checked' : '' }}/>
                            <label class="form-check-label" for="enabled_page_cache"></label>
                        </div>
                    </div>
                </div>

                {{-- enabled_data_cache --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6" for="enabled_data_cache">@lang('wncms::word.enabled_data_cache')</label>
                    <div class="col-lg-9 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input id="enabled_data_cache" type="hidden" name="enabled_data_cache" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" id="enabled_data_cache" name="enabled_data_cache" value="1" {{ old('enabled_data_cache', $website->enabled_data_cache ?? null) ? 'checked' : '' }}/>
                            <label class="form-check-label" for="enabled_data_cache"></label>
                        </div>
                    </div>
                </div>

                {{-- Remark --}}
                <div class="row mb-1">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.remark')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="remark" class="form-control form-control-sm" value="{{ $website->remark ?? old('remark') }}" />
                    </div>
                </div>

            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('wncms::backend.parts.submit', ['label' => __('wncms::word.edit')])
                </button>
            </div>

        </div>
    </form>
</div>

@endsection

@push('foot_js')
<script>
    const aliases = @json($aliases->toArray());

        function sortAliases(column) {
            const sorted = [...aliases].sort((a, b) => {
                if (column === 'domain') {
                    return a.domain.localeCompare(b.domain);
                }
                return b[column] - a[column]; // Descending
            });

            const textarea = document.getElementById('domain_aliases_textarea');
            textarea.value = sorted.map(item => item.domain).join("\r\n");
        }
</script>
@endpush