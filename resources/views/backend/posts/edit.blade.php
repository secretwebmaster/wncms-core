@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush

@section('content')

@include('wncms::backend.parts.message')

{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ session('active_tab', 'basic') === 'basic' ? 'active' : '' }}" 
           data-bs-toggle="tab" href="#post-main" role="tab">
            @lang('wncms::word.basic')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ session('active_tab') === 'comments' ? 'active' : '' }}" 
           data-bs-toggle="tab" href="#post-comments" role="tab">
            @lang('wncms::word.comments')
        </a>
    </li>
    @php($hookTabHeaders = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tabs', [$post, request()])))
    @foreach ($hookTabHeaders as $hookTabHeader)
        {!! $hookTabHeader !!}
    @endforeach
</ul>

<div class="tab-content">
    {{-- Main Post Form --}}
    <div class="tab-pane fade {{ session('active_tab', 'basic') === 'basic' ? 'show active' : '' }}" id="post-main" role="tabpanel">
        <form class="form" method="POST" action="{{ route('posts.update', ['id' => $post->id]) }}" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            @include('wncms::backend.posts.form-items')

            <div class="mt-5">
                <button type="submit" class="btn btn-dark fw-bold">@lang('wncms::word.save')</button>
            </div>
        </form>
    </div>

    {{-- Comments Tab --}}
    <div class="tab-pane fade {{ session('active_tab') === 'comments' ? 'show active' : '' }}" id="post-comments" role="tabpanel">
        @include('wncms::backend.posts.comment-list', [
            'comments' => $post->comments()
                ->whereNull('parent_id')
                ->with(['children', 'children.user'])
                ->latest()
                ->get()
        ])
    </div>
    @php($hookTabContents = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tab_contents', [$post, request()])))
    @foreach ($hookTabContents as $hookTabContent)
        {!! $hookTabContent !!}
    @endforeach
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')

<script>
    // Persist active tab across refresh using localStorage
    document.addEventListener("DOMContentLoaded", function () {
        const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
        const activeTabKey = "post_active_tab";

        // Restore from localStorage
        const lastTab = localStorage.getItem(activeTabKey);
        if (lastTab) {
            const triggerEl = document.querySelector(`a[href="${lastTab}"]`);
            if (triggerEl) {
                new bootstrap.Tab(triggerEl).show();
            }
        }

        // Save to localStorage when switched
        tabLinks.forEach(link => {
            link.addEventListener("shown.bs.tab", function (e) {
                localStorage.setItem(activeTabKey, e.target.getAttribute("href"));
            });
        });
    });
</script>
@endpush
