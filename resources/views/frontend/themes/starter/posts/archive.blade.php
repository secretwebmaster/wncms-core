@extends('frontend.themes.starter.layouts.app')

@section('content')

archive {{ $tag?->name ?? '' }}

@endsection