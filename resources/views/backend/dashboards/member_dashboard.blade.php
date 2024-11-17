@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- Header --}}
    <div class="row g-5 gx-xl-10">
        <div class="col-12">
            <div class="card border-transparent" data-theme="light" style="background-color: #1C325E;">
                <div class="card-body d-flex px-xl-10 align-items-center">
                    <div class="m-0">
                        <div class="position-relative fs-2x z-index-2 fw-bold text-white mb-7">
                            <span class="me-2">@lang('wncms::word.welcome_back')!</span>
                            <br>
                            <span>@lang('wncms::word.pick_a_service_to_start')</span>
                        </div>
                    </div>
                    <div class="ms-auto d-none d-md-block">
                        <img class="mw-150px" src="{{ asset('wncms/images/logos/favicon.png') }}" class="position-absolute me-3 bottom-0 end-0 h-200px" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="row g-5 gx-xl-10">

    </div>

@endsection