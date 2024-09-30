@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="row justify-content-center align-items-center h-100">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0 text-gray-100">@lang('word.model_create', ['model_name' => __('word.role')])</h3>
                </div>
            </div>
        
            <div class="collapse show">
                <form class="form" method="POST" action="{{ route('roles.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body border-top p-3 p-md-9">
                        <label class="form-label fw-bold fs-6">@lang('word.role_name')</label>
                        <input type="text" name="role_name" class="form-control form-control-sm" value="{{ old('name', $role->role_name ?? null) }}"/>
                    </div>
        
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit">
                            @include('backend.parts.submit', ['label' => __('word.create')])
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

@endsection