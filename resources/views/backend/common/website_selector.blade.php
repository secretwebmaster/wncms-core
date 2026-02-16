@php
    $websiteMode = method_exists($model, 'getWebsiteMode') ? $model::getWebsiteMode() : 'global';
    $isEditing = (bool) ($model?->exists ?? false);
    $workingWebsiteId = (int) (session('selected_website_id') ?: (wncms()->website()->get()?->id ?? 0));
    $modelWebsiteId = (int) ($model?->website?->id ?? 0);
    $modelWebsiteIds = $model?->websites?->pluck('id')->map(fn($id) => (int) $id)->toArray() ?? [];

    $defaultSingleWebsiteId = $isEditing ? $modelWebsiteId : ($modelWebsiteId ?: $workingWebsiteId);
    $defaultMultiWebsiteIds = $isEditing
        ? $modelWebsiteIds
        : (!empty($modelWebsiteIds) ? $modelWebsiteIds : ($workingWebsiteId ? [$workingWebsiteId] : []));

    $selectedWebsiteId = (int) old('website_id', $defaultSingleWebsiteId);
    $selectedWebsiteIds = old('website_ids', $defaultMultiWebsiteIds);

    if (is_string($selectedWebsiteIds)) {
        $selectedWebsiteIds = explode(',', $selectedWebsiteIds);
    }
    $selectedWebsiteIds = array_values(array_unique(array_filter(array_map('intval', (array) $selectedWebsiteIds))));
    // dump(
    //     $websiteMode,
    //     $selectedWebsiteId,
    //     $selectedWebsiteIds
    // );
@endphp

@if(gss('multi_website') && in_array($websiteMode, ['single', 'multi']))
    @if($websiteMode === 'single')
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="website_id">@lang('wncms::word.website')</label>
            <div class="col-lg-9 fv-row">
                <select id="website_id" name="website_id" class="form-select form-select-sm">
                    <option value="">@lang('wncms::word.please_select')</option>
                    @foreach($websites ?? [] as $website)
                        <option value="{{ $website->id }}" {{ (int) $website->id === $selectedWebsiteId ? 'selected' : '' }}>{{ $website->domain }} #({{ $website->id }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    @if($websiteMode === 'multi')
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.website')</label>
            <div class="col-lg-9 fv-row">
                <div class="row">
                    @foreach($websites ?? [] as $website)
                        <div class="col-12 mb-2">
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input me-2" id="website_ids_{{ $website->id }}" name="website_ids[]" type="checkbox" value="{{ $website->id }}" {{ in_array((int) $website->id, $selectedWebsiteIds, true) ? 'checked' : '' }}>
                                <label class="form-check-label text-gray-700" for="website_ids_{{ $website->id }}">{{ $website->domain }} #({{ $website->id }})</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endif
