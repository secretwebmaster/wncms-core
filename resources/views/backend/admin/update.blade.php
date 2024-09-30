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
                                <th>@lang('wncms::word.id')</th>
                                <th>@lang('wncms::word.created_at')</th>
                                <th>@lang('wncms::word.name')</th>
                                <th>@lang('wncms::word.version')</th>
                                <th>@lang('wncms::word.your_version')</th>
                                <th>@lang('wncms::word.action')</th>
                            </tr>
                        </thead>
                        <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                            @foreach($result['data'] ?? [] as $itemType => $itemData)

                                @if(!empty(end($itemData['updates'])))
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ !empty(end($itemData['updates'])['released_at']) ? \Carbon\Carbon::parse(end($itemData['updates'])['released_at'])?->format('Y-m-d') : '-' }}</td>
                                        <td class="w-100">{{ $itemType }}</td>
                                        <td>{{ $itemData['latest_version'] ?? '-' }}</td>
                                        <td>{{ end($itemData['updates'])['your_version'] ?? '-'}}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary fw-bold btn-update" 
                                                data-item-type="{{ $itemData['type'] ?? '' }}" 
                                                data-item-id="{{ $itemData['id'] ?? '' }}"
                                                data-original-text="@lang('wncms::word.update')"
                                                data-loading-text="@lang('wncms::word.updating').."
                                                data-success-text="@lang('wncms::word.updated')"
                                                data-fail-text="@lang('wncms::word.retry')"
                                            >@lang('wncms::word.update')</button>
                                        </td>
                                    <tr>
                                @endif
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

            var text_original = button.data('original-text');
            var text_updating = button.data('loading-text');
            var text_success= button.data('success-text');
            var text_fail = button.data('fail-text');

            var itemType = button.data('item-type');
            var itemId = button.data('item-id');

            // Disable the button to prevent multiple clicks.
            button.prop('disabled', true);
            button.text(text_updating);

            var checkProgress = function () {
                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    url: "{{ route('api.v1.update.progress') }}",
                    data: {
                        itemType: itemType,
                        itemId: itemId,
                    },
                    type: "POST",
                    success: function (data) {
                        console.log(data);
                        if (data.progress == '1') {
                            setTimeout(checkProgress, 5000); // Wait 5 seconds and check again.
                        }else{
                            console.log('should reload');
                            button.prop('disabled', false);
                            location.reload();
                        }
                    },
                    fail: function(data){
                        button.prop('disabled', false);
                        button.text(text_fail);
                    }
                });
            };

            // Initiate the update process.
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('api.v1.update') }}",
                data: {
                    itemType: itemType,
                    itemId: itemId,
                },
                type: "POST",
                success: function (data) {
                    console.log(data);
                    // Start checking progress after initiating the update.
                    setTimeout(checkProgress, 5000); // Wait 5 seconds and check.
                },
                fail: function(data){
                    button.prop('disabled', false);
                    button.text(text_fail);
                }
            });
        });
    </script>
@endpush
