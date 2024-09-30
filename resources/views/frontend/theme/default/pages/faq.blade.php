@extends('frontend.theme.default.layouts.app')


@section('content')
    <a class="nav-link" href="{{ route('frontend.pages.home') }}">< @lang('word.home')</a>
    <h2>@lang('word.faq')</h2>
    <table>
        <thead>
            <th>@lang('word.id')</th>
            <th>@lang('word.website')</th>
            <th>@lang('word.status')</th>
            <th>@lang('word.slug')</th>
            <th>@lang('word.question')</th>
            <th>@lang('word.answer')</th>
            <th>@lang('word.faq_tag')</th>
            <th>@lang('word.label')</th>
            <th>@lang('word.remark')</th>
            <th>@lang('word.order')</th>
            <th>@lang('word.is_pinned')</th>
            <th>@lang('word.created_at')</th>
            <th>@lang('word.updated_at')</th>
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