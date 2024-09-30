@extends('wncms::layouts.install')

@section('template_title')
@lang('wncms::installer.welcome.templateTitle')
@endsection

@section('title')
@lang('wncms::installer.welcome.title')
@endsection

@section('container')
    <p class="text-center">
        @lang('wncms::installer.welcome.message')
    </p>
    <p class="text-center">
        <a href="{{ route('installer.requirements') }}" class="button">
            @lang('wncms::installer.welcome.next')
            <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
        </a>
    </p>
@endsection