@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.blog') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.blog')</span></a>
        </div>

        @php($thumbnailPlaceholder = asset('wncms/images/placeholders/loading_ghost.webp'))
        @php($thumbnailSrc = $post->thumbnail ?: $thumbnailPlaceholder)
        @php($postCategories = $post->postCategories ?? collect($post->post_categories ?? []))
        @php($postTags = $post->postTags ?? collect($post->post_tags ?? []))

        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <img src="{{ $thumbnailPlaceholder }}" data-src="{{ $thumbnailSrc }}" alt="{{ $post->title }}" class="lazyload h-56 w-full object-cover lg:h-full">
                    </div>
                    <div class="p-5 sm:p-6 lg:col-span-3">
                        <h1 class="text-2xl font-semibold text-slate-900">{{ $post->title }}</h1>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($postCategories as $category)
                            <a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $category->name]) }}" class="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">{{ $category->name }}</a>
                            @endforeach
                            @foreach($postTags as $tag)
                            <a href="{{ route('frontend.posts.tag', ['type' => 'tag', 'slug' => $tag->name]) }}" class="rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700">#{{ $tag->name }}</a>
                            @endforeach
                        </div>

                        @if(!empty($post->excerpt))
                            <p class="mt-5 text-sm leading-relaxed text-slate-600">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2>@lang('wncms::word.content')</h2>
                <article class="post-content">
                    {!! $post->content ?: '<p class="text-slate-500">' . __('wncms::word.n_a') . '</p>' !!}
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2>@lang('wncms::word.post') @lang('wncms::word.detail')</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">@lang('wncms::word.id')</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $post->id }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">@lang('wncms::word.slug')</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $post->slug ?: __('wncms::word.n_a') }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">@lang('wncms::word.status')</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $post->status ?: __('wncms::word.n_a') }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">@lang('wncms::word.published_at')</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $post->published_at ?: __('wncms::word.n_a') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
