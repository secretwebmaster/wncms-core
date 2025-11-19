@extends("$themeId::layouts.app")

@section('content')

    {{-- Page title --}}
    <h2 class="page-title">
        @lang("$themeId::word.search") : {{ $keyword }}
    </h2>

    @if ($posts->count())
        {{-- Result list --}}
        @foreach ($posts as $post)
            @include(wncms()->theme()->view($themeId, 'components.post-item'), ['post' => $post])
        @endforeach

        {{-- Pagination --}}
        <div class="pagination-wrapper">
            {{ $posts->links() }}
        </div>
    @else
        <p class="no-posts">@lang("$themeId::word.no_posts_found")</p>
    @endif

@endsection
