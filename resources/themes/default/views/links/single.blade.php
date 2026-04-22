@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.blog') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.blog')</span></a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>{{ wncms()->getModelWord('link', 'single') }}</h2>
            <div class="table-container">
                <table class="kv-table">
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
                            <td>{!! $link->{$column} !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection
