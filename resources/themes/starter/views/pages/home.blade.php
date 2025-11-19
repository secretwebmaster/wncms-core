@extends("$themeId::layouts.app")

@section('content')

@php
    $posts = wncms()->post()->getList([
        'count' => 10,
        'sort' => 'random',
        'cache' => false
    ]);
@endphp

{{ $themeId }}

@endsection