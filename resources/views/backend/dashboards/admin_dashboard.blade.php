@extends('layouts.backend')

@section('content')

    @if(gss('use_custom_admin_dashbaord'))
        @includeIf('backend.dashboards.custom_admin_dashboard')
    @else
        <div class="row g-5 g-xl-10 mb-xl-10">

            @include('backend.parts.message')

            @includeIf('backend.dashboards.custom_admin_dashboard_items')
            
            @if(empty(gss('hide_default_admin_dashboard_items')))
                <div class="col-12 col-md-6">
                    <div class="row">

                        {{-- page Count --}}
                        <div class="col-6 mb-3">
                            <a href="{{ route('websites.index') }}" class="card bg-dark hoverable card-xl-stretch">
                                <div class="card-body">
                                    <i class="fa-solid fa-user fa-bounce fa-2xl"></i>
                                    <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">@lang('word.num_of_websites', ['count' => $website_count])</div>
                                    {{-- <div class="fw-semibold text-gray-100">@lang('word.websites')</div> --}}
                                </div>
                            </a>
                        </div>

                        {{-- User Count --}}
                        <div class="col-6 mb-3">
                            <a href="{{ route('users.index') }}" class="card bg-dark hoverable card-xl-stretch">
                                <div class="card-body">
                                    <i class="fa-solid fa-globe fa-bounce fa-2xl"></i>
                                    <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">@lang('word.num_of_users', ['count' => $user_count])</div>
                                    {{-- <div class="fw-semibold text-gray-100">@lang('word.users')</div> --}}
                                </div>
                            </a>
                        </div>

                        {{-- Post Count --}}
                        <div class="col-6 mb-3">
                            <a href="{{ route('posts.index') }}" class="card bg-dark hoverable card-xl-stretch">
                                <div class="card-body">
                                    <i class="fa-solid fa-pencil fa-bounce fa-2xl"></i>
                                    <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">@lang('word.num_of_posts', ['count' => $post_count])</div>
                                    {{-- <div class="fw-semibold text-gray-100">@lang('word.posts')</div> --}}
                                </div>
                            </a>
                        </div>

                        {{-- Page Count --}}
                        <div class="col-6 mb-3">
                            <a href="{{ route('pages.index') }}" class="card bg-dark hoverable card-xl-stretch">
                                <div class="card-body">
                                    <i class="fa-solid fa-file-lines fa-bounce fa-2xl"></i>
                                    <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">@lang('word.num_of_pages', ['count' => $page_count])</div>
                                    {{-- <div class="fw-semibold text-gray-100">@lang('word.pages')</div> --}}
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if(empty(gss('hide_system_update_log')))
                <div class="col-12 col-md-6">
                    <div style="max-height: 80vh;overflow-y: scroll;">
                        @include('backend.admin.update_content')
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection