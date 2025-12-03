@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

<form class="form" method="POST" action="{{ route('pages.store') }}" enctype="multipart/form-data">
    @csrf

    @include('wncms::backend.pages.form-items', [
        'submitLabelText'         => __('wncms::word.publish'),
        'available_templates'     => $available_templates ?? [],
        'page_template_options'   => $page_template_options ?? [],
        'page_template_values'    => $page_template_values ?? [],
    ])

</form>

@endsection