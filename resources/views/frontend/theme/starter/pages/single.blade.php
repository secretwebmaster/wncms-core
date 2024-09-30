@extends('frontend.theme.starter.layouts.app')

@push('head_seo')
    @include('frontend.common.seo.head-seo', [
        'seoContentType' => 'article',
        'seoTitle' => $page->title,
        'seoDescription' => $page->excerpt,
    ])
@endpush

@section('content')

page single 

@endsection