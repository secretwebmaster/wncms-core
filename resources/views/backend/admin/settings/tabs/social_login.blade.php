<ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#social_login_provider_google" type="button" role="tab">@lang('wncms::word.google')</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="social_login_provider_google" role="tabpanel">
        <div class="row mb-1">
            @include('wncms::backend.admin.settings.label', [
                'tab_content' => ['name' => 'google_client_id'],
                'settings' => $settings,
            ])

            <div class="col-lg-8 fv-row">
                <input type="text" class="form-control form-control-sm" name="settings[google_client_id]" value="{{ $settings['google_client_id'] ?? '' }}">
            </div>
        </div>

        <div class="row mb-1">
            @include('wncms::backend.admin.settings.label', [
                'tab_content' => ['name' => 'google_client_secret'],
                'settings' => $settings,
            ])

            <div class="col-lg-8 fv-row">
                <input type="text" class="form-control form-control-sm" name="settings[google_client_secret]" value="{{ $settings['google_client_secret'] ?? '' }}">
            </div>
        </div>

        <div class="row mb-1">
            @include('wncms::backend.admin.settings.label', [
                'tab_content' => ['name' => 'google_redirect'],
                'settings' => $settings,
            ])

            <div class="col-lg-8 fv-row">
                <input type="text" class="form-control form-control-sm" name="settings[google_redirect]" value="{{ $settings['google_redirect'] ?? url('/panel/login/google/callback') }}">
            </div>
        </div>

        <div class="offset-lg-4 col-lg-8 mt-4">
            <div class="alert alert-info mb-4">@lang('wncms::word.please_save_social_login_settings_before_testing_google')</div>

            <div class="d-flex flex-wrap gap-2">
                <a href="https://console.cloud.google.com/auth/branding" class="btn btn-sm btn-info fw-bold" target="_blank" rel="noopener noreferrer">@lang('wncms::word.open_google_setup_page')</a>
                <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modal_google_setup_guide">@lang('wncms::word.view_google_setup_guide')</button>
                <a href="{{ route('settings.google_test') }}" class="btn btn-sm btn-dark fw-bold">@lang('wncms::word.test_google_config')</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="modal_google_setup_guide">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('wncms::word.google_setup_guide')</h3>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">@lang('wncms::word.google_setup_guide_intro')</div>

                <ol class="mb-0 ps-4">
                    <li class="mb-3">@lang('wncms::word.google_setup_step_1')</li>
                    <li class="mb-3">@lang('wncms::word.google_setup_step_2')</li>
                    <li class="mb-3">@lang('wncms::word.google_setup_step_3')</li>
                    <li class="mb-3">@lang('wncms::word.google_setup_step_4')</li>
                    <li class="mb-0">@lang('wncms::word.google_setup_step_5')</li>
                </ol>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
            </div>
        </div>
    </div>
</div>
