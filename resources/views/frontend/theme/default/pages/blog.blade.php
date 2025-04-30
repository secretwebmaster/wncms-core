@extends('frontend.theme.default.layouts.app')

@section('content')
    <a class="nav-link" href="{{ route('frontend.pages.home') }}">< @lang('wncms::word.home')</a>
    <h2>@lang('wncms::word.post_list')</h2>
    <table>
        <thead>
            <th>@lang('wncms::word.id')</th>
            <th>@lang('wncms::word.title')</th>
            <th>@lang('wncms::word.category')</th>
            <th>@lang('wncms::word.tag')</th>
        </thead>
        <tbody>
            @foreach($posts = $wncms->post()->getList(['page_size' => 10]) as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td><a href="{{ $post->singleUrl }}">{{ $post->title }}</a></td>
                    <td>
                        @foreach($post->postCategories as $postCategory)
                        @if($loop->index != 0),@endif
                        <span><a href="{{ route('frontend.posts.category', ['tagName' => $postCategory->name]) }}">{{ $postCategory->name }}</a></span>
                        @endforeach
                    </td>
                    <td>
                        @foreach($post->postTags as $postTag)
                        @if($loop->index != 0),@endif
                        <span><a href="{{ route('frontend.posts.tag', ['tagName' => $postTag->name]) }}">{{ $postTag->name }}</a></span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {!! $posts->links() !!}
@endsection