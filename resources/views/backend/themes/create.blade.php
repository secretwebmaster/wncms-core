@extends('layouts.backend')
@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
@endpush

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('theme','create') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('themes.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">
                
                {{-- theme_thumbnail --}}
                <div class="row mb-3">
                    @include('backend.inputs.form_items', [
                        'input_data' => [
                            'type' => 'image',
                            'model' => $theme,
                            'name' => 'screenshot',
                            'image_width' => "200px",
                            'image_height' => "120px",
                            'label_col_span' => 3,
                            'input_col_span' => 9,
                    ]])
                </div>


                {{-- Status --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.status')</label>

                    <div class="col-lg-9 fv-row">
                        <select name="status" class="form-select form-select-sm" required>
                            @foreach($statuses as $key => $value)
                                <option  value="{{ $value }}" {{ $value === $theme?->status ? 'selected' :'' }}><b>@lang('word.' . $value)</b></option>
                            @endforeach
                        </select>
                    </div>
                </div>

                
                {{-- name --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.name')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $theme?->name ?? old('name') }}" required/>
                    </div>
                </div>

                
                {{-- system --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.system')</label>
                    <div class="col-lg-9 fv-row">
                        <select name="system" class="form-select form-select-sm" required>
                            <option value="">@lang('word.please_select')</option>
                            @foreach($systems as $system)
                                <option  value="{{ $system }}" {{ $system === $theme?->system ? 'selected' :'' }}><b>@lang('word.' . $system)</b></option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                {{-- path --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.path')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="path" class="form-control form-control-sm" value="{{ $theme?->path ?? old('path') }}" required/>
                    </div>
                </div>
                

                {{-- demo_url --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.demo_url')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="demo_url" class="form-control form-control-sm" value="{{ $theme?->demo_url ?? old('demo_url') }}" required/>
                    </div>
                </div>
                
                {{-- version --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.version')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="version" class="form-control form-control-sm" value="{{ $theme?->version ?? old('version') }}"/>
                    </div>
                </div>
                
                {{-- author --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.author')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="author" class="form-control form-control-sm" value="{{ $theme?->author ?? old('author') }}"/>
                    </div>
                </div>

                
                {{-- price --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.price')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="number" name="price" class="form-control form-control-sm" value="{{ $theme?->price ?? old('price') }}"/>
                    </div>
                </div>
                
                {{-- description --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.description')</label>
                    <div class="col-lg-9 fv-row">
                        <textarea class="form-control" name="description" rows="10">{{ $theme?->description }}</textarea>
                    </div>
                </div>





            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-white btn-active-light-primary me-2">@lang('word.cancel')</button>

                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('backend.parts.submit', ['label' => __('word.create')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection