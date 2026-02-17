@extends('wncms::layouts.backend')

@section('content')

<div class="row g-3">
    {{-- Fix Permission --}}
    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.fix-permission')
    </div>

    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.clear-cache')
    </div>

    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.install-default-theme')
    </div>

    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.rerun-core-update', [
            'core_update_versions' => $core_update_versions ?? [],
        ])
    </div>
</div>

@endsection
