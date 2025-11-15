{{-- default_toolbar_buttons.blade.php --}}
@php
    $label_prefix = $label_prefix ?? $model_prefix;

    // Default hide flags (developer can override when including this file)
    $hideToolbarCreateButton      = $hideToolbarCreateButton      ?? false;
    $hideToolbarBulkCreateButton  = $hideToolbarBulkCreateButton  ?? false;
    $hideToolbarCloneButton       = $hideToolbarCloneButton       ?? false;
    $hideToolbarBulkDeleteButton  = $hideToolbarBulkDeleteButton  ?? false;
@endphp


{{-- Create --}}
@if(!$hideToolbarCreateButton)
    @if(wncms_route_exists($model_prefix . '.create'))
        @if(auth()->user()->can(str($model_prefix)->singular() . '_create'))
            <a href="{{ route($model_prefix . '.create') }}" class="btn btn-sm btn-primary fw-bold mb-1">
                {{ wncms_model_word($label_prefix, 'create') }}
            </a>
        @endif
    @endif
@endif


{{-- Bulk Create --}}
@if(!$hideToolbarBulkCreateButton)
    @if(wncms_route_exists($model_prefix . '.create.bulk'))
        @if(auth()->user()->can(str($model_prefix)->singular() . '_bulk_create'))
            <a href="{{ route($model_prefix . '.create.bulk') }}" class="btn btn-sm btn-primary fw-bold mb-1">
                {{ wncms_model_word($label_prefix, 'bulk_create') }}
            </a>
        @endif
    @endif
@endif


{{-- Clone --}}
@if(!$hideToolbarCloneButton)
    @if(wncms_route_exists($model_prefix . '.clone.bulk'))
        @if(auth()->user()->can(str($model_prefix)->singular() . '_clone'))
            <button class="btn btn-sm btn-info fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_clone_{{ $model_prefix }}">
                {{ wncms_model_word($label_prefix, 'clone') }}
            </button>

            {{-- Modal --}}
            <div class="modal fade" tabindex="-1" id="modal_clone_{{ $model_prefix }}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('wncms::word.pleease_choose_destination')</h3>
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"></div>
                        </div>

                        <div class="modal-body">
                            <p>{{ wncms_model_word($label_prefix, 'clone') }}</p>

                            <div class="row mb-6">
                                <div class="col">
                                    <select name="website_id" data-control="select2" data-placeholder="@lang('wncms::word.please_select')" class="form-select form-select-lg bulk_clone_select_website_id">
                                        <option value="">@lang('wncms::word.please_select')</option>
                                        @foreach($websites ?? [] as $_website)
                                            <option value="{{ $_website->id }}">{{ $_website->domain }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('wncms::word.cancel')</button>
                            <button class="btn btn-sm btn-info fw-bold bulk_clone_submit" data-model="role" data-route="{{ route($model_prefix . '.clone.bulk') }}">
                                <span class="indicator-label">@lang('wncms::word.bulk_clone')</span>
                                <span class="indicator-progress">@lang('wncms::word.please_wait')...<span class="spinner-border spinner-border-sm ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endif


{{-- Bulk Delete --}}
@if(!$hideToolbarBulkDeleteButton)
    @if(wncms_route_exists($model_prefix . '.bulk_delete'))
        @if(auth()->user()->can(str($model_prefix)->singular() . '_bulk_delete'))
            <button class="btn btn-sm btn-danger fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_delete_{{ $model_prefix }}">
                {{ wncms_model_word($label_prefix, 'bulk_delete') }}
            </button>

            {{-- Modal --}}
            <div class="modal fade" tabindex="-1" id="modal_delete_{{ $model_prefix }}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">{{ wncms_model_word($label_prefix, 'bulk_delete') }}</h3>
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"></div>
                        </div>

                        <div class="modal-body">
                            <p class="alert alert-danger">@lang('wncms::word.checked_items_will_be_deleted')</p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('wncms::word.cancel')</button>
                            <button class="btn btn-sm btn-danger fw-bold bulk_delete_models" data-model="{{ $model_prefix }}" data-route="{{ route($model_prefix . '.bulk_delete') }}">
                                <span class="indicator-label">{{ wncms_model_word($label_prefix, 'bulk_delete') }}</span>
                                <span class="indicator-progress">@lang('wncms::word.please_wait')...<span class="spinner-border spinner-border-sm ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endif
