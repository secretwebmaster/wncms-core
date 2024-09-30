@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">@lang('word.model_create', ['model_name' => __('word.role')])</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('roles.update', $role) }}" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            <div class="card-body border-top p-3 p-md-9">

                {{-- role_name --}}
                <div class="row mb-6">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.role_name')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="role_name" class="form-control form-control-sm" value="{{ old('role_name', $role->name) }}"/>
                    </div>
                </div>

                
                {{-- permissions --}}
                <div class="row mb-3">
                <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.permissions')</label>
                <div class="col-lg-9 fv-row">
                    <div class="row align-items-center mt-3">
                        <div class="col-12 mb-3">
                            @include('common.check_all', ['check_all_target_class' => 'permission_checkbox'])
                        </div>
                        @foreach($permissions as $index => $permission)
                            <div class="col-12 col-sm-6 col-xl-6 col-xxl-4 mb-1">
                                <label class="form-check form-check-inline form-check-solid me-5">
                                    <input class="form-check-input permission_checkbox h-20px w-20px" name="permissions[]" type="checkbox" value="{{ $permission->id }}" @if(in_array($permission->name, $role->permissions()->pluck('name')->toArray()))checked @endif/>
                                    <span class="fw-bold ps-2 fs-6 d-inline-block mw-300px text-truncate">{{ $permission->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.edit')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection