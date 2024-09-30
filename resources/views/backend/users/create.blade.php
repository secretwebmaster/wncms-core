@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">@lang('word.create_user')</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">

                <div class="row mb-m">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">@lang('word.role')</label>
                    <div class="col-lg-8 fv-row">
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="">@lang('word.please_select')</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $role->name == old('role') ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

               <div class="row mb-m">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">@lang('word.username')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="username" class="form-control form-control-sm" value="{{ old('username') }}" required/>
                    </div>
                </div> 

               <div class="row mb-m">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">@lang('word.email')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="text" name="email" class="form-control form-control-sm" value="{{ old('email') }}" required/>
                    </div>
                </div> 

                <div class="row mb-m">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">@lang('word.password')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="password" name="password" class="form-control form-control-sm" value="{{ old('password') }}" required/>
                    </div>
                </div>

                <div class="row mb-m">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">@lang('word.password_confirmation')</label>
                    <div class="col-lg-8 fv-row">
                        <input type="password" name="password_confirmation" class="form-control form-control-sm" value="{{ old('password_confirmation') }}" required/>
                    </div>
                </div>


            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.create')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection