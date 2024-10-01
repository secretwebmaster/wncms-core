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
@section('content')
    <a href="{{ route('frontend.pages.blog') }}">@lang('wncms::word.blog')</a>
    <h2>{{ wncms_model_word('faq', 'single') }}</h2>

    <h4>@lang('wncms::word.faq_detail')</h4>
    <div style="max-width: 100%;overflow-x:scroll">
        <table style="max-width: 100%;">
            <tbody>
                <tr>
                    <td>@lang('wncms::word.tag')</td>
                    <td>
                        @foreach($faq->tagsWithType('faq_tag') ?? [] as $tag)    
                        <a href="{{ route('frontend.faqs.tag', ['tagName' => $tag->name]) }}">{{ $tag->name }}</a>
                        @endforeach
                    </td>
                </tr>

                @foreach($faq->getAttributes() as $column => $value)
                    <tr>
                        <td>{{ $column }}</td>
                        <td>{{ $faq->{$column} }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    
    </div>

    <h4>@lang('wncms::word.relationships')</h4>
    <div>
        {!! $faq->getTagNameWitHtmlTag("faq_tag","li", "myClass", "myId") !!}
    </div>



@endsection