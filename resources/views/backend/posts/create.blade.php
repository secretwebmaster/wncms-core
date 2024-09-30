@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush

@section('content')

    @include('wncms::backend.parts.message')

    <form class="form" method="POST" action="{{ route('posts.store', ['post' => $post]) }}" enctype="multipart/form-data">
        @csrf
        @include('wncms::backend.posts.form-items', [
            'submitLabelText' => __('word.publish')
        ])
    </form>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush