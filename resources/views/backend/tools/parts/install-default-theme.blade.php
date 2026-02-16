<div class="tool-item card d-flex flex-column p-3 h-100 w-100">
    <h5 class="fw-bold">@lang('wncms::word.install_default_theme')</h5>
    <p class="flex-grow-1">@lang('wncms::word.install_default_theme_description')</p>

    <button type="button" class="btn btn-dark mt-auto" data-bs-toggle="modal" data-bs-target="#installDefaultThemeModal">
        @lang('wncms::word.install_default_theme_button')
    </button>

    <div class="modal fade" id="installDefaultThemeModal" tabindex="-1" aria-labelledby="installDefaultThemeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="installDefaultThemeModalLabel">@lang('wncms::word.install_default_theme')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>@lang('wncms::word.install_default_theme_warning')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                    <button class="btn btn-dark" type="button" wncms-btn-ajax wncms-btn-swal data-route="{{ route('tools.install_default_theme') }}" data-method="POST">
                        @lang('wncms::word.install_default_theme_button')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
