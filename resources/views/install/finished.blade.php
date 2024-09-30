@extends('wncms::layouts.install')

@section('template_title')
    @lang('wncms::installer.final.templateTitle')
@endsection

@section('title')
    {{-- <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i> --}}
    @lang('wncms::installer.final.title')
@endsection

@section('container')

    <div class="buttons">
        <a href="{{ route('login') }}" class="button">@lang('word.login')</a>
    </div>

@endsection
