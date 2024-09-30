@if(false)
<script>
    window.location.href = "{{ route('frontend.pages.home') }}";
</script>
@else
    @extends('layouts.error')

    @section('content')

    <div class="d-flex flex-column flex-root" id="kt_app_root">

        <div class="d-flex flex-column flex-center flex-column-fluid">
            <div class="d-flex flex-column flex-center text-center p-10">
                <div class="card card-flush py-5">
                    <div class="card-body">
                        {{-- message for 405 error --}}
                        <h1 class="fw-bolder fs-1 text-gray-900 mb-4">Error 405</h1>
                        <div>
                            <p class="text-gray-700 fs-4">@lang('word.error_405_message')</p>
                        </div>
                        @if(!empty($exception))
                        <div class="alert alert-danger">
                            {{ $exception->getMessage() }}
                        </div>
                        @endif
                        <div class="mb-0">
                            <a href="{{ route('frontend.pages.home') }}" class="btn btn-sm btn-dark">@lang('word.return_home')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @endsection
@endif