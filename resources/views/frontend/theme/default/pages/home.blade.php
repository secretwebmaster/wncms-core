@extends('wncms::frontend.theme.default.layouts.app')

@section('content')

    {{-- Pages --}}
    @include('wncms::frontend.theme.default.parts.page-list')

    {{-- Posts --}}
    @include('wncms::frontend.theme.default.parts.post-list')

    {{-- Tags --}}
    @include('wncms::frontend.theme.default.parts.tag-list')

    {{-- Webstie options --}}
    @include('wncms::frontend.theme.default.parts.website-options')
    
    {{-- Thenme options --}}
    @include('wncms::frontend.theme.default.parts.theme-options')

@endsection

