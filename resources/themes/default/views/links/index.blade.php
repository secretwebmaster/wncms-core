@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.home') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.home')</span></a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>{{ wncms()->getModelWord('link', 'index') }}</h2>
            @include("$themeId::parts.link-list")
        </div>
    </main>
@endsection
