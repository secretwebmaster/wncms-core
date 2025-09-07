@if(false)
<script>
    window.location.href = "{{ route('frontend.pages.home') }}";
</script>
@else
@extends('wncms::layouts.error')

@section('content')

<div class="card card-flush w-100 py-5">
    <div class="card-body">
        {{-- message for 404 error --}}
        <h1 class="fw-bolder fs-1 text-gray-900 mb-4">Error 404</h1>
        <div>
            <p class="text-gray-700 fs-4">@lang('wncms::word.error_404_message')</p>
        </div>
        @if(!empty($exception))
        <div class="alert alert-danger">
            {{ $exception->getMessage() }}
        </div>
        @endif
        <div class="mb-0">
            <a href="{{ route('frontend.pages.home') }}" class="btn btn-sm btn-dark">@lang('wncms::word.return_home')</a>
        </div>
    </div>
</div>

@endsection
@endif