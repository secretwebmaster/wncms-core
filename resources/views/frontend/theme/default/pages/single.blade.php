@extends('frontend.theme.default.layouts.app')

@push('head_css')
    @if($page->type == 'builder')
    <style>{!! $page->css !!}</style>
    @endif
@endpush

@section('content')
    @if($page->type == 'builder')
        {!! $page->html !!}
    @else
        <a href="{{ route('frontend.pages.home') }}">< @lang('word.home')</a>
        <h2>{{ $page->title }}</h2>
        <div class="page-content">
            {!! $page->content !!}
        </div>
    @endif
@endsection