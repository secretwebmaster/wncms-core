@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages', ['slug' => 'faq']) }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.faq')</span></a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>{{ $pageTitle ?? __('wncms::word.faq_archive') }}</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <th>@lang('wncms::word.id')</th>
                        <th>@lang('wncms::word.question')</th>
                        <th>@lang('wncms::word.tag')</th>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                        <tr>
                            <td>{{ $faq->id }}</td>
                            <td><a href="{{ route('frontend.faqs.show', ['slug' => $faq->slug]) }}">{{ $faq->question }}</a></td>
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
            </div>

            {!! $faqs->links() !!}
        </div>
    </main>
@endsection
