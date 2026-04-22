<h2>@lang('wncms::word.post_list')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('wncms::word.id')</th>
            <th>@lang('wncms::word.thumbnail')</th>
            <th>@lang('wncms::word.title')</th>
            <th>@lang('wncms::word.category')</th>
            <th>@lang('wncms::word.tag')</th>
        </thead>
        <tbody>
            @php($thumbnailPlaceholder = asset('wncms/images/placeholders/loading_ghost.webp'))
            @foreach($posts = $wncms->post()->getList(['page_size'=>5,'count'=>24,'direction'=>'asc', 'page_name'=> 'post-page','cache' => true]) as $post)
            @php($thumbnailSrc = $post->thumbnail ?: $thumbnailPlaceholder)
            @php($postUrl = route('frontend.posts.show', ['slug' => $post->slug]))
            <tr>
                <td>{{ $post->id }}</td>
                <td><img class="post-thumbnail lazyload" src="{{ $thumbnailPlaceholder }}" data-src="{{ $thumbnailSrc }}" alt="{{ $post->title }}"></td>
                <td><a href="{{ $postUrl }}">{{ $post->title }}</a></td>
                <td>
                    @foreach($post->postCategories as $postCategory)
                    @if($loop->index != 0),@endif
                    <span><a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $postCategory->name]) }}">{{ $postCategory->name }}</a></span>
                    @endforeach
                </td>
                <td>
                    @foreach($post->postTags as $postTag)
                    @if($loop->index != 0),@endif
                    <span><a href="{{ route('frontend.posts.tag', ['type' => 'tag', 'slug' => $postTag->name]) }}">{{ $postTag->name }}</a></span>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{!! $posts->links() !!}
