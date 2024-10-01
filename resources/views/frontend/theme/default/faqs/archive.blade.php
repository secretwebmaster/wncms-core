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
        .pagination{
            display: flex;
            padding-left: 0;
        }
        .page-item{
            list-style: none;
            margin: 5px;
        }
    </style>
@endpush

@section('content')
    <a href="{{ route('frontend.pages', ['slug' => 'faq']) }}">< @lang('word.faq')</a>
    <h2>{{ $pageTitle ?? __('word.faq_archive') }}</h2>
    <table>
        <thead>
            <th>@lang('word.id')</th>
            <th>@lang('word.question')</th>
            <th>@lang('word.tag')</th>
        </thead>
        <tbody>
            @foreach($faqs as $faq)
            <tr>
                <td>{{ $faq->id }}</td>
                <td><a href="{{ route('frontend.faqs.single', ['slug' => $faq->slug]) }}">{{ $faq->question }}</a></td>
                <td>
                    @foreach($faq->tagsWithType('faq_tag') as $faqTag)
                    @if($loop->index != 0),@endif
                    <span><a href="{{ route('frontend.faqs.tag', ['tagName' => $faqTag->name]) }}">{{ $faqTag->name }}</a></span>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    
    {!! $faqs->links() !!}
@endsection