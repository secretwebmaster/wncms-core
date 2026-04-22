@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.blog') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.blog')</span></a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2>{{ $tag->name }}</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms::word.url')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(wncms()->link()->getList(['tag_type' => $tag->type, 'tags' => $tag->slug, 'count' => 10]) as $link)
                        <tr>
                            <td><a href="{{ route('frontend.links.show', ['id' => $link->id]) }}">{{ $link->name }}</a></td>
                            <td>{{ $link->url }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection
