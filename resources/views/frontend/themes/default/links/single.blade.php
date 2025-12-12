@extends('wncms::frontend.themes.default.layouts.app')
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
<h2>{{ wncms_model_word('link', 'single') }}</h2>
<div>
    <table>
        <tbody>
            <tr>
                <td>@lang('wncms::word.tag')</td>
                <td>
                    @foreach($link->tags as $tag)
                    <a href="{{ route('frontend.links.tag', ['type' => $tag->type, 'slug' => $tag->name]) }}">{{ $tag->name }}</a>
                    @endforeach
                </td>
            </tr>
            @foreach($link->getAttributes() as $column => $value)
            <tr>
                <td>{{ $column }}</td>
                {{-- <td>{{ $link->{$column} }}</td> --}}
                <td>{!! $link->{$column} !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection