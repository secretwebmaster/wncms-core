@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="row justify-content-center mw-100 m-0">
    <div class="col-12 col-md-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.model_create', ['model_name' => __('word.permission')])</h3>
                </div>
            </div>

            <div class="collapse show">
                <form class="form" method="POST" action="{{ route('permissions.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body border-top p-3 p-md-9">
                        {{-- name --}}
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.name')</label>
                            <div class="col-lg-9 fv-row">
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}"/>
                            </div>
                        </div>
                        
                        {{-- common_suffixes --}}
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.common_suffixes')</label>
                            <div class="col-lg-9 fv-row">
                                <div class="row align-items-center mt-3">
                                    <div class="col-123 mb-3">
                                        <button type="button" class="btn_check_all btn btn-sm btn-dark px-2 py-1 fw-bold" data-target-class="common_suffix_checkbox">@lang('word.check_all')</button>
                                    </div>
                                    @foreach($common_suffixes as $index => $common_suffix)
                                        <div class="col-6 col-md-3 mb-1 common_suffix_checkbox">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="common_suffixess[]" type="checkbox" value="{{ $common_suffix }}"/>
                                                <span class="fw-bold ps-2 fs-6">{{ $common_suffix }}</span>
                                            </label>
                                        </div>
                                    @endforeach

       
                                </div>
                            </div>

                        </div>

                        {{-- roles --}}
                        <div class="row mb-3">
                            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.roles')</label>
                            <div class="col-lg-9 fv-row">
                                <div class="row align-items-center mt-3">
                                    <div class="col-123 mb-3">
                                        <button type="button" class="btn_check_all btn btn-sm btn-dark px-2 py-1 fw-bold" data-target-class="role_checkbox">@lang('word.check_all')</button>
                                    </div>
                                    @foreach($roles as $index => $role)
                                        <div class="col-6 col-md-3 mb-1 role_checkbox">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="roless[]" type="checkbox" value="{{ $role->id }}"/>
                                                <span class="fw-bold ps-2 fs-6">{{ $role->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>



                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-dark w-100" id="kt_account_profile_details_submit">
                            @include('backend.parts.submit', ['label' => __('word.create')])
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        
        <div class="alert alert-info">
            <b>命名規範:</b><br>
            {model_name}_{action}_{bulk?}<br>
            all model singular<br><br>
            <b>Example:</b><br>
            user_index 可以看列表<br>
            user_create 可以顯示創建頁<br>
            user_create_bulk 可以顯示批量創建頁<br>
            user_store 可以儲存<br>
            user_store_bulk 可以儲存批量保存<br>
        </div>

        <div class="alert alert-primary">
            @include('backend.permissions.existing_permissions')
        </div>
    </div>


</div>

@endsection

@push('foot_js')
    <script>
        $('.btn_check_all').click(function() {
            var targetClass = $(this).data('target-class');
            var checkboxes = $('.' + targetClass + ' input[type="checkbox"]');
            var allChecked = checkboxes.filter(':checked').length === checkboxes.length;
            checkboxes.prop('checked', !allChecked);
        });
    </script>
@endpush