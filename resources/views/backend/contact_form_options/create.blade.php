@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('contact_form_option', 'create') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('contact_form_options.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body border-top p-3 p-md-9">


                {{-- name --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.name')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $contact_form_option->name ?? null) }}" required/>
                    </div>
                </div>
                                
                {{-- type --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.type')</label>
                    <div class="col-lg-9 fv-row">
                        <select name="type" class="form-select form-select-sm" required>
                            <option value=""@lang('word.please_select')> @lang('word.type')</option>
                            @foreach($types ?? [] as $type)
                                <option  value="{{ $type }}" {{ $type === old('type', $starter->type ?? null) ? 'selected' :'' }}>@lang('word.option_type_' . $type)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                {{-- display_name --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.display_name')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="display_name" class="form-control form-control-sm" value="{{ old('display_name', $contact_form_option->display_name ?? null) }}"/>
                    </div>
                </div>

                
                {{-- placeholder --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.placeholder')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="placeholder" class="form-control form-control-sm" value="{{ old('placeholder', $contact_form_option->placeholder ?? null) }}"/>
                    </div>
                </div>

                
                {{-- default_value --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.default_value')</label>
                    <div class="col-lg-9 fv-row">
                        <input type="text" name="default_value" class="form-control form-control-sm" value="{{ old('default_value', $contact_form_option->default_value ?? null) }}"/>
                    </div>
                </div>
                
                
                {{-- options --}}
                <div class="row mb-3">
                    <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.options')</label>
                    <div class="col-lg-9 fv-row">
                        <textarea name="options" class="form-control" rows="10">{{ $contact_form_option->options ?? '' }}</textarea>
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