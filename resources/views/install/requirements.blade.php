@extends('wncms::layouts.install')

@section('template_title')
    @lang('wncms::installer.requirements.templateTitle')
@endsection

@section('title')
    @lang('wncms::installer.requirements.title')
@endsection

@section('container')

    @foreach($requirements['requirements'] as $type => $requirement)
        <ul class="list">
            <li class="list__item list__title {{ $phpSupportInfo['supported'] ? 'success' : 'error' }}">
                <strong>{{ $type }}</strong>
                @if($type == 'php')
                    <strong>
                        <small>(version {{ $phpSupportInfo['minimum'] }} @lang('wncms::word.is_reuired'))</small>
                    </strong>
                    <span class="float-right">
                        <strong>
                            {{ $phpSupportInfo['current'] }}
                        </strong>
                        <i class="fa-regular fa fa-fw fa-{{ $phpSupportInfo['supported'] ? 'circle-check' : 'circle-xmark' }} row-icon" aria-hidden="true"></i>
                    </span>
                @elseif($type == 'php_functions')
                    <strong>
                        <small>(@lang('wncms::installer.please_enable_these_php_functions'))</small>
                    </strong>
                @endif
            </li>

            @foreach($requirements['requirements'][$type] as $extention => $enabled)
                <li class="list__item {{ $enabled ? 'success' : 'error' }}">
                    {{ $extention }}
                    <i class="fa-regular fa fa-fw fa-{{ $enabled ? 'circle-check' : 'circle-xmark' }} row-icon" aria-hidden="true"></i>
                </li>
            @endforeach
        </ul>
    @endforeach

    @if ( ! isset($requirements['errors']) && $phpSupportInfo['supported'] )
        <div class="buttons">
            <a class="button" href="{{ route('installer.permissions') }}">
                @lang('wncms::installer.requirements.next')
                <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
            </a>
        </div>
    @endif

@endsection