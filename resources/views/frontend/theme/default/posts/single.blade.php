@extends('frontend.theme.default.layouts.app')
@push('head_css')
<style>
    table {
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid black;
        padding: 3px;
        text-align: left;
        font-size: 12px;
    }

    th {
        background-color: #f2f2f2;
        /* Optional: Add a background color to the header cells */
    }
</style>
@endpush

{{-- @dd(
     $post->title,
     $post->getAttribute('title'),
) --}}

@section('content')
    <a class="nav-link" href="{{ route('frontend.pages.blog') }}">< @lang('wncms::word.blog')</a>
    <h2>{{ wncms_model_word('post', 'single') }}</h2>

    <div style="max-width: 100%;overflow-x:scroll">
        <table style="max-width: 100%;">
            <tbody>
                <tr>
                    <td>@lang('wncms::word.category')</td>
                    <td>
                        @foreach($post->post_categories as $category)    
                        <a href="{{ route('frontend.posts.category', ['tagName' => $category->name]) }}">{{ $category->name }}</a>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td>@lang('wncms::word.tag')</td>
                    <td>
                        @foreach($post->post_tags as $tag)    
                        <a href="{{ route('frontend.posts.tag', ['tagName' => $tag->name]) }}">{{ $tag->name }}</a>
                        @endforeach
                    </td>
                </tr>
                @foreach($post->getAttributes() as $column => $value)
                    <tr>
                        <td>{{ $column }}</td>
                        {{-- <td>{{ $post->{$column} }}</td> --}}
                        <td>{!! $post->{$column} !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    
    </div>

    <h4>@lang('wncms::word.relationships')</h4>
    <div>
        {!! $post->getTagNameWitHtmlTag("post_category","li", "myClass", "myId") !!}
    </div>



@endsection