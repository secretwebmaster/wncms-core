@extends('layouts.backend')

@section('content')


<div class="row justify-content-center">
    <div class="col-12 col-md-4">

        <div class="card">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.create_website')</h3>
                </div>
            </div>

            <div class="collapse show">
                <form class="form" method="POST" action="{{ route('websites.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body border-top p-3 p-md-9">

                        @include('backend.parts.message')

                        @if(!empty($first_website))
                        <div class="alert alert-success fw-bold">
                            <i class="fa-regular fa-lightbulb text-success"></i>
                            <span class="ms-2">@lang('word.tell_us_about_your_first_website')</span>
                        </div>
                        @endif
                        

                        {{-- License --}}
                        {{-- <div class="row mb-3">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.license')</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="license" class="form-control form-control-sm" value="{{ old('license') }}" />
                                <div class="ps-1 text-info fw-bold">新增更多網站需要購買授權碼，可以登入你的客戶平台獲取</div>
                            </div>
                        </div> --}}

                        {{-- Site Name --}}
                        <div class="row mb-3">
                            <label class="col-lg-12 col-form-label fw-bold fs-6">@lang('word.site_name')</label>
                            <div class="col-lg-12 fv-row">
                                <input type="text" name="site_name" class="form-control form-control-sm" value="{{ old('site_name') }}" required/>
                            </div>
                        </div>

                        {{-- Domain --}}
                        <div class="row mb-3">
                            <label class="col-lg-12 col-form-label fw-bold fs-6">@lang('word.domain')</label>
                            <div class="col-lg-12 fv-row">
                                <input type="text" name="domain" class="form-control form-control-sm" value="{{ old('domain', !empty($first_website) ? request()->getHost() : '') }}" required/>
                                <div class="ps-1 text-muted">@lang('word.enter_domain_such_as_wntheme.com')</div>
                            </div>
                        </div>

                        {{-- Theme --}}
                        <div class="row mb-6">
                            <label class="col-lg-12 col-form-label  fw-bold fs-6">@lang('word.theme')</label>
                            <div class="col-lg-12 fv-row">
                                <select name="theme" class="form-select form-select-sm" required>
                                    <option value="">@lang('word.please_select_theme')</option>
                                    @foreach($themes as $theme)
                                    <option value="{{ str_replace('frontend/theme/','',$theme) }}" {{ str_replace('frontend/theme/','',$theme)===($website->theme ?? 'default' ?? old('theme')) ? 'selected' : '' }}>{{ str_replace('frontend/theme/','',$theme) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <button type="submit" wncms-btn-loading class="btn btn-dark w-100 wncms-submit">
                                @include('backend.parts.submit', ['label' => __('word.create')])
                            </button>
                            {{-- <a href="https://t.me/secretwebmaster" target="_blank" class="btn btn-info w-100 fw-bold me-2 mt-1">@lang('word.purchuse_more_license')</a> --}}
                        </div>


                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection