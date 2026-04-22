@extends("$themeId::layouts.app")

@push('head_css')
    @if($page->type == 'builder')
    <style>{!! $page->css !!}</style>
    @endif
@endpush

@section('content')
    @if($page->type == 'builder')
        {!! $page->html !!}
    @else
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="page-breadcrumb">
                <a class="nav-link" href="{{ route('frontend.pages.home') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.home')</span></a>
            </div>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2>{{ $page->title }}</h2>
                <div class="page-content prose prose-slate max-w-none">
                    {!! $page->content !!}
                </div>
            </article>
        </main>
    @endif
@endsection
