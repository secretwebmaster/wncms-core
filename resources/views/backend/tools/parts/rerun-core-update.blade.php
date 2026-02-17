<div class="tool-item card d-flex flex-column p-3 h-100 w-100">
    <h5 class="fw-bold">@lang('wncms::word.rerun_core_update')</h5>
    <p class="flex-grow-1">@lang('wncms::word.rerun_core_update_description')</p>

    <button type="button" class="btn btn-dark mt-auto" data-bs-toggle="modal" data-bs-target="#rerunCoreUpdateModal">
        @lang('wncms::word.rerun_core_update_button')
    </button>

    <div class="modal fade" id="rerunCoreUpdateModal" tabindex="-1" aria-labelledby="rerunCoreUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('tools.rerun_core_update') }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="rerunCoreUpdateModalLabel">@lang('wncms::word.rerun_core_update')</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>@lang('wncms::word.rerun_core_update_warning')</p>

                        <label class="form-label fw-bold" for="rerun_core_update_version">@lang('wncms::word.select_update_version')</label>
                        <select id="rerun_core_update_version" name="version" class="form-select" required>
                            <option value="">@lang('wncms::word.please_select')</option>
                            @foreach (($core_update_versions ?? []) as $version)
                                <option value="{{ $version }}">v{{ $version }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                        <button class="btn btn-dark" type="submit">@lang('wncms::word.rerun_core_update_button')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
