@extends('layouts.backend')
{{-- @push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush --}}

@section('content')

@include('backend.parts.message')

<div class="row justify-content-center">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.create_tag_type')</h3>
                </div>
            </div>

            <div class="collapse show">
                <form class="form" method="POST" action="{{ route('tags.store_type') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body border-top p-3 p-md-9">
                        {{-- name --}}
                        <div class="form-item mb-3">
                            <label class="form-label fw-bold fs-6">@lang('word.name')</label>     
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}"/>
                        </div>
                        {{-- slug --}}
                        <div class="form-item">
                            <label class="form-label fw-bold fs-6">@lang('word.slug')</label>     
                            <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug') }}"/>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-dark w-100" id="kt_account_profile_details_submit">
                            @include('backend.parts.submit', ['label' => __('word.create')])
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        
        <div class="alert alert-info">

            <b>命名規範:</b><br>
            {model_name}_{sub_type}<br>
            all model singular<br><br>
            <b>Slug Example:</b><br>
            post_category = 文章 Post 分類 Category<br>
            post_tag = 文章 Post 標籤 tag<br>
            video_category = 影片 Video,  分類 Category<br>
            video_tag = 影片 Video,  標籤 tag<br>


        </div>
    </div>
</div>

@endsection

@push('foot_js')
@include('common.js.tinymce')
@endpush