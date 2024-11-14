@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

{{-- WNCMS toolbar filters --}}
<div class="wncms-toolbar-filter mt-5">
    <form action="{{ route('packages.index') }}">
        <div class="row gx-1 align-items-center position-relative my-1">
            @include('wncms::backend.common.default_toolbar_filters')
            <div class="col-6 col-md-auto mb-3 ms-0">
                <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
            </div>
        </div>
    </form>
</div>

{{-- WNCMS toolbar buttons --}}
<div class="wncms-toolbar-buttons mb-5">
    <div class="card-toolbar flex-row-fluid gap-1">
        {{-- Additional buttons if needed --}}
        <form id="form-add-package">
            <div class="row gx-1 align-items-center">
                <div class="col-3">
                    <input type="text" name="package" class="form-control form-control-sm" placeholder="@lang('wncms::word.package_name')">
                </div>
                <div class="col-1">
                    <input type="text" name="version" class="form-control form-control-sm" placeholder="@lang('wncms::word.version') (@lang('wncms::word.optional'))">
                </div>
                <div class="col-auto">
                    <button id="add-package-button" class="btn btn-sm btn-success fw-bold" wncms-btn-ajax
                        wncms-btn-swal
                        data-original-text="@lang('wncms::word.add_package')"
                        data-loading-text="@lang('wncms::word.loading').."
                        data-success-text="@lang('wncms::word.package_added_successfully')"
                        data-fail-text="@lang('wncms::word.fail_to_add_package')"
                        data-route="{{ route('packages.add') }}"
                        data-form="form-add-package"
                        data-method="POST"
                        data-reload="true">
                        @lang('wncms::word.add_package')
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- Display Installed Packages --}}
<div class="card card-flush rounded overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle text-nowrap mb-0">
                {{-- thead --}}
                <thead class="table-dark">
                    <tr class="text-start fw-bold gs-0">
                        <th>@lang('wncms::word.id')</th>
                        <th>@lang('wncms::word.name')</th>
                        <th>@lang('wncms::word.version')</th>
                        <th>@lang('wncms::word.update')</th>
                        <th>@lang('wncms::word.remove')</th>
                    </tr>
                </thead>

                {{-- tbody --}}
                <tbody id="packages-list" class="fw-semibold text-gray-600">
                    @foreach($packages as $index => $package)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $package['name'] }}</td>
                        <td>{{ $package['version'] }}</td>
                        <td>
                            <span class="package-status">@lang('wncms::word.checking_for_updates')...</span>
                            <button class="btn btn-sm btn-info px-2 py-1 fw-bold btn-update-package" style="display: none;"
                                wncms-btn-ajax
                                wncms-btn-swal
                                data-original-text="@lang('wncms::word.update')"
                                data-loading-text="@lang('wncms::word.loading').."
                                data-success-text="@lang('wncms::word.updated')"
                                data-fail-text="@lang('wncms::word.fail_to_update')"
                                data-route="{{ route('packages.update') }}"
                                data-method="POST"
                                data-param-package="{{ $package['name'] }}"
                                data-param-version=""
                                data-success-action="customFunctionName">
                                @lang('wncms::word.update')
                            </button>
                            <span class="no-update-status" style="display: none;">@lang('wncms::word.no_updates_available')</span>
                        </td>
                        <td>
                             {{-- remove_package --}}
                             <button type="button" class="btn btn-sm btn-danger px-2 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#modal_remove_package_{{ $index + 1 }}">@lang('wncms::word.remove')</button>
                             <div class="modal fade" tabindex="-1" id="modal_remove_package_{{ $index + 1 }}">
                                 <div class="modal-dialog">
                                     <div class="modal-content">
                                         <div class="modal-header">
                                             <h3 class="modal-title">@lang('wncms::word.remove_package')</h3>
                                         </div>
                             
                                         <div class="modal-body">
                                             <p>@lang('wncms::word.this_process_is_irreversible')</p>
                                             <p>@lang('wncms::word.removing_item', ['name' => $package['name']])</p>
                                         </div>
                             
                                         <div class="modal-footer">
                                             <button type="button" class="btn btn-sm btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                                             <button class="btn btn-sm btn-danger fw-bold"
                                             wncms-btn-ajax
                                             wncms-btn-swal
                                             data-original-text="@lang('wncms::word.remove')"
                                             data-loading-text="@lang('wncms::word.loading').."
                                             data-success-text="@lang('wncms::word.removed')"
                                             data-fail-text="@lang('wncms::word.fail_to_remove')"
                                             data-route="{{ route('packages.remove') }}"
                                             data-method="POST"
                                             data-param-package="{{ $package['name'] }}"
                                             data-reload="true">
                                             @lang('wncms::word.remove')
                                         </button>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('foot_js')
<script>
    $(document).ready(function() {
        // Check for package updates
        $.ajax({
            url: "{{ route('packages.check') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log(response);
                if (response.updates && response.updates.length > 0) {
                    response.updates.forEach(function(package) {
                        let button = $(`.btn-update-package[data-param-package="${package.name}"]`);
                        button.attr('data-param-version', package.latest);
                        button.siblings('.package-status').hide();
                        button.text(button.text() + package.latest);
                        button.show();
                    });
                }

                // Mark packages with no updates
                $('#packages-list tr').each(function() {
                    let button = $(this).find('.btn-update-package');
                    if (!button.is(':visible')) {
                        $(this).find('.package-status').hide();
                        $(this).find('.no-update-status').show();
                    }
                });
            },
            error: function() {
                alert('@lang("wncms::word.failed_to_check_updates")');
            }
        });

        function customFunctionName() {
            console.log('Executing customFunctionName');
            // Add your custom logic here
        }
    });
</script>
@endpush