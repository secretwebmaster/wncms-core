@extends("$themeId::layouts.app")

@section('content')

    {{-- Page title --}}
    <h2 class="page-title">
        {{ $pageTitle ?? @lang("$themeId::word.blog") }}
    </h2>

    @if ($posts->count())
        {{-- Ranked posts --}}
        @foreach ($posts as $post)
            @include(wncms()->theme()->view($themeId, 'components.post-item'), ['post' => $post])
        @endforeach
    @else
        <p class="no-posts">@lang("$themeId::word.no_posts_found")</p>
    @endif

@endsection
