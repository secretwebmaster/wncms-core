@extends('backend.users.account')

@section('account_content')

    <div class="card mb-5 mb-xl-10">
            
        {{-- Card header --}}
        <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
            {{-- Card title --}}
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">@lang('word.user_info')</h3>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('users.account.profile.update') }}" class="form fv-plugins-bootstrap5 fv-plugins-framework" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card-body">

                {{-- avatar --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.avatar')</label>
        
                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ isset($user) && $user->avatar ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ isset($user) && $user->avatar ? 'url('.asset($user->avatar).')' : 'none' }};"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                <i class="fa fa-pencil fs-7"></i>

                                <input type="file" name="avatar" accept="image/*"/>
                                {{-- remove image --}}
                                <input type="hidden" name="avatar_remove"/>
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>

                        <div class="form-text">@lang('word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
                    </div>
                </div>

                {{-- name --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.name')</label>
                    <div class="col-lg-9">
                        <div class="row">
                            
                            <div class="col-lg-6 fv-row">
                                <input type="text" name="last_name" class="form-control form-control-sm" placeholder="@lang('word.last_name')" value="{{ old('last_name', $user->last_name ?? '') }}"/>
                            </div>
                            
                            <div class="col-lg-6 fv-row">
                                <input type="text" name="first_name" class="form-control form-control-sm mb-3 mb-lg-0" placeholder="@lang('word.first_name')" value="{{ old('first_name', $user->first_name ?? '') }}"/>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- username --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.username')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="username" class="form-control form-control-sm" value="{{ old('username', $user->username ?? null) }}" disabled/>
                    </div>
                </div>
                
                {{-- email --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.email')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="email" class="form-control form-control-sm" value="{{ old('email', $user->email ?? null) }}" disabled/>
                    </div>
                </div>

                {{-- registered_at --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.registered_at')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text"  class="form-control form-control-sm" value="{{ $user->created_at->format('Y-m-d') }}" disabled/>
                    </div>
                </div>

                {{-- last_login_at --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.last_login_at')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text"  class="form-control form-control-sm" value="{{ $user->last_login_at?->format('Y-m-d') }}" disabled/>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark fw-bold w-100">@lang('word.update')</button>

            </div>
        </form>

    </div>

@endsection



