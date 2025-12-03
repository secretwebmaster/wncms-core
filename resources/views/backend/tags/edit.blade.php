@extends('wncms::layouts.backend')
{{-- @push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush --}}
@section('content')

@include('wncms::backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0 text-gray-100">@lang('wncms::word.edit_tag')</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('tags.update', $tag) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="card-body border-top p-3 p-md-9">
                @include('wncms::backend.tags.form-items')
            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-white btn-active-light-primary me-2">@lang('wncms::word.cancel')</button>

                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('wncms::backend.parts.submit', ['label' => __('wncms::word.edit')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@include('wncms::backend.tags.load_parent_tags')
@endpush