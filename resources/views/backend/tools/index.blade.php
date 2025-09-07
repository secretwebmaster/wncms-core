@extends('wncms::layouts.backend')

@section('content')

<div class="row g-3">
    {{-- Fix Permission --}}
    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.fix-permission')
    </div>

    {{-- Future tools --}}
    <div class="col-12 col-md-6 col-lg-3 d-flex">
        @include('wncms::backend.tools.parts.clear-cache')
    </div>
</div>

@endsection
