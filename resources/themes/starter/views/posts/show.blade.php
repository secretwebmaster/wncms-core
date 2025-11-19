@extends("$themeId::layouts.app")

@section('content')
<article class="post">
    <h1>{{ $post['title'] }}</h1>

    <div class="post-meta">
        <span>{{ $post['created_at']->format('Y-m-d') }}</span>
    </div>

    <div class="post-content">
        {!! $post['content'] !!}
    </div>
</article>
@endsection
