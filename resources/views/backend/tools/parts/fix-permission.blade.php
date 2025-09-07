<div class="tool-item card d-flex flex-column p-3 h-100 w-100">
    <h5 class="fw-bold">@lang('wncms::word.fix_permission')</h5>
    <p class="flex-grow-1">@lang('wncms::word.fix_permission_description')</p>

    <button type="button" class="btn btn-dark mt-auto" data-bs-toggle="modal" data-bs-target="#fixPermissionModal">
        @lang('wncms::word.run_fix_permission')
    </button>

    {{-- Modal --}}
    <div class="modal fade" id="fixPermissionModal" tabindex="-1" aria-labelledby="fixPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fixPermissionModalLabel">@lang('wncms::word.fix_permission')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>@lang('wncms::word.fix_permission_warning')</p>
                    <pre><code>chown -R www:www {{ base_path() }}</code></pre>
                    <p class="text-muted">@lang('wncms::word.fix_permission_run_as_root')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                </div>
            </div>
        </div>
    </div>
</div>
