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
$link->title,
$link->getAttribute('title'),
) --}}

@section('content')
<a class="nav-link" href="{{ route('frontend.pages.blog') }}">@lang('wncms::word.blog')</a>
<h2>{{ $tag->name }}</h2>
<div>
    <table>
        <thead>
            <tr>
                <th>@lang('wncms::word.name')</th>
                <th>@lang('wncms::word.url')</th>
            </tr>
        </thead>
        <tbody>
            
            @foreach(wncms()->link()->getList([
                'tag_type' => $tag->type,
                'tags' => $tag->slug,
                'count' => 10,
            ]) as $link)
            <tr>
                <td><a href="{{ route('frontend.links.single', ['id' => $link->id]) }}">{{ $link->name }}</a></td>
                <td>{{ $link->url }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection