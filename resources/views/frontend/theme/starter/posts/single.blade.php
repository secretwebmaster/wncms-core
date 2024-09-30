@extends('wncms::frontend.theme.starter.layouts.app')

@push('head_seo')
    @include('wncms::frontend.common.seo.head-seo', [
        'seoTitle' => $post->title,
        'seoDescription' => $post->excerpt,
        'seoImage' => $post->thumbnail,
    ])
@endpush

@section('content')

single #{{ $post->id }}

@endsection