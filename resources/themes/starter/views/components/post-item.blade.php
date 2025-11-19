<article class="post-item">
    <h2 class="post-item-title">
        <a href="{{ route('frontend.posts.show', $post['slug']) }}"> {{ $post['title'] }}</a>
    </h2>

    <div class="post-item-excerpt">{{ $post['excerpt'] }}</div>
</article>
