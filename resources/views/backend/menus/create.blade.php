@extends('wncms::layouts.backend')
@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush

@section('content')

@include('wncms::backend.parts.message')

<div class="row justify-content-center align-items-center h-100">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ wncms()->getModelWord('menu', 'create') }}</h3>
                </div>
            </div>

            <div class="collapse show">
                <form class="form" method="POST" action="{{ route('menus.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body border-top p-3 p-md-9">

                        {{-- website_id --}}

                        {{-- name --}}
                        <div class="row mb-6">
                            <label class="fw-bold fs-6 mb-3">@lang('wncms::word.name')</label>
                            <div class="">
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}"/>
                            </div>
                        </div>

                        {{-- hint --}}
                        <div class="alert alert-info mt-3">
                            @lang('wncms::word.menu_create_hint')
                        </div>

                        {{-- submit --}}
                        <button type="submit" class="btn btn-dark w-100" id="kt_account_profile_details_submit">
                            @include('wncms::backend.parts.submit', ['label' => __('wncms::word.create')])
                        </button>
                    </div>
                </form>


            </div>
        </div>
    </div>
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush