@extends('frontend.theme.starter.layouts.app')

@push('head_seo')
    @include('frontend.common.seo.head-seo', [
        'seoContentType' => 'article',
        'seoTitle' => $website->site_name,
        'seoDescription' => $website->site_seo_description,
        'seoKeyword' => $website->site_seo_keywords,
        'seoImage' => $website->site_logo,
    ])
@endpush

@section('content')

home

@endsection