@extends("$themeId::layouts.app")

@section('content')

    @if ($posts->count())
        @foreach ($posts as $post)
            @include(wncms()->theme()->view($themeId, 'components.post-item'), ['post' => $post])
        @endforeach

        {{-- Pagination --}}
        <div> {{ $posts->links() }}</div>
    @endif

@endsection
