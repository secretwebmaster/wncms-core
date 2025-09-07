<div class="tool-item card d-flex flex-column p-3 h-100 w-100">
    <h5 class="fw-bold">@lang('wncms::word.clear_cache')</h5>
    <p class="flex-grow-1">@lang('wncms::word.clear_cache_description')</p>

    {{-- Open modal --}}
    <button type="button" class="btn btn-dark mt-auto" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
        @lang('wncms::word.clear_cache_button')
    </button>

    {{-- Modal --}}
    <div class="modal fade" id="clearCacheModal" tabindex="-1" aria-labelledby="clearCacheModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearCacheModalLabel">@lang('wncms::word.clear_cache')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger fw-bold">
                        ⚠️ @lang('wncms::word.clear_cache_warning')
                    </p>
                    <p>@lang('wncms::word.clear_cache_be_careful')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                    <button class="btn btn-danger"
                        type="button"
                        wncms-btn-ajax
                        wncms-btn-swal
                        data-route="{{ route('cache.flush') }}"
                        data-method="POST">
                        @lang('wncms::word.clear_cache_button')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
