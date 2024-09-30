@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

    <div class="card">
        <div class="card-header border-0 cursor-pointer px-3 px-md-9">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ wncms_model_word('banner', 'create') }}</h3>
            </div>
        </div>
        <div class="collapse show">
            <form class="form" method="POST" action="{{ route('banners.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="card-body border-top p-3 p-md-9">
                    @include('backend.banners.form-item')
                </div>

                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2">@lang('word.cancel')</button>

                    <button type="submit" class="btn btn-primary wncms-submit">
                        @include('backend.parts.submit', ['label' => __('word.create')])
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection