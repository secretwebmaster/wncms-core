@extends('frontend.theme.default.layouts.app')


@section('content')
    <a class="nav-link" href="{{ route('frontend.pages.home') }}">< @lang('wncms::word.home')</a>
    <h2>@lang('wncms::word.faq')</h2>
    <table>
        <thead>
            <th>@lang('wncms::word.id')</th>
            <th>@lang('wncms::word.website')</th>
            <th>@lang('wncms::word.status')</th>
            <th>@lang('wncms::word.slug')</th>
            <th>@lang('wncms::word.question')</th>
            <th>@lang('wncms::word.answer')</th>
            <th>@lang('wncms::word.faq_tag')</th>
            <th>@lang('wncms::word.label')</th>
            <th>@lang('wncms::word.remark')</th>
            <th>@lang('wncms::word.order')</th>
            <th>@lang('wncms::word.is_pinned')</th>
            <th>@lang('wncms::word.created_at')</th>
            <th>@lang('wncms::word.updated_at')</th>
        </thead>
        <tbody>
            @foreach($wncms->faq()->getList(pageSize:10) as $faq)
                <tr>
                    <td>{{ $faq->id }}</td>
                    <td>{{ $faq->website?->domain }}</td>
                    <td>{{ $faq->status }}</td>
                    <td>{{ $faq->slug }}</td>
                    <td><a href="{{ route('frontend.faqs.single', ['slug' => $faq->slug]) }}">{{ $faq->question }}</a></td>
                    <td>{{ $faq->answer }}</td>
                    <td>{{ $faq->tagsWithType('faq_tag')->pluck('name')->implode(',') }}</td>
                    <td>{{ $faq->label }}</td>
                    <td>{{ $faq->remark }}</td>
                    <td>{{ $faq->order }}</td>
                    <td>{{ $faq->is_pinned }}</td>
                    <td>{{ $faq->created_at }}</td>
                    <td>{{ $faq->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {!! $wncms->faq()->getList(pageSize:10)->links() !!}
@endsection