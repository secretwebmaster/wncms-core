@extends("$themeId::layouts.app")


@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.home') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.home')</span></a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>@lang('wncms::word.faq')</h2>
            <div class="table-container">
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
                        @foreach($faqs = $wncms->package('wncms-faqs')->faq()->getList(['page_size' => 10]) as $faq)
                            <tr>
                                <td>{{ $faq->id }}</td>
                                <td>{{ $faq->website?->domain }}</td>
                                <td>{{ $faq->status }}</td>
                                <td>{{ $faq->slug }}</td>
                                <td><a href="{{ route('frontend.faqs.show', ['slug' => $faq->slug]) }}">{{ $faq->question }}</a></td>
                                <td>{{ $faq->answer }}</td>
                                <td>{{ $faq->tagsWithType('faq_tag')->pluck('name')->implode(',') }}</td>
                                <td>{{ $faq->label }}</td>
                                <td>{{ $faq->remark }}</td>
                                <td>{{ $faq->sort }}</td>
                                <td>{{ $faq->is_pinned }}</td>
                                <td>{{ $faq->created_at }}</td>
                                <td>{{ $faq->updated_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {!! $faqs->links() !!}
        </div>
    </main>
@endsection
