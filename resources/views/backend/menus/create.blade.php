@extends('layouts.backend')
@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">@lang('word.create_menu')</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('menus.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">

                {{-- website_id --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.website')</label>
                    <div class="col-lg-8 fv-row">
                        <select name="website_id" class="form-select form-select-sm">
                            <option value="">@lang('word.please_select')</option>
                            @foreach($websites as $w)
                                <option  value="{{ $w->id }}" {{ $w->id === old('website_id') ? 'selected' :'' }}><b>{{ $w->domain }}</b></option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- name --}}
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">@lang('word.name')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}"/>
                    </div>
                </div>

            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-white btn-active-light-primary me-2">@lang('word.cancel')</button>

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