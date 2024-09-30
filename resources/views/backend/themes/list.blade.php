@extends('layouts.backend')

@push('head_css')
<style>
    .scrollable-image-container{
        position: relative;
    }
    .scrollable-image-wrapper {
        position: relative;
        aspect-ratio:16/9;
        overflow: scroll;
        /* Set the desired height for the scrollable container */
    }
    .scrollable-image {
        width: 100%;
    }

    .scrollable-image-container i{
        position: absolute;
        bottom: 0;
        right: 0;
        margin: 20px 10px;
        padding: 10px;
        font-size: 25px;
        border-radius: 20px;
        color: white;
        background: rgb(25 25 67 / 40%);
        display: none;
    }

    .theme-card:hover i{
        display: inline;
    }

    @media screen and (max-width:768px){
        .scrollable-image-container i{
            display: inline;
        }
    }
    
</style>

@endpush
@section('content')

@include('backend.parts.message')

<div class="row">
    @foreach($themes as $theme)
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="theme-card card card-flush overflow-hidden shadow-sm">
            <div class="scrollable-image-container">
                <div class="scrollable-image-wrapper">
                    <img class="img-fluid lazyload scrollable-image" src="{{ asset('wncms/images/placeholders/upload.png') }}" data-src="{{ $theme->screenshot }}" onerror="this.src='{{ asset('wncms/images/placeholders/upload.png') }}">
                </div>
                <i class="fa-solid fa-arrows-up-down fa-bounce fa-lg"></i>
            </div>

            <div class="card-footer p-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-bold">
                        <span class="text-capitalize">{{ $theme->name }}</span><br>
                        <span class="small text-muted">@lang('word.version'): {{ $theme->version }}</span><br>
                        <span class="small text-muted">@lang('word.last_update'): {{ $theme->updated_at }}</span><br>
                    </div>
                </div>

                <div class="mt-3">
                    {{-- create_website --}}
                    <button type="button" class="btn btn-sm btn-dark w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#modal_create_workspace_{{ $theme->id }}">@lang('word.choose_theme_to_build_website_automatically')</button>
                    <div class="modal fade" tabindex="-1" id="modal_create_workspace_{{ $theme->id }}">
                        <form id="modal_create_workspace_{{ $theme->id }}" action="{{ route('workspaces.purchase') }}" method="POST">
                            @csrf
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3 class="modal-title">@lang('word.fill_in_website_info')</h3>
                                    </div>
                        
                                    <div class="modal-body">
                                        <input type="hidden" name="theme_id" value="{{ $theme->id }}">
                                        <div class="scrollable-image-container shadow-sm rounded mb-3">
                                            <div class="scrollable-image-wrapper">
                                                <img class="img-fluid lazyload scrollable-image" src="{{ asset('wncms/images/placeholders/upload.png') }}" data-src="{{ $theme->screenshot }}" onerror="this.src='{{ asset('wncms/images/placeholders/upload.png') }}">
                                            </div>
                                            <i class="fa-solid fa-arrows-up-down fa-bounce fa-lg"></i>
                                        </div>
                                        <div class="form-item mb-3">
                                            <label class="form-label fw-bold">@lang('word.price')</label>
                                            <input type="text" class="form-control border border-1 border-dark" value="500 TOKEN" disabled>
                                        </div>
                                        <div class="form-item mb-1">
                                            <label class="form-label fw-bold">@lang('word.site_name')</label>
                                            <input type="text" name="site_name" class="form-control" placeholder="@lang('word.enter_site_name_this_could_be_edited_later')">
                                        </div>
                                    </div>
                        
                                    <div class="modal-footer">
                                        <button type="submit" form-id="modal_create_workspace_{{ $theme->id }}" class="btn btn-sm btn-dark fw-bold w-100">@lang('word.submit')</button>
                                        <button type="button" class="btn btn-sm btn-secondary fw-bold w-100" data-bs-dismiss="modal">@lang('word.close')</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    @can('theme_edit')
                    <a class="btn btn-sm btn-info w-100 fw-bold mt-1" href="{{ route('themes.edit', $theme) }}">@lang('word.edit')</a>
                    @endcan
                    @can('theme_delete')
                    @include('backend.parts.modal_delete', ['model' => $theme, 'route' => route('themes.destroy', $theme), 'btn_class' => 'mt-1 w-100'])
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
