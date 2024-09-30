@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

{{-- 工具欄 --}}
<div class="card-header align-items-center pt-5 gap-2 gap-md-5">
    <div class="card-title">
        <form action="{{ route('posts.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                {{-- 分類 --}}
                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">@lang('word.select_category')</option>
                        @foreach($post_category_parants as $category)
                            <option value="{{ $category->name }}" @if($category->name == request()->category) selected @endif>{{ $category->name }}</option>
                            @php
                                $children = $category->children;
                            @endphp

                            @while($children->isNotEmpty())
                                @foreach($children as $child)
                                    <option value="{{ $child->name }}" @if($child->name == request()->category) selected @endif>-- {{ $child->name }}</option>
                                @endforeach
                                @php
                                    $children = $child->children;
                                @endphp
                 
                            @endwhile

                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail', 'show_trashed', 'show_thumbnail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('word.' . $show)</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>
</div>

{{-- WNCMS toolbar buttons --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">

        {{-- Create + Bilk Create + Clone + Bulk Delete --}}
        @include('backend.common.default_toolbar_buttons', [
            'model_prefix' => 'posts'
        ])

        {{-- Bulk Soft Delete --}}
        <button class="btn btn-sm btn-danger fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_delete_post">@lang('word.bulk_delete')</button>
        <div class="modal fade" tabindex="-1" id="modal_delete_post">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">@lang('word.pleease_choose_destination')</h3>
                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-danger">@lang('word.posts_will_be_deleted')</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                        <button class="btn btn-sm btn-danger fw-bold bulk_delete_models" data-model="Post" data-route="{{ route('models.bulk_delete') }}">
                            <span class="indicator-label">@lang('word.bulk_delete')</span>
                            <span class="indicator-progress">@lang('word.bulk_delete')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk Delete --}}
        <button class="btn btn-sm btn-danger fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_force_delete_post">@lang('word.bulk_force_delete')</button>
        <div class="modal fade" tabindex="-1" id="modal_force_delete_post">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">@lang('word.pleease_choose_destination')</h3>
                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-danger">@lang('word.posts_will_be_deleted')</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                        <button class="btn btn-sm btn-danger fw-bold bulk_force_delete_models" data-model="Post" data-force="1" data-route="{{ route('models.bulk_force_delete') }}">
                            <span class="indicator-label">@lang('word.bulk_force_delete')</span>
                            <span class="indicator-progress">@lang('word.bulk_force_delete')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Handle Tags --}}
        <button type="button" class="btn btn-sm btn-info fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_update_post_tag">@lang('word.handling_post_tags')</button>
        <div class="modal fade" tabindex="-1" id="modal_update_post_tag">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form_bulk_sync_tags" action="{{ route('posts.bulk_sync_tags') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.handling_post_tags')</h3>
                        </div>

                        <div class="modal-body">
                            <div class="form-item mb-3">
                                <label for="" class="form-label">@lang('word.action')</label>
                                <select class="form-select" name="action">
                                    @foreach(['attach', 'detach', 'sync'] as $action)
                                    <option value="{{ $action }}">@lang('word.' . $action)</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Category --}}
                            <div class="form-item mb-3">
                                <label class="form-label">@lang('word.category')</label>
                                <input id="tagify_post_category" class="form-control form-control-sm p-0" name="post_categories" />

                                @php
                                    $categories = wnTag()->getList(tagType: 'post_category')->map(function ($tag) {
                                        return ['value' => $tag->id, 'name' => $tag->name];
                                    })->toArray();
                                @endphp
                                
                                @push('foot_js')
                                    <script type="text/javascript">
                                        window.addEventListener('DOMContentLoaded', (event) => {
                                            var input = document.querySelector("#tagify_post_category");
                                            var whitelist = @json($categories);
                                    
                                            // Initialize Tagify
                                            tagify = new Tagify(input, {
                                                whitelist: whitelist,
                                                enforceWhitelist: false,
                                                skipInvalid: true,
                                                duplicates: false,
                                                tagTextProp: 'name',
                                                maxTags: 999,
                                                dropdown: {
                                                    maxItems: 100,
                                                    mapValueTo : 'name',
                                                    classname: "tagify__inline__suggestions",
                                                    enabled: 0,
                                                    closeOnSelect: false,
                                                    searchKeys: ['name', 'value'],
                                                },
                                            });
                                        });
                                    </script>
                                @endpush
                            </div>

                            {{-- Tag --}}
                            <div class="form-item mb-3">
                                <label class="form-label">@lang('word.tag')</label>
                                <input id="tagify_post_tag" class="form-control form-control-sm p-0" name="post_tags" />

                                @php
                                    $tags = wnTag()->getList(tagType: 'post_tag')->map(function ($tag) {
                                        return ['value' => $tag->id, 'name' => $tag->name];
                                    })->toArray();
                                @endphp

                                @push('foot_js')
                                    <script type="text/javascript">
                                        window.addEventListener('DOMContentLoaded', (event) => {
                                            var input = document.querySelector("#tagify_post_tag");
                                            var whitelist = @json($tags);
                                    
                                            // Initialize Tagify
                                            tagify = new Tagify(input, {
                                                whitelist: whitelist,
                                                enforceWhitelist: false,
                                                skipInvalid: true,
                                                duplicates: false,
                                                tagTextProp: 'name',
                                                maxTags: 999,
                                                dropdown: {
                                                    maxItems: 100,
                                                    mapValueTo : 'name',
                                                    classname: "tagify__inline__suggestions",
                                                    enabled: 0,
                                                    closeOnSelect: false,
                                                    searchKeys: ['name', 'value'],
                                                },
                                            });
                                        });
                                    </script>
                                @endpush
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                            <button type="button" class="btn btn-primary"
                                wncms-btn-ajax
                                wncsm-btn-swal
                                wncms-get-model-ids
                                data-model="post"
                                data-route="{{ route('posts.bulk_sync_tags') }}"
                                data-method="post"
                                data-form="form_bulk_sync_tags"
                                date-original-text="@lang('word.submit')"
                                data-submitted-btn-text="@lang('word.loading')"
                            >@lang('word.submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_mulk_set_website">@lang('word.bulk_set_website')</button>
        <div class="modal fade" tabindex="-1" id="modal_mulk_set_website">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form_bulk_set_website" action="#" method="POST">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.bulk_set_website')</h3>
                        </div>
            
                        <div class="modal-body">
                            <div class="form-item">
                                <select name="website_id" id="" class="form-select form-select-sm">
                                    <option value="">@lang('word.please_select')</option>
                                    @foreach($websites as $_website)
                                    <option value="{{ $_website->id }}">{{ $_website->domain }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                            <button type="button" class="btn btn-primary fw-bold"
                                wncms-btn-ajax
                                wncms-get-model-ids
                                wncms-btn-swal
                                data-form="form_bulk_set_website"
                                data-original-text="@lang('word.submit')"
                                data-loading-text="@lang('word.loading').."
                                data-success-text="@lang('word.submitted')"
                                data-fail-text="@lang('word.fail_to_submit')"
                                data-route="{{ route('posts.bulk_set_websites') }}"
                                data-method="POST"
                            >@lang('word.submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- one_click_create_demo_posts --}}
        <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#one_click_create_demo_posts">@lang('word.one_click_create_demo_posts')</button>
        <div class="modal fade" tabindex="-1" id="one_click_create_demo_posts">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('posts.generate_demo_posts') }}" method="POST">
                        @csrf

                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.one_click_create_demo_posts')</h3>
                        </div>
            
                        <div class="modal-body">
                            <div class="alert alert-info">@lang('word.one_click_create_demo_posts_description')</div>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                            <button type="submit" class="btn btn-primary fw-bold">@lang('word.submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- bulk_clone_posts --}}
        <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_bulk_clone_posts">@lang('word.bulk_clone_posts')</button>
        <div class="modal fade" tabindex="-1" id="modal_bulk_clone_posts">
            <div class="modal-dialog">
                <form id="form_bulk_clone_posts">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.bulk_clone_posts')</h3>
                        </div>
            
                        <div class="modal-body">
                            <select name="clone_status" class="form-select">
                                <option value="published">@lang('word.published')</option>
                                <option value="drafted">@lang('word.drafted')</option>
                            </select>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                            <button type="button" class="btn btn-primary fw-bold"
                                wncms-btn-ajax
                                wncms-get-model-ids
                                wncms-btn-swal
                                data-form="form_bulk_clone_posts"
                                data-original-text="@lang('word.submit')"
                                data-loading-text="@lang('word.loading').."
                                data-success-text="@lang('word.submitted')"
                                data-fail-text="@lang('word.fail_to_submit')"
                                data-route="{{ route('posts.bulk_clone') }}"
                                data-method="POST"
                            >@lang('word.submit')</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

{{-- Model Data --}}
<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover align-middle text-nowrap mb-0">
                <thead class="table-dark">
                    <tr class="fw-bold gs-0">
                        <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                            </div>
                        </th>
                        <th>@lang('word.action')</th>
                        <th>@lang('word.id')</th>
                        <th>@lang('word.user')</th>
                        <th>@lang('word.website') <a href="javascript:;" title="@lang('word.preview_the_post_on_specific_domain')"><i class="fa-regular fa-circle-question"></i></a></th>
                        <th>@lang('word.status')</th>
                        {{-- <th>@lang('word.is_trashed')</th> --}}

                        @if(request()->show_detail)<th>
                            @lang('word.visibility')</th>
                        @endif

                        @if(request()->show_detail || request()->show_thumbnail)
                        <th>@lang('word.thumbnail')</th>
                        @endif

                        <th>@lang('word.title') <a href="javascript:;" title="@lang('word.preview_the_post_on_current_domain')"><i class="fa-regular fa-circle-question"></i></button></th>
                        <th>@lang('word.category')</th>

                        @if(request()->show_detail)
                        <th>@lang('word.tag')</th>
                        @endif

                        <th>@lang('word.is_pinned')</th>
                        <th>@lang('word.order')</th>

                        <th>@lang('word.published_at')</th>

                        @if(request()->show_detail)
                        <th>@lang('word.created_at')</th>
                        <th>@lang('word.updated_at')</th>
                        <th>@lang('word.expired_at')</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                    @foreach($posts as $post)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $post->id }}" />
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('posts.edit' , $post) }}">@lang('word.edit')</a>
                                <a class="btn btn-sm btn-info fw-bold px-2 py-1" href="{{ route('posts.clone' , $post) }}">@lang('word.clone')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$post , 'route' => route('posts.destroy' , $post)])
                                @if($post->trashed())
                                <a class="btn btn-sm btn-success fw-bold px-2 py-1" href="{{ route('posts.restore' , $post) }}">@lang('word.restore')</a>
                                @endif
                            </td>
                            <td>{{ $post->id }}</td>
                            <td>{{ $post->user?->username }}</td>
                            <td>
                                @foreach($post->websites as $website)
                                <span title="{{ $website->domain }}"><a href="{{ $wncms->getRoute('frontend.posts.single', ['slug' => $post->slug], false, $website->domain) }}" target="_blank">#{{$website->id}}</a></span>
                                @endforeach
                            </td>
                            <td>@include('common.table_status', ['model' => $post])</td>
                            {{-- <td>@if($post->trashed()) <span class="badge badge-danger">@lang('word.trashed')</span>@endif</td> --}}

                            @if(request()->show_detail)
                            <td>{{ $post->visibility }}</td>
                            @endif

                            @if(request()->show_detail || request()->show_thumbnail)
                            <td><img class="lazyload mw-100px rounded" src="{{ $post->thumbnail }}" alt=""></td>
                            @endif

                            <td class="mw-400px text-truncate"><a href="{{ route('frontend.posts.single', ['slug' => $post->slug]) }}" target="_blank" title="{{ $post->title }}">{{ $post->title }}</a></td>
                            <td class="mw-500px text-truncate">{{ $post->TagsWithType('post_category')->implode('name',',') }}</td>

                            @if(request()->show_detail)
                            <td>{{ $post->TagsWithType('post_tag')->implode('name',',') }}</td>
                            @endif

                            <td>@include('common.table_is_active', ['model' => $post, 'active_column' => 'is_pinned'])</td>
                            <td>{{ $post->order }}</td>

                            <td>{{ $post->published_at }}</td>
                            
                            @if(request()->show_detail)
                            <td>{{ $post->created_at }}</td>
                            <td>{{ $post->updated_at }}</td>
                            <td>{{ $post->expired_at }}</td>
                            @endif
                        <tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-5">
    {{ $posts->withQueryString()->links() }}
</div>

@endsection

@push('foot_js')
    <script>
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>
@endpush