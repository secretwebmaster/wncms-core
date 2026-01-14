@extends('frontend.themes.starter.layouts.app')

@push('head_seo')
    @include('frontend.common.seo.head-seo', [
        'seoType' => 'article',
        'seoTitle' => $page->title,
        'seoDescription' => $page->excerpt,
    ])
@endpush

@section('content')

page single 

@endsection