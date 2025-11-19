@if(gss('developer_mode'))
<div class="tab-pane fade {{ $activeTab === 'developer' ? 'show active' : '' }}" id="tab_developer" role="tabpanel">
    <div class="card">
        <div class="collapse show">
            <div class="card-body border border-dark border-5 rounded p-6">

                {{-- developer_mode switch --}}
                <div class="row mb-1">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">
                        @lang('wncms::word.developer_mode')
                        <br>
                        <span class="fs-xs text-gray-300">developer_mode</span>
                    </label>
                    <div class="col-lg-8 d-flex align-items-center">
                        <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                            <input type="hidden" name="settings[developer_mode]" value="0">
                            <input class="form-check-input w-35px h-20px border border-1 border-secondary"
                                type="checkbox"
                                name="settings[developer_mode]"
                                value="1"
                                {{ $settings['developer_mode'] ?? false ? 'checked' : '' }} />
                            <label class="form-check-label" for="developer_mode"></label>
                        </div>
                        <div class="text-muted p-1">@lang('wncms::word.developer_mode_description')</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endif