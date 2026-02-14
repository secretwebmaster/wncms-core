@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush

@section('content')

    @include('wncms::backend.parts.message')

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#post-main" role="tab">@lang('wncms::word.basic')</a>
        </li>
        @php($hookTabHeaders = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tabs', [$post, request()])))
        @foreach ($hookTabHeaders as $hookTabHeader)
            {!! $hookTabHeader !!}
        @endforeach
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="post-main" role="tabpanel">
            <form class="form" method="POST" action="{{ route('posts.store', ['id' => $post->id]) }}" enctype="multipart/form-data">
                @csrf
                @include('wncms::backend.posts.form-items', ['submitLabelText' => __('wncms::word.publish')])
            </form>
        </div>
        @php($hookTabContents = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tab_contents', [$post, request()])))
        @foreach ($hookTabContents as $hookTabContent)
            {!! $hookTabContent !!}
        @endforeach
    </div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush
