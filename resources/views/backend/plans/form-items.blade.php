<div class="card-body border-top p-3 p-md-9">
    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $plan->name ?? null) }}" required/>
        </div>
    </div>

    {{-- Description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="description">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $plan->description ?? null) }}</textarea>
        </div>
    </div>

    {{-- Price --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="price">@lang('wncms::word.price')</label>
        <div class="col-lg-9 fv-row">
            <input id="price" type="number" name="price" class="form-control form-control-sm" step="0.01" value="{{ old('price', $plan->price ?? null) }}" required/>
        </div>
    </div>

    {{-- Billing Cycle --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.billing_cycle')</label>
        <div class="col-lg-9 fv-row">
            <select id="billing_cycle" name="billing_cycle" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['daily', 'weekly', 'monthly', 'yearly', 'one-time'] as $cycle)
                    <option value="{{ $cycle }}" {{ $cycle === old('billing_cycle', $plan->billing_cycle ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $cycle)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Lifetime --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_lifetime">@lang('wncms::word.is_lifetime')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_lifetime" type="hidden" name="is_lifetime" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_lifetime" name="is_lifetime" value="1" {{ old('is_lifetime', $plan->is_lifetime ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_lifetime"></label>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['active', 'inactive'] as $status)
                    <option value="{{ $status }}" {{ $status === old('status', $plan->status ?? 'active') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
