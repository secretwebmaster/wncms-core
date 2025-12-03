@extends('wncms::layouts.backend')
@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}"/>
@endpush

@section('content')

@include('wncms::backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0 text-gray-100">{{ wncms_model_word('parameter', 'create') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('parameters.store') }}" enctype="multipart/form-data">
            @csrf

            @include('wncms::backend.parameters.form-items')

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('wncms::backend.parts.submit', ['label' => __('wncms::word.create')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush