@extends('frontend.theme.default.layouts.app')

@section('content')

    {{-- Pages --}}
    @include('frontend.theme.default.parts.page-list')

    {{-- Posts --}}
    @include('frontend.theme.default.parts.post-list')

    {{-- Tags --}}
    @include('frontend.theme.default.parts.tag-list')

    {{-- Webstie options --}}
    @include('frontend.theme.default.parts.website-options')
    
    {{-- Thenme options --}}
    @include('frontend.theme.default.parts.theme-options')

@endsection

