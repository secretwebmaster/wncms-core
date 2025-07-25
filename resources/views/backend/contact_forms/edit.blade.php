@extends('wncms::layouts.backend')

@push('head_js')
{{-- Sortable JS --}}
<script src="{{ asset('wncms/js/sortable.min.js') }}"></script>
@endpush

@section('content')

@include('wncms::backend.parts.message')

<div class="card">
    <div class="card-header border-0 cursor-pointer px-3 px-md-9">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ wncms_model_word('contact_form', 'edit') }}</h3>
        </div>
    </div>

    <div class="collapse show">
        <form class="form" method="POST" action="{{ route('contact_forms.update', $contact_form) }}" enctype="multipart/form-data">
            @csrf
            @method("PATCH")

            @include('wncms::backend.contact_forms.form-items')

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" wncms-btn-loading class="btn btn-primary wncms-submit">
                    @include('wncms::backend.parts.submit', ['label' => __('wncms::word.edit')])
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')
@endpush