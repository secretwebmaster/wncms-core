@extends('wncms::frontend.theme.starter.layouts.app')

@section('content')

archive {{ $tag?->name ?? '' }}

@endsection