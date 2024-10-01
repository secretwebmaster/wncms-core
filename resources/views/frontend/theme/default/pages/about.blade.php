@extends('frontend.theme.default.layouts.app')

@section('content')

<div class="page">
    <div>
        <div><a href="{{ route('frontend.pages.home') }}">< @lang('wncms::word.home')</a></div>
        <h2>{{ $page->title }}</h2>
        @foreach($page->getWidgets() ?? [] as $widget)
            @include("frontend.theme.default.pages.widgets.{$widget}", [
                'pageWidgetIndex' => $page->getWidgetIndex($loop->index, $widget),
            ])
        @endforeach
    </div>

</div>

@endsection
