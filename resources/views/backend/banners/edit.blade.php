@extends('layouts.backend')

@section('content')

@include('backend.parts.message')
<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">@lang('word.edit_banner')</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('banners.update' , $banner) }}" enctype="multipart/form-data">
            @method('PATCH')
            @csrf

            <div class="card-body border-top p-3 p-md-9">
                @include('backend.banners.form-item')
            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.edit')])
                </button>
            </div>

        </form>
    </div>
</div>

@endsection