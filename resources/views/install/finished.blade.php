@extends('layouts.install')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    {{-- <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i> --}}
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')

    <div class="buttons">
        <a href="{{ route('login') }}" class="button">{{ trans('word.login') }}</a>
    </div>

@endsection
