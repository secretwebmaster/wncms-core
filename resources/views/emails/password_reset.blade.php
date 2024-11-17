@extends('wncms::layouts.email')

@section('content')

{{-- Greeting --}}
<h1>{{ $greeting ?? __('wncms::word.greeting') }}</h1>

{{-- Intro Lines --}}
@foreach ($introLines ?? [] as $line)
    <p>{{ $line }}</p>
@endforeach

{{-- Action Button --}}
@isset($actionUrl)
    <p style="text-align: center;">
        <a href="{{ $actionUrl }}" class="action-button">{{ $actionText ?? __('wncms::word.action_button') }}</a>
    </p>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines ?? [] as $line)
    <p>{{ $line }}</p>
@endforeach

@endsection
