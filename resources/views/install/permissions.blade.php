@extends('wncms::layouts.install')

@section('template_title')
    @lang('wncms::installer.permissions.templateTitle')
@endsection

@section('title')
    {{-- <i class="fa fa-key fa-fw" aria-hidden="true"></i> --}}
    @lang('wncms::installer.permissions.title')
@endsection

@section('container')

    <ul class="list">
        @foreach($permissions['permissions'] as $permission)
        <li class="list__item list__item--permissions {{ $permission['isSet'] ? 'success' : 'error' }}">
            {{ $permission['folder'] }}
            <span>
                <i class="fa-regular fa fa-fw fa-{{ $permission['isSet'] ? 'circle-check' : 'circle-xmark' }}"></i>
                {{ $permission['permission'] }}
            </span>
        </li>
        @endforeach
    </ul>

    @if ( ! isset($permissions['errors']))
        <div class="buttons">
            <a href="{{ route('installer.wizard') }}" class="button">
                @lang('wncms::installer.permissions.next')
                <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
            </a>
        </div>
    @endif

@endsection
