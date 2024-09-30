@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />

{{-- Sortable JS --}}
<script src="{{ asset('wncms/js/sortable.min.js') }}"></script>

@endpush

@section('content')

@include('wncms::backend.parts.message')

<form class="form" method="POST" action="{{ route('pages.update', ['page' => $page]) }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    @include('wncms::backend.pages.form-items', [
        'submitLabelText' => __('word.update'),
        'available_templates' => $available_templates,
    ])
</form>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush