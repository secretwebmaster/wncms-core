@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush

@section('content')

    @include('wncms::backend.parts.message')

    <form class="form" method="POST" action="{{ route('pages.store') }}" enctype="multipart/form-data">
        @csrf
        @include('wncms::backend.pages.form-items', [
            'submitLabelText' => __('wncms::word.publish')
        ])
    </form>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush