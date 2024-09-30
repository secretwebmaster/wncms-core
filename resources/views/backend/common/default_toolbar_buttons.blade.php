{{-- Create --}}
@if(wncms_route_exists($model_prefix . '.create'))
    @if(auth()->user()->can(str($model_prefix)->singular() . '_' . 'create'))
    <a href="{{ route($model_prefix . '.create') }}" class="btn btn-sm btn-primary fw-bold mb-1">{{ wncms_model_word($model_prefix, 'create') }}</a>
    @endif
@endif

{{-- Bulk Create --}}
@if(wncms_route_exists($model_prefix . '.create.bulk'))
    @if(auth()->user()->can(str($model_prefix)->singular() . '_' . 'bulk_create'))
    <a href="{{ route($model_prefix . '.create.bulk') }}" class="btn btn-sm btn-primary fw-bold mb-1">{{ wncms_model_word($model_prefix, 'bulk_create') }}</a>
    @endif
@endif

{{-- Clone --}}
@if(wncms_route_exists($model_prefix . '.clone.bulk'))
    @if(auth()->user()->can(str($model_prefix)->singular() . '_' . 'clone'))
        <button class="btn btn-sm btn-info fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_clone_{{ $model_prefix }}">{{ wncms_model_word($model_prefix, 'clone') }}</button>
        <div class="modal fade" tabindex="-1" id="modal_clone_{{ $model_prefix }}">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">@lang('word.pleease_choose_destination')</h3>
                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                    </div>
                    <div class="modal-body">
                        <p>{{ wncms_model_word($model_prefix, 'clone') }}</p>
                    
                        <div class="row mb-6">
                            <div class="col">
                                <select name="website_id" data-control="select2" data-placeholder="@lang('word.please_select')" class="form-select form-select-lg bulk_clone_select_website_id">
                                    <option value="">@lang('word.please_select')</option>
                                    @foreach($websites ?? [] as $_website)
                                        <option value="{{ $_website->id }}">{{ $_website->domain }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
        
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                        <button class="btn btn-sm btn-info fw-bold bulk_clone_submit" data-model="role" data-route="{{ route($model_prefix . '.clone.bulk') }}">
                            <span class="indicator-label">@lang('word.bulk_clone')</span>
                            <span class="indicator-progress">@lang('word.please_wait')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

{{-- Bulk Delete --}}
@if(wncms_route_exists($model_prefix . '.bulk_delete'))
    @if(auth()->user()->can(str($model_prefix)->singular() . '_' . 'bulk_delete'))
        <button class="btn btn-sm btn-danger fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_delete_{{ $model_prefix }}">@lang('word.bulk_delete')</button>
        <div class="modal fade" tabindex="-1" id="modal_delete_{{ $model_prefix }}">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">@lang('word.bulk_delete')</h3>
                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-danger">@lang('word.checked_items_will_be_deleted')</p>
                    </div>
        
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                        <button class="btn btn-sm btn-danger fw-bold bulk_delete_models" data-model="{{ $model_prefix }}" data-route="{{ route($model_prefix .'.bulk_delete') }}">
                            <span class="indicator-label">@lang('word.bulk_delete')</span>
                            <span class="indicator-progress">@lang('word.bulk_delete')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

