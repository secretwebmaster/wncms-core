@extends('frontend.theme.default.layouts.app')

@section('content')
    <a class="nav-link" href="{{ route('frontend.pages.home') }}">< @lang('word.home')</a>
    <h2>@lang('word.post_list')</h2>
    <table>
        <thead>
            <th>@lang('word.id')</th>
            <th>@lang('word.title')</th>
            <th>@lang('word.category')</th>
            <th>@lang('word.tag')</th>
        </thead>
        <tbody>
            @foreach($wncms->post()->getList(pageSize:10) as $post)
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

    {!! $wncms->post()->getList(pageSize:10)->links() !!}
@endsection