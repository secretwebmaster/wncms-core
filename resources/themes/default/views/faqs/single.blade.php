@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.blog') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.blog')</span></a>
        </div>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2>{{ wncms()->getModelWord('faq', 'single') }}</h2>
                <div class="table-container">
                    <table class="kv-table">
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
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-slate-900">@lang('wncms::word.relationships')</h3>
                <div>
                    {!! $faq->getTagNameWitHtmlTag('faq_tag', 'li', 'myClass', 'myId') !!}
                </div>
            </section>
        </div>
    </main>
@endsection
