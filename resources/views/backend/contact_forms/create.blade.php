@extends('layouts.backend')

@push('head_js')
{{-- Sortable JS --}}
<script src="{{ asset('wncms/js/sortable.min.js') }}"></script>
@endpush

@section('content')

    @include('backend.parts.message')

    <div class="card">
        <div class="card-header border-0 cursor-pointer px-3 px-md-9">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ wncms_model_word('contact_form', 'create') }}</h3>
            </div>
        </div>

        <div class="collapse show">
            <form class="form" method="POST" action="{{ route('contact_forms.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body border-top p-3 p-md-9">


                    {{-- name --}}
                    <div class="row mb-3">
                        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.name')</label>
                        <div class="col-lg-9 fv-row">
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $contact_form->name ?? null) }}"/>
                        </div>
                    </div>

                    {{-- title --}}
                    <div class="row mb-3">
                        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.title')</label>
                        <div class="col-lg-9 fv-row">
                            <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $contact_form->title ?? null) }}"/>
                        </div>
                    </div>

                    {{-- description --}}
                    <div class="row mb-3">
                        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.description')</label>
                        <div class="col-lg-9 fv-row">
                            <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $contact_form->description ?? null) }}"/>
                        </div>
                    </div>

                    {{-- remark --}}
                    <div class="row mb-3">
                        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.remark')</label>
                        <div class="col-lg-9 fv-row">
                            <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $contact_form->remark ?? null) }}"/>
                        </div>
                    </div>

                    {{-- contact_form_options --}}
                    <div class="row mb-3">
                        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.options')</label>
                        <div class="col-lg-9 fv-row">
                            <div class="row align-items-center mt-3" id="contact-form-options">
                                @foreach($options->sortBy('order') as $index => $option)
                                    <div class="col-12 mb-1">
                                        <label class="form-check form-check-inline form-check-solid me-5">
                                            <input class="form-check-input" name="options[]" type="checkbox" value="{{ $option->id }}" @if($contact_form->options->contains($option->id)) checked @endif/>
                                            <span class="fw-bold ps-2 fs-6">
                                                <i class="fa-solid fa-bars"></i>
                                                <span>{{ $option->display_name }}</span>
                                                @if(gss('show_developer_hints'))
                                                <span class="text-gray-300"> (#{{ $option->id }} | {{ $option->name }} | {{ $option->placeholder ?: '---' }} | {{ $option->order }})</span>
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @push('foot_js')
                            <script>
                                widgetList = document.getElementById('contact-form-options');
                                new Sortable(widgetList, {
                                    group: {
                                        name: 'shared',
                                        pull: 'clone', // To clone: set pull to 'clone',
                                        put: false, // Disable putting on the widget-list
                                        disabled: true,
                                    },
                                    animation: 150,
                                    sort: true, // Disable sorting for widget-list

                                });
                            </script>
                        @endpush
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

@push('foot_js')
@include('common.js.tinymce')
@endpush