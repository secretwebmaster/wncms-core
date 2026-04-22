@extends("$themeId::layouts.app")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="page-breadcrumb">
            <a class="nav-link" href="{{ route('frontend.pages.blog') }}"><span class="nav-link-icon">←</span><span>@lang('wncms::word.blog')</span></a>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="mb-0">{{ $pageTitle ?? __('wncms::word.post_archive') }}</h2>
            </div>

            @php($thumbnailPlaceholder = asset('wncms/images/placeholders/loading_ghost.webp'))

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($posts as $post)
                    @php($thumbnailSrc = $post->thumbnail ?: $thumbnailPlaceholder)
                    @php($postUrl = route('frontend.posts.show', ['slug' => $post->slug]))
                    <article class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <a href="{{ $postUrl }}" class="block bg-slate-100">
                            <img src="{{ $thumbnailPlaceholder }}" data-src="{{ $thumbnailSrc }}" alt="{{ $post->title }}" class="lazyload h-44 w-full object-cover transition duration-300 group-hover:scale-105">
                        </a>
                        <div class="p-4">
                            <h3 class="line-clamp-2 text-base font-semibold text-slate-900"><a href="{{ $postUrl }}" class="hover:text-sky-700">{{ $post->title }}</a></h3>
                            <p class="mt-2 text-xs text-slate-500">{{ __($themeId . '::word.post_id_label') }}: {{ $post->id }}</p>

                            @if($post->postCategories->count())
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach($post->postCategories as $postCategory)
                                        <a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $postCategory->name]) }}" class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky-700">{{ $postCategory->name }}</a>
                                    @endforeach
                                </div>
                            @endif

                            @if($post->postTags->count())
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($post->postTags as $postTag)
                                        <a href="{{ route('frontend.posts.tag', ['type' => 'tag', 'slug' => $postTag->name]) }}" class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-700">#{{ $postTag->name }}</a>
                                    @endforeach
                                </div>
                            @endif

                            <p class="mt-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 96) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-5">
                {!! $posts->links() !!}
            </div>
        </section>
    </main>
@endsection
