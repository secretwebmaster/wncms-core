@extends('layouts.backend')
@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}"/>
@endpush

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('faq', 'edit') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('faqs.update', $faq) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            @include('backend.faqs.form-items')

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.edit')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('common.js.tinymce')
@endpush