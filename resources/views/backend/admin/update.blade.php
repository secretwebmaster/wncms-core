@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

@if(gss('disable_core_update'))
    <div class="alert alert-danger" role="alert">
        @lang('wncms::word.core_update_disabled')
    </div>
@endif

<div class="row mb-3">
    <div class="col-12">
        <div class="card card-flush rounded overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap mb-0">
                        <thead class="table-dark">
                            <tr class="text-start fw-bold gs-0">
                                {{-- <th>@lang('wncms::word.id')</th> --}}
                                <th>@lang('wncms::word.name')</th>
                                <th>@lang('wncms::word.version')</th>
                                <th>@lang('wncms::word.your_version')</th>
                                <th>@lang('wncms::word.created_at')</th>
                                <th>@lang('wncms::word.action')</th>
                            </tr>
                        </thead>
                        <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                            @foreach($result['data'] ?? [] as $itemData)
                                <tr>
                                    {{-- <td>{{ $loop->iteration }}</td> --}}
                                    <td>{{ $itemData['product'] }}</td>
                                    <td>{{ $itemData['updates']['version'] }}</td>
                                    <td>{{ gss($itemData['product'] . '_version', '-') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($itemData['updates']['released_at'])?->format('Y-m-d') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary fw-bold btn-update" 
                                            data-item-id="{{ $itemData['product'] ?? '' }}"
                                            data-package="{{ $itemData['package'] ?? '' }}"
                                            data-version="{{ $itemData['updates']['version'] }}"
                                            data-original-text="@lang('wncms::word.update')"
                                            data-loading-text="@lang('wncms::word.updating').."
                                            data-success-text="@lang('wncms::word.updated')"
                                            data-fail-text="@lang('wncms::word.retry')"
                                        >@lang('wncms::word.update')</button>
                                    </td>
                                <tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<h3 class="mt-10 mb-3">@lang('wncms::word.update_content')</h3>

{{-- Update content --}}
@include('wncms::backend.admin.update_content')

@endsection

@push('foot_js')
<script>
    $('.btn-update').on('click', function () {
        var button = $(this);

        // Retrieve button texts and item data
        var textOriginal = button.data('original-text');
        var textUpdating = button.data('loading-text');
        var textSuccess = button.data('success-text');
        var textFail = button.data('fail-text');
        var itemId = button.data('item-id');
        var package = button.data('package');
        var version = button.data('version');

        // Disable the button and update its text
        button.prop('disabled', true).text(textUpdating);

        var intervalId;

        // Function to check update progress
        var checkProgress = function () {
            console.log('Checking progress...');
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('api.v1.update.progress') }}",
                type: "POST",
                data: {
                    itemId: itemId,
                },
                success: function (data) {
                    console.log(data);
                    if (data.progress == 0) {
                        // Update complete, stop checking progress
                        console.log('Update finished, reloading...');
                        clearInterval(intervalId);
                        button.prop('disabled', false).text(textSuccess);
                        location.reload();
                    }
                },
                error: function () {
                    // Handle failure during progress check
                    clearInterval(intervalId);
                    button.prop('disabled', false).text(textFail);
                }
            });
        };

        // Start checking progress immediately
        intervalId = setInterval(checkProgress, 2000);

        // Initiate the update process
        $.ajax({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: "{{ route('api.v1.update.run') }}",
            type: "POST",
            data: {
                itemId: itemId,
                package:package,
                version: version
            },
            success: function (data) {
                // if use job
                console.log('Update started...', data);
                
                // if instant update
                // clearInterval(intervalId);
            },
            error: function () {
                // Handle failure during the update initiation
                clearInterval(intervalId);
                button.prop('disabled', false).text(textFail);
            }
        });
    });
</script>

@endpush
